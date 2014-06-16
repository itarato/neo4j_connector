<?php
/**
 * @file
 * Field handler classes.
 */

namespace Drupal\search_api_neo4j;

use Drupal\field\Entity\FieldConfig;
use Drupal\neo4j_connector\Neo4JIndexParam;
use Everyman\Neo4j\Node;

/**
 * Class Neo4JDrupalReferenceFieldHandler
 * Entity reference based field handler.
 */
class Neo4JDrupalReferenceFieldHandler extends Neo4JDrupalAbstractFieldHandler {

  /**
   * Index that locates the graph node.
   *
   * @var Neo4JIndexParam
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
   * @param Neo4JIndexParam $index_param
   *  Index.
   * @param $ref_entity_type
   *  Referenced entity_type.
   */
  public function __construct(Node $graph_node, FieldConfig $field_info, Neo4JIndexParam $index_param, $ref_entity_type) {
    parent::__construct($graph_node, $field_info);
    $this->indexParam = $index_param;
    $this->refEntityType = $ref_entity_type;
  }

  /**
   * Implements Neo4JDrupalAbstractFieldHandler::processFieldItem().
   */
  public function processFieldItem($value) {
    $client = neo4j_connector_get_client();
    $this->indexParam->value = $value;
    $client->connectOrCreate($this->node, $this->indexParam, NEO4J_ENTITY_INDEX_DOMAIN, "{$this->refEntityType}:{$value}", $this->fieldInfo->name);
  }

}
