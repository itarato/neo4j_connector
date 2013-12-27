<?php
/**
 * Created by PhpStorm.
 * User: itarato
 * Date: 12/27/13
 * Time: 12:47 AM
 */

namespace Drupal\neo4j_connector;
use Everyman\Neo4j\Node;

/**
 * Class Neo4JDrupalAbstractFieldHandler
 */
abstract class Neo4JDrupalAbstractFieldHandler {

  /**
   * Graph node that the fields will be attached to.
   *
   * @var Node
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
