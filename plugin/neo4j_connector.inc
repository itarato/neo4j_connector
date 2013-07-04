<?php
/**
 * @file
 * Neo4J connector classes and interfaces.
 */

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Index\NodeIndex;
use Everyman\Neo4j\Node;

/**
 * Class Neo4JDrupal
 * Main connector to the Neo4J database.
 */
class Neo4JDrupal {

  /**
   * Main DB client.
   *
   * @var Everyman\Neo4j\Client
   */
  public $client;

  /**
   * Constructor.
   * Use Neo4JDrupal::sharedInstance() instead.
   */
  public function __construct() {
    $this->client = new Client();
  }

  /**
   * Singleton instance. It's the best to use this, unless you want some tricks.
   *
   * @return Neo4JDrupal
   */
  public static function sharedInstance() {
    static $instance = NULL;
    if (!$instance) {
      $instance = new Neo4JDrupal();
    }

    return $instance;
  }

  /**
   * Request an index on the fly.
   *
   * @param $index_name string
   * @return NodeIndex
   */
  public function getIndex($index_name) {
    static $indexes = array();

    if (!isset($indexes[$index_name])) {
      $indexes[$index_name] = new NodeIndex($this->client, $index_name);
      $indexes[$index_name]->save();
    }

    return $indexes[$index_name];
  }

  /**
   * Execute a query.
   *
   * @param $template
   *  Query string.
   * @param array $vars
   *  Variables.
   * @return \Everyman\Neo4j\Query\ResultSet
   */
  public function query($template, $vars = array()) {
    $query = new Everyman\Neo4j\Cypher\Query($this->client, $template, $vars);
    return $query->getResultSet();
  }

  /**
   * Adds a new Drupal entity to the DB.
   * Also takes care about fields.
   *
   * @param $entity
   *  Entity object.
   * @param string $entity_type
   *  Entity type string.
   * @param array $properties
   *  Properties array to store on the graph node.
   * @param Neo4JDrupalIndexParam $indexParam
   *  Index to locate the new node.
   * @return Node
   *  Created graph node object.
   */
  public function addEntity($entity, $entity_type = 'node', array $properties, Neo4JDrupalIndexParam $indexParam = NULL) {
    $node = $this->addGraphNode($properties, $indexParam);
    $this->addEntityFields($entity, $entity_type, $node);
    return $node;
  }

  /**
   * Create graph node.
   *
   * @param array $properties
   *  Array of properties.
   * @param Neo4JDrupalIndexParam $indexParam
   *  Index.
   * @return Node
   */
  public function addGraphNode(array $properties, Neo4JDrupalIndexParam $indexParam = NULL) {
    $node = $this->client->makeNode();
    $node->setProperties($properties);
    $node->save();

    if ($indexParam) {
      $this->getIndex($indexParam->name)->add($node, $indexParam->key, $indexParam->value);
    }

    watchdog(__FUNCTION__, 'Graph node has been created: @id', array('@id' => $node->getId()));

    return $node;
  }

  /**
   * Delete a graph node using the index.
   *
   * @param Neo4JDrupalIndexParam $indexParam
   *  Index.
   */
  public function deleteEntity(Neo4JDrupalIndexParam $indexParam) {
    $this->deleteRelationships($indexParam);

    if ($node = $indexParam->getNode()) {
      $this->getIndex($indexParam->name)->remove($node);
      $node->delete();
      watchdog(__FUNCTION__, 'Graph node has been deleted: @nid', array('@nid' => $node->getId()));
    }
  }

  /**
   * Remove all relationships from a graph node.
   *
   * @param Neo4JDrupalIndexParam $indexParam
   *  Index.
   */
  public function deleteRelationships(Neo4JDrupalIndexParam $indexParam) {
    if ($node = $indexParam->getNode()) {
      $relationships = $node->getRelationships();
      foreach ($relationships as $relationship) {
        $relationship->delete();
      }
    }
  }

  /**
   * Update a Drupal entity.
   * Removes and reestablish all relationships (fields and references).
   *
   * @param $entity
   *  Drupal entity.
   * @param string $entity_type
   *  Entity type.
   * @param array $properties
   *  Properties to store.
   * @param Neo4JDrupalIndexParam $indexParam
   *  Index.
   */
  public function updateEntity($entity, $entity_type = 'node', array $properties, Neo4JDrupalIndexParam $indexParam = NULL) {
    $this->deleteEntity($indexParam);
    $this->addEntity($entity, $entity_type, $properties, $indexParam);
  }

  /**
   * Create field relationships of an entity.
   *
   * @param $entity
   *  Drupal entity.
   * @param $entity_type
   *  Entity type.
   * @param $node
   *  Graph node.
   */
  public function addEntityFields($entity, $entity_type, Node $node) {
    list(, , $bundle) = entity_extract_ids($entity_type, $entity);
    $field_instances = field_info_instances($entity_type, $bundle);
    foreach ($field_instances as $field_instance) {
      $field_info = field_info_field($field_instance['field_name']);
      if ($neo4jFieldHandler = Neo4JDrupalFieldHandlerFactory::getInstance($field_info['module'], $node)) {
        $neo4jFieldHandler->processFieldData($entity, $entity_type, $field_instance['field_name']);
      }
    }
  }

}

class Neo4JDrupalFieldHandlerFactory {

  /**
   * @param array $field_instance
   * @return Neo4JDrupalAbstractFieldHandler
   */
  public static function getInstance($module_name, Node $node) {
    switch ($module_name) {

      case 'entityreference':
        $indexParam = new Neo4JDrupalIndexParam(NEO4J_CONNECTOR_NODE_INDEX, 'entity_id');
        return new Neo4JDrupalReferenceFieldHandler($node, $module_name, 'NODE_REFERENCE', $indexParam, 'target_id');

      case 'node_reference':
        $indexParam = new Neo4JDrupalIndexParam(NEO4J_CONNECTOR_NODE_INDEX, 'entity_id');
        return new Neo4JDrupalReferenceFieldHandler($node, $module_name, 'NODE_REFERENCE', $indexParam, 'nid');

      case 'user_reference':
        $indexParam = new Neo4JDrupalIndexParam(NEO4J_CONNECTOR_USER_INDEX, 'user_id');
        return new Neo4JDrupalReferenceFieldHandler($node, $module_name, 'USER_REFERENCE', $indexParam, 'uid');

      case 'text':
        return new Neo4JDrupalSimpleValueFieldHandler($node, $module_name, 'HAS_TEXT', 'text_field_index', 'value');

      case 'number':
        return new Neo4JDrupalSimpleValueFieldHandler($node, $module_name, 'HAS_NUMBER', 'number_field_index', 'value');

      case 'taxonomy':
        return new Neo4JDrupalTaxonomyFieldHandler($node, $module_name, 'HAS_TERM', NEO4J_CONNECTOR_TERM_INDEX);

    }

    return NULL;
  }

}

abstract class Neo4JDrupalAbstractFieldHandler {

  protected $node;

  protected $type;

  public $referenceName;

  public function __construct(Node $node, $type, $referenceName) {
    $this->node = $node;
    $this->type = $type;
    $this->referenceName = $referenceName;
  }

  public function processFieldData($entity, $entity_type, $field_name) {
    $items = field_get_items($entity_type, $entity, $field_name);

    if (!$items) {
      return;
    }

    foreach ($items as $item) {
      $this->processFieldItem($item);
    }
  }

  public abstract function processFieldItem(array $item);

}

class Neo4JDrupalSimpleValueFieldHandler extends Neo4JDrupalAbstractFieldHandler {

  public $fieldValueKey;

  public $referenceName;

  public function __construct(Node $node, $type, $referenceName, $indexName, $fieldValueKey) {
    parent::__construct($node, $type, $referenceName);
    $this->indexName = $indexName;
    $this->fieldValueKey = $fieldValueKey;
  }

  public function processFieldItem(array $item) {
    $index = Neo4JDrupal::sharedInstance()->getIndex($this->indexName);
    $field_node = $index->findOne('value', $item[$this->fieldValueKey]);

    if (!$field_node) {
      $field_node = Neo4JDrupal::sharedInstance()->client->makeNode(array(
        'value' => $item[$this->fieldValueKey],
        'type' => $this->type,
      ));
      $field_node->save();
      Neo4JDrupal::sharedInstance()->getIndex($this->indexName)->add($field_node, 'value', $item[$this->fieldValueKey]);
    }

    $this->node->relateTo($field_node, $this->referenceName)->save();
  }

}

class Neo4JDrupalReferenceFieldHandler extends Neo4JDrupalAbstractFieldHandler {

  public $indexParam;

  protected $fieldIDKey;

  public function __construct(Node $node, $type, $referenceName, Neo4JDrupalIndexParam $indexParam, $fieldIDKey) {
    parent::__construct($node, $type, $referenceName);
    $this->indexParam = $indexParam;
    $this->fieldIDKey = $fieldIDKey;
  }

  public function processFieldItem(array $item) {
    $this->indexParam->value = $item[$this->fieldIDKey];
    if ($referencedNode = $this->indexParam->getNode()) {
      $this->node->relateTo($referencedNode, $this->referenceName)->save();
    }
  }

}

class Neo4JDrupalTaxonomyFieldHandler extends Neo4JDrupalAbstractFieldHandler {

  public $index;

  public function __construct(Node $node, $type, $referenceName, $index) {
    parent::__construct($node, $type, $referenceName);
    $this->index = $index;
  }

  public function processFieldItem(array $item) {
    $term = taxonomy_term_load($item['tid']);

    $termNode = Neo4JDrupal::sharedInstance()->getIndex($this->index)->findOne('tid', $term->tid);

    if (!$termNode) {
      $termNode = Neo4JDrupal::sharedInstance()->client->makeNode(array(
        'title' => $term->name,
        'tid' => $term->tid,
        'entity_type' => 'taxonomy_term',
      ));
      $termNode->save();
      Neo4JDrupal::sharedInstance()->getIndex($this->index)->add($termNode, 'tid', $term->tid);
    }

    $this->node->relateTo($termNode, $this->referenceName)->save();
  }

}

class Neo4JDrupalIndexParam {

  public $name;

  public $key;

  public $value;

  /**
   * @param null $name
   * @param null $key
   * @param null $value
   */
  public function __construct($name = NULL, $key = NULL, $value = NULL) {
    $this->name = $name;
    $this->key = $key;
    $this->value = $value;
  }

  /**
   * @return Node|NULL
   */
  public function getNode() {
    $propCont = Neo4JDrupal::sharedInstance()->getIndex($this->name)->findOne($this->key, $this->value);
    if ($propCont) {
      return Neo4JDrupal::sharedInstance()->client->getNode($propCont->getId());
    }
    return FALSE;
  }

}
