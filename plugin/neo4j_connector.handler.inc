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
   * @param Node $graph_node
   *  Graph node.
   * @return Neo4JDrupalAbstractFieldHandler|NULL
   */
  public static function getInstance($module_name, Node $graph_node) {
    switch ($module_name) {

      case 'entityreference':
        $indexParam = new Neo4JDrupalIndexParam(NEO4J_CONNECTOR_ENTITY_INDEX_PREFIX . 'node', 'entity_id');
        return new Neo4JDrupalReferenceFieldHandler($graph_node, $module_name, 'NODE_REFERENCE', $indexParam, 'target_id', 'node');

      case 'node_reference':
        $indexParam = new Neo4JDrupalIndexParam(NEO4J_CONNECTOR_ENTITY_INDEX_PREFIX . 'node', 'entity_id');
        return new Neo4JDrupalReferenceFieldHandler($graph_node, $module_name, 'NODE_REFERENCE', $indexParam, 'nid', 'node');

      case 'user_reference':
        $indexParam = new Neo4JDrupalIndexParam(NEO4J_CONNECTOR_ENTITY_INDEX_PREFIX . 'user', 'entity_id');
        return new Neo4JDrupalReferenceFieldHandler($graph_node, $module_name, 'USER_REFERENCE', $indexParam, 'uid', 'user');

      case 'text':
        return new Neo4JDrupalSimpleValueFieldHandler($graph_node, $module_name, 'HAS_TEXT', 'text_field_index', 'value');

      case 'number':
        return new Neo4JDrupalSimpleValueFieldHandler($graph_node, $module_name, 'HAS_NUMBER', 'number_field_index', 'value');

      case 'taxonomy':
        $indexParam = new Neo4JDrupalIndexParam(NEO4J_CONNECTOR_ENTITY_INDEX_PREFIX . 'taxonomy_term', 'entity_id');
        return new Neo4JDrupalReferenceFieldHandler($graph_node, $module_name, 'TAXONOMY_TERM_REFERENCE', $indexParam, 'tid', 'taxonomy_term');
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
   * @param Node $graph_node
   * @param $type
   * @param $reference_name
   */
  public function __construct(Node $graph_node, $type, $reference_name) {
    $this->node = $graph_node;
    $this->type = $type;
    $this->referenceName = $reference_name;
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
   * @param Node $graph_node
   *  Graph node to attach to.
   * @param $type
   *  Type of graph node.
   * @param $reference_name
   *  Name of the relationship.
   * @param $indexName
   *  Name of the index.
   * @param $fieldValueKey
   *  Key of the value in the field array.
   */
  public function __construct(Node $graph_node, $type, $reference_name, $indexName, $fieldValueKey) {
    parent::__construct($graph_node, $type, $reference_name);
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

  protected $refEntityType;

  /**
   * Constructor.
   *
   * @param Node $graph_node
   *  Graph node.
   * @param $type
   *  Type.
   * @param $reference_name
   *  Name of relationship.
   * @param Neo4JDrupalIndexParam $index_param
   *  Index.
   * @param $field_value_key
   *  Key of the value.
   * @param $ref_entity_type
   *  Referenced entity_type.
   */
  public function __construct(Node $graph_node, $type, $reference_name, Neo4JDrupalIndexParam $index_param, $field_value_key, $ref_entity_type) {
    parent::__construct($graph_node, $type, $reference_name);
    $this->indexParam = $index_param;
    $this->fieldValueKey = $field_value_key;
    $this->refEntityType = $ref_entity_type;
  }

  /**
   * Implements Neo4JDrupalAbstractFieldHandler::processFieldItem().
   */
  public function processFieldItem(array $item) {
    $this->indexParam->value = $item[$this->fieldValueKey];

    $referencedNode = Neo4JDrupal::sharedInstance()->getGraphNodeOfIndex($this->indexParam);

    if (!$referencedNode) {
      $referencedNode = neo4j_connector_send_to_index_without_fields($this->refEntityType, $this->indexParam->value);
    }

    if ($referencedNode) {
      $this->node->relateTo($referencedNode, $this->referenceName)->save();
    }
    else {
      watchdog(__CLASS__, 'Unable to connect to reference. Type: @type, id: @id.', array(
        '@type' => $this->refEntityType,
        '@id' => $this->indexParam->value,
      ), WATCHDOG_ERROR);
    }
  }

}
