<?php
/**
 * @file
 */

namespace Drupal\neo4j_entity_index\FieldConnectionHandler;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\field\FieldConfigInterface;
use Drupal\neo4j_connector\Neo4JDrupal;
use Drupal\neo4j_connector\Neo4JIndexParam;
use Everyman\Neo4j\Node;
use Exception;

/**
 * Class AbstractFieldConnectionHandler
 */
abstract class AbstractFieldConnectionHandler {

  /**
   * @var EntityInterface
   */
  protected $entity;

  /**
   * @var string
   */
  protected $field_name;

  /**
   * @var \Drupal\field\FieldConfigInterface
   */
  protected $fieldConfig;

  public function __construct(EntityInterface $entity, $field_name, FieldConfigInterface $fieldConfig = NULL) {
    $this->entity = $entity;
    $this->field_name = $field_name;
    $this->fieldConfig = $fieldConfig;
  }

  /**
   * @param Node $graphNode
   * @throws \Exception
   */
  public function connect(Node $graphNode) {
    /** @var FieldItemInterface $field_item */
    foreach ($this->getFieldValues() as $field_item) {
      $field_value = $this->extractFieldValue($field_item);
      $fieldValueNode = $this->getFieldValueNode($field_value);

      if (!$fieldValueNode) {
        \Drupal::logger(__CLASS__)->info('No filed value graph node to connect with.');
        continue;
      }

      $graphNode
        ->relateTo($fieldValueNode, $this->field_name)
        ->setProperty(Neo4JDrupal::OWNER, $graphNode->getId())
        ->save();
    }
  }

  /**
   * @return FieldItemList
   */
  public function getFieldValues() {
    return $this->entity->{$this->field_name};
  }

  /**
   * @param \Drupal\Core\Field\FieldItemInterface $fieldItem
   * @return mixed
   */
  abstract public function extractFieldValue(FieldItemInterface $fieldItem);

  /**
   * @param $field_value
   * @return Neo4JIndexParam
   */
  public function getFieldValueIndex($field_value) {
    return new Neo4JIndexParam('field', 'value_hash', md5($field_value));
  }

  abstract public function getFieldValueNode($field_value);

}
