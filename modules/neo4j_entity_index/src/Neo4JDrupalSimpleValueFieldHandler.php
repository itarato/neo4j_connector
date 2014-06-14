<?php
/**
 * @file
 */

namespace Drupal\neo4j_entity_index;

use Drupal\field\Entity\FieldConfig;
use Drupal\neo4j_connector\Neo4JDrupal;
use Everyman\Neo4j\Label;
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
   * @param $field_info
   *  Field info.
   * @param $indexName
   *  Name of the index.
   */
  public function __construct(Node $graph_node, FieldConfig $field_info, $indexName) {
    parent::__construct($graph_node, $field_info);
    $this->indexName = $indexName;
  }

  /**
   * Implements Neo4JDrupalAbstractFieldHandler::processFieldItem().
   */
  public function processFieldItem($value) {
    $client = neo4j_connector_get_client();
    $index = $client->getIndex($this->indexName);
    $field_node = $index->findOne('value', $value);

    // @todo handle fields properly with labels and such.
    if (!$field_node) {
      $field_node = $client->client->makeNode(array(
        'value' => $value,
        'type' => $this->fieldInfo->module,
      ));
      $field_node->save();

      $labels = array();
      foreach (array($this->fieldInfo->name) as $label_string) {
        $labels[] = new Label($client->client, $label_string);
      }
      $field_node->addLabels($labels);

      $client->getIndex($this->indexName)->add($field_node, 'value', $value);
    }

    $this->node
      ->relateTo($field_node, $this->fieldInfo->name)
      ->setProperty(Neo4JDrupal::OWNER, $this->node->getId())
      ->save();
  }

}
