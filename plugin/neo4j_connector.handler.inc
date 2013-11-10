<?php
/**
 * @file
 * Field handler classes.
 */

use Everyman\Neo4j\Node;

/**
 * Class Neo4JDrupalFieldHandlerFactory
 * Factory to create field handler instances.
 */
class Neo4JDrupalFieldHandlerFactory {

  /**
   * Create an instance of the appropriate field handler.
   *
   * @param $module_name
   *  Name of the module that defines the field.
   * @param Node $node
   *  Graph node.
   * @return Neo4JDrupalAbstractFieldHandler|NULL
   */
  public static function getInstance($module_name, Node $node) {
    switch ($module_name) {

      case 'entityreference':
        $indexParam = new Neo4JDrupalIndexParam(NEO4J_CONNECTOR_ENTITY_INDEX_PREFIX . 'node', 'entity_id');
        return new Neo4JDrupalReferenceFieldHandler($node, $module_name, 'NODE_REFERENCE', $indexParam, 'target_id');

      case 'node_reference':
        $indexParam = new Neo4JDrupalIndexParam(NEO4J_CONNECTOR_ENTITY_INDEX_PREFIX . 'node', 'entity_id');
        return new Neo4JDrupalReferenceFieldHandler($node, $module_name, 'NODE_REFERENCE', $indexParam, 'nid');

      case 'user_reference':
        $indexParam = new Neo4JDrupalIndexParam(NEO4J_CONNECTOR_ENTITY_INDEX_PREFIX . 'user', 'user_id');
        return new Neo4JDrupalReferenceFieldHandler($node, $module_name, 'USER_REFERENCE', $indexParam, 'uid');

      case 'text':
        return new Neo4JDrupalSimpleValueFieldHandler($node, $module_name, 'HAS_TEXT', 'text_field_index', 'value');

      case 'number':
        return new Neo4JDrupalSimpleValueFieldHandler($node, $module_name, 'HAS_NUMBER', 'number_field_index', 'value');

      case 'taxonomy':
        return new Neo4JDrupalTaxonomyFieldHandler($node, $module_name, 'HAS_TERM', NEO4J_CONNECTOR_ENTITY_INDEX_PREFIX . 'taxonomy_term');

    }

    return NULL;
  }

}

/**
 * Class Neo4JDrupalAbstractFieldHandler
 */
abstract class Neo4JDrupalAbstractFieldHandler {

  /**
   * Graph node that the fields will be attached to.
   *
   * @var Everyman\Neo4j\Node
   */
  protected $node;

  /**
   * Type.
   *
   * @var string
   */
  protected $type;

  /**
   * Name of the relationship.
   *
   * @var string
   */
  public $referenceName;

  /**
   * Constructor.
   *
   * @param Node $node
   * @param $type
   * @param $referenceName
   */
  public function __construct(Node $node, $type, $referenceName) {
    $this->node = $node;
    $this->type = $type;
    $this->referenceName = $referenceName;
  }

  /**
   * Goes through of the fields and handle them.
   *
   * @param $entity
   *  Drupal entity object.
   * @param $entity_type
   *  Drupal entity type.
   * @param $field_name
   *  Field name.
   */
  public function processFieldData($entity, $entity_type, $field_name) {
    $items = field_get_items($entity_type, $entity, $field_name);

    if (!$items) {
      return;
    }

    foreach ($items as $item) {
      $this->processFieldItem($item);
    }
  }

  /**
   * Placeholder for the individual field processing.
   *
   * @param array $item
   * @return mixed
   */
  public abstract function processFieldItem(array $item);

}

/**
 * Class Neo4JDrupalSimpleValueFieldHandler
 * A value based field handler - contains a single value.
 */
class Neo4JDrupalSimpleValueFieldHandler extends Neo4JDrupalAbstractFieldHandler {

  /**
   * Key that holds the value (nid, value, target_id, ...).
   *
   * @var string
   */
  public $fieldValueKey;

  /**
   * Name of the associated index.
   *
   * @var string
   */
  public $indexName;

  /**
   * Constructor.
   *
   * @param Node $node
   *  Graph node to attach to.
   * @param $type
   *  Type of graph node.
   * @param $referenceName
   *  Name of the relationship.
   * @param $indexName
   *  Name of the index.
   * @param $fieldValueKey
   *  Key of the value in the field array.
   */
  public function __construct(Node $node, $type, $referenceName, $indexName, $fieldValueKey) {
    parent::__construct($node, $type, $referenceName);
    $this->indexName = $indexName;
    $this->fieldValueKey = $fieldValueKey;
  }

  /**
   * Implements Neo4JDrupalAbstractFieldHandler::processFieldItem().
   */
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

/**
 * Class Neo4JDrupalReferenceFieldHandler
 * Entity reference based field handler.
 */
class Neo4JDrupalReferenceFieldHandler extends Neo4JDrupalAbstractFieldHandler {

  /**
   * Index that locates the graph node.
   *
   * @var Neo4JDrupalIndexParam
   */
  public $indexParam;

  /**
   * Key that contains the value.
   *
   * @var string
   */
  protected $fieldValueKey;

  /**
   * Constructor.
   *
   * @param Node $node
   *  Graph node.
   * @param $type
   *  Type.
   * @param $referenceName
   *  Name of relationship.
   * @param Neo4JDrupalIndexParam $indexParam
   *  Index.
   * @param $fieldIDKey
   *  Key of the value.
   */
  public function __construct(Node $node, $type, $referenceName, Neo4JDrupalIndexParam $indexParam, $fieldIDKey) {
    parent::__construct($node, $type, $referenceName);
    $this->indexParam = $indexParam;
    $this->fieldValueKey = $fieldIDKey;
  }

  /**
   * Implements Neo4JDrupalAbstractFieldHandler::processFieldItem().
   */
  public function processFieldItem(array $item) {
    $this->indexParam->value = $item[$this->fieldValueKey];
    if ($referencedNode = $this->indexParam->getNode()) {
      $this->node->relateTo($referencedNode, $this->referenceName)->save();
    }
  }

}

/**
 * Class Neo4JDrupalTaxonomyFieldHandler
 * Taxonomy term reference field handler.
 */
class Neo4JDrupalTaxonomyFieldHandler extends Neo4JDrupalAbstractFieldHandler {

  /**
   * Index name.
   *
   * @var string
   */
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
