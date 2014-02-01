<?php
/**
 * @file
 * Field handler classes.
 */

namespace Drupal\neo4j_connector;

use Drupal\field\Entity\Field;
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

  protected $refEntityType;

  /**
   * Constructor.
   *
   * @param Node $graph_node
   *  Graph node.
   * @param $field_info
   *  Field info.
   * @param Neo4JDrupalIndexParam $index_param
   *  Index.
   * @param $ref_entity_type
   *  Referenced entity_type.
   */
  public function __construct(Node $graph_node, Field $field_info, Neo4JDrupalIndexParam $index_param, $ref_entity_type) {
    parent::__construct($graph_node, $field_info);
    $this->indexParam = $index_param;
    $this->refEntityType = $ref_entity_type;
  }

  /**
   * Implements Neo4JDrupalAbstractFieldHandler::processFieldItem().
   */
  public function processFieldItem($value) {
    $this->indexParam->value = $value;

    $referencedNode = Neo4JDrupal::sharedInstance()->getGraphNodeOfIndex($this->indexParam);

    if (!$referencedNode) {
      $referencedNode = neo4j_connector_send_to_index_without_fields($this->refEntityType, $value);
    }

    if ($referencedNode) {
      $this->node->relateTo($referencedNode, $this->fieldInfo->name)->save();
    }
    else {
      watchdog(__CLASS__, 'Unable to connect to reference. Type: @type, id: @id.', array(
        '@type' => $this->refEntityType,
        '@id' => $this->indexParam->value,
      ), WATCHDOG_ERROR);
    }
  }

}
