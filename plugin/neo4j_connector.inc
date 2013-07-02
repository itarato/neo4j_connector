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
   * @param $entity_type
   * @param $entity
   */
  public function addEntity($entity, $entity_type = 'node') {
    list($id, $rid, $bundle) = entity_extract_ids($entity_type, $entity);
    $node = $this->client->makeNode(array(
      'title' => $entity->title,
      'entity_id' => $id,
      'rid' => $rid,
      'entity_type' => $entity_type,
      'bundle' => $bundle,
    ));
    $node->save();
    $this->getIndex(NEO4J_CONNECTOR_ENTITY_INDEX)->add($node, 'entity_id', $id);
    watchdog(__FUNCTION__, 'Graph node has been created: @eid -> @nid', array('@eid' => $id, '@nid' => $node->getId()));

    $field_instances = field_info_instances($entity_type, $bundle);
    foreach ($field_instances as $field_instance) {
      if ($neo4jFieldHandler = Neo4JDrupalFieldHandlerFactory::getInstance($field_instance['widget']['module'], $node)) {
        $neo4jFieldHandler->processFieldData($entity, $entity_type, $field_instance['field_name']);
      }
    }
  }

  /**
   * @param $entity_type
   * @param $entity
   */
  public function deleteEntity($entity, $entity_type = 'node') {
    $propCont = $this->getNodeOfEntity($entity, $entity_type);
    if ($propCont) {
      $node = $this->client->getNode($propCont->getId());

      $relationships = $node->getRelationships();
      foreach ($relationships as $relationship) {
        $relationship->delete();
      }

      $this->getIndex(NEO4J_CONNECTOR_ENTITY_INDEX)->remove($node);
      $node->delete();
      watchdog(__FUNCTION__, 'Graph node has been deleted: @nid', array('@nid' => $node->getId()));
    }
  }

  /**
   * @param $entity
   * @param string $entity_type
   */
  public function updateEntity($entity, $entity_type = 'node') {
    $this->deleteEntity($entity, $entity_type);
    $this->addEntity($entity, $entity_type);
  }

  /**
   * @param $entity_type
   * @param $entity
   * @return \Everyman\Neo4j\PropertyContainer
   */
  public function getNodeOfEntity($entity, $entity_type = 'node') {
    list($id,,) = entity_extract_ids($entity_type, $entity);
    return $this->getNodeByEntityID($id);
  }

  /**
   * @param $entity_id
   * @return \Everyman\Neo4j\PropertyContainer
   */
  public function getNodeByEntityID($entity_id) {
    // @todo use entity_type
    return $this->getIndex(NEO4J_CONNECTOR_ENTITY_INDEX)->findOne('entity_id', $entity_id);
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
