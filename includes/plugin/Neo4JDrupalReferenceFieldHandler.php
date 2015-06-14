<?php
/**
 * @file
 */

use Everyman\Neo4j\Node;

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
   * @var string
   */
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
  public function processFieldItem(array $item, stdClass $entity) {
    $this->indexParam->value = $item[$this->fieldValueKey];

    $referencedNode = Neo4JDrupal::sharedInstance()->getGraphNodeOfIndex($this->indexParam);

    if (!$referencedNode) {
      $referencedNode = neo4j_connector_send_to_index_without_fields($this->refEntityType, $this->indexParam->value);
    }

    if ($referencedNode) {
      $this->node->relateTo($referencedNode, $this->referenceName)
        ->setProperty(Neo4JDrupal::OWNER, $this->node->getId())
        ->save();
    }
    else {
      watchdog(__CLASS__, 'Unable to connect to reference. Type: @type, id: @id.', array(
        '@type' => $this->refEntityType,
        '@id' => $this->indexParam->value,
      ), WATCHDOG_ERROR);
    }
  }

}
