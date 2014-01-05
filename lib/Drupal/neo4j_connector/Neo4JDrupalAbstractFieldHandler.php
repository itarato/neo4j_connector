<?php
/**
 * Created by PhpStorm.
 * User: itarato
 * Date: 12/27/13
 * Time: 12:47 AM
 */

namespace Drupal\neo4j_connector;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\Language;
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
   * @param $field_name
   *  Field name.
   */
  public function processFieldData(EntityInterface $entity, $field_name) {
    // @todo properly get languages.
    $items = $entity->getTranslation(Language::LANGCODE_NOT_SPECIFIED)->get($field_name);
    foreach ($items as $item) {
      $this->processFieldItem($item->value);
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
