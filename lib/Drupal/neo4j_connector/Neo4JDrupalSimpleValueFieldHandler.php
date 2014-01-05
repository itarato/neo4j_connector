<?php
/**
 * Created by PhpStorm.
 * User: itarato
 * Date: 12/27/13
 * Time: 12:48 AM
 */

namespace Drupal\neo4j_connector;

use Everyman\Neo4j\Node;

/**
 * Class Neo4JDrupalSimpleValueFieldHandler
 * A value based field handler - contains a single value.
 */
class Neo4JDrupalSimpleValueFieldHandler extends Neo4JDrupalAbstractFieldHandler {

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
   */
  public function __construct(Node $graph_node, $type, $reference_name, $indexName) {
    parent::__construct($graph_node, $type, $reference_name);
    $this->indexName = $indexName;
  }

  /**
   * Implements Neo4JDrupalAbstractFieldHandler::processFieldItem().
   */
  public function processFieldItem($value) {
    $index = Neo4JDrupal::sharedInstance()->getIndex($this->indexName);
    $field_node = $index->findOne('value', $value);

    if (!$field_node) {
      $field_node = Neo4JDrupal::sharedInstance()->client->makeNode(array(
        'value' => $value,
        'type' => $this->type,
      ));
      $field_node->save();
      Neo4JDrupal::sharedInstance()->getIndex($this->indexName)->add($field_node, 'value', $value);
    }

    $this->node->relateTo($field_node, $this->referenceName)->save();
  }

}
