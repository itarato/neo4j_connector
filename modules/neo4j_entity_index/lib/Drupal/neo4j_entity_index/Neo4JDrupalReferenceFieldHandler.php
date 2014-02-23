<?php
/**
 * @file
 * Field handler classes.
 */

namespace Drupal\neo4j_entity_index;

use Drupal\field\Entity\Field;
use Drupal\neo4j_connector\Neo4JDrupal;
use Drupal\neo4j_connector\Neo4JDrupalIndexParam;
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
    $client = neo4j_connector_get_client();
    $client->connectOrCreate($this->node, $this->indexParam, NEO4J_ENTITY_INDEX_DOMAIN, "{$this->refEntityType}:{$value}", $this->fieldInfo->name);
  }

}
