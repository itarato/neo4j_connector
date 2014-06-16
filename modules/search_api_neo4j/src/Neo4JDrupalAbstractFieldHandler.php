<?php
/**
 * Created by PhpStorm.
 * User: itarato
 * Date: 12/27/13
 * Time: 12:47 AM
 */

namespace Drupal\search_api_neo4j;

use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Entity\FieldConfig;
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
   * Field info.
   *
   * @var FieldConfig
   */
  protected $fieldInfo;

  /**
   * Constructor.
   *
   * @param Node $graph_node
   * @param FieldConfig $field_info
   */
  public function __construct(Node $graph_node, FieldConfig $field_info) {
    $this->node = $graph_node;
    $this->fieldInfo = $field_info;
  }

  /**
   * Goes through of the fields and handle them.
   *
   * @param $entity
   *  Drupal entity object.
   * @param $field_name
   *  Field name.
   */
  public function processFieldData(EntityInterface $entity, $field_name) {
    // @todo properly get languages.

    $items = $entity->{$field_name};
    foreach ($items as $item) {
      if (($value = $item->value)) {
        $this->processFieldItem($item->value);
      }
    }
  }

  /**
   * Placeholder for the individual field processing.
   *
   * @param $value
   * @return mixed
   */
  public abstract function processFieldItem($value);

}
