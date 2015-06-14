<?php
/**
 * @file
 */

use Everyman\Neo4j\Node;

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

    // Either process the fields individually.
    /** @var array[] $items */
    foreach ($items as $item) {
      $this->processFieldItem($item, $entity);
    }
    // Or all in one.
    $this->processAllFieldItems($items, $entity);
  }

  /**
   * Placeholder for the individual field processing.
   *
   * @param array $item
   *  Array of field item values.
   * @param stdClass $entity
   *  Entity object.
   */
  public function processFieldItem(array $item, stdClass $entity) { }

  /**
   * Placeholder for the fields (all in one array) processing.
   *
   * @param array $items
   *  Array of field items.
   * @param stdClass $entity
   *  Entity object.
   */
  public function processAllFieldItems(array $items, stdClass $entity) { }

}
