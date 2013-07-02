<?php
/**
 * @file
 */

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Index\NodeIndex;
use Everyman\Neo4j\Node;

define('NEO4J_CONNECTOR_ENTITY_INDEX', 'entity_index');

class Neo4JDrupal {

  public $client;

  public function __construct() {
    $this->client = new Client();
  }

  public static function sharedInstance() {
    static $instance = NULL;
    if (!$instance) {
      $instance = new Neo4JDrupal();
    }

    return $instance;
  }

  /**
   * @param $index_name
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
   * @param $query
   * @return \Everyman\Neo4j\Query\ResultSet
   */
  public function query($template, $vars = array()) {
    $query = new Everyman\Neo4j\Cypher\Query($this->client, $template, $vars);
    return $query->getResultSet();
  }

  /**
   * @param $entity
   * @param string $entity_type
   * @param array $properties
   * @param Neo4JDrupalIndexParam $indexParam
   */
  public function addEntity($entity, $entity_type = 'node', array $properties, Neo4JDrupalIndexParam $indexParam = NULL) {
    $node = $this->addGraphNode($properties, $indexParam);
    $this->addEntityFields($entity, $entity_type, $node);
    return $node;
  }

  /**
   * @param array $properties
   * @param Neo4JDrupalIndexParam $indexParam
   * @return Node
   */
  public function addGraphNode(array $properties, Neo4JDrupalIndexParam $indexParam = NULL) {
    $node = $this->client->makeNode();
    $node->setProperties($properties);
    $node->save();

    if ($indexParam) {
      $this->getIndex($indexParam->name)->add($node, $indexParam->key, $indexParam->value);
    }

    watchdog(__FUNCTION__, 'Graph node has been created: @nid', array('@nid' => $node->getId()));

    return $node;
  }

  /**
   * @param Neo4JDrupalIndexParam $indexParam
   */
  public function deleteEntity(Neo4JDrupalIndexParam $indexParam) {
    $this->deleteEntityConnections($indexParam);

    if ($node = $indexParam->getNode()) {
      $this->getIndex($indexParam->name)->remove($node);
      $node->delete();
      watchdog(__FUNCTION__, 'Graph node has been deleted: @nid', array('@nid' => $node->getId()));
    }
  }

  public function deleteEntityConnections(Neo4JDrupalIndexParam $indexParam) {
    if ($node = $indexParam->getNode()) {
      $relationships = $node->getRelationships();
      foreach ($relationships as $relationship) {
        $relationship->delete();
      }
    }
  }

  /**
   * @param $entity
   * @param string $entity_type
   * @param array $properties
   * @param Neo4JDrupalIndexParam $indexParam
   */
  public function updateEntity($entity, $entity_type = 'node', array $properties, Neo4JDrupalIndexParam $indexParam = NULL) {
    $this->deleteEntity($indexParam);
    $this->addEntity($entity, $entity_type, $properties, $indexParam);
  }

  /**
   * @param $entity
   * @param $entity_type
   * @param $node
   */
  public function addEntityFields($entity, $entity_type, $node) {
    list(, , $bundle) = entity_extract_ids($entity_type, $entity);
    $field_instances = field_info_instances($entity_type, $bundle);
    foreach ($field_instances as $field_instance) {
      if ($neo4jFieldHandler = Neo4JDrupalFieldHandlerFactory::getInstance($field_instance['widget']['module'], $node)) {
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
        return new Neo4JDrupalEntityreferenceFieldHandler($node, $module_name, 'REFERENCES');

      case 'text':
        return new Neo4JDrupalSimpleValueFieldHandler($node, $module_name, 'HAS_TEXT', 'text_field_index', 'value');

      case 'number':
        return new Neo4JDrupalSimpleValueFieldHandler($node, $module_name, 'HAS_NUMBER', 'number_field_index', 'value');

      case 'taxonomy':
        return new Neo4JDrupalTaxonomyFieldHandler($node, $module_name, 'HAS_TERM', 'term_index');

    }

    return null;
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

    foreach ($items as $item) {
      $this->processFieldItem($item);
    }
  }

  public abstract function processFieldItem(array $item);

}

class Neo4JDrupalSimpleValueFieldHandler extends Neo4JDrupalAbstractFieldHandler {

  public $fieldValueKey;

  public $referenceName;

  public function __construct(Node $node, $type, $indexName, $referenceName, $fieldValueKey) {
    parent::__construct($node, $type, $referenceName);
    $this->indexName = $indexName;
    $this->fieldValueKey = $fieldValueKey;
    $this->referenceName = $referenceName;
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

    $this->node->relateTo($field_node, $this->indexName)->save();
  }

}

class Neo4JDrupalEntityreferenceFieldHandler extends Neo4JDrupalAbstractFieldHandler {

  public function processFieldItem(array $item) {
    $referencedNode = Neo4JDrupal::sharedInstance()->getNodeByEntityID($item['target_id']);
    if ($referencedNode) {
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

    $termNode = Neo4JDrupal::sharedInstance()->getIndex($this->index)->findOne('id', $term->tid);

    if (!$termNode) {
      $termNode = Neo4JDrupal::sharedInstance()->client->makeNode(array(
        'title' => $term->name,
        'id' => $term->tid,
        'type' => 'taxonomy_term',
      ));
      $termNode->save();
      Neo4JDrupal::sharedInstance()->getIndex($this->index)->add($termNode, 'id', $term->tid);
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
      return $this->client->getNode($propCont->getId());
    }
    return FALSE;
  }

}
