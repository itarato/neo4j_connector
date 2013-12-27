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
