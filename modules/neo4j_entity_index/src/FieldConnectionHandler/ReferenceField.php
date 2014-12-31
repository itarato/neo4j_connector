<?php
/**
 * @file
 */

namespace Drupal\neo4j_entity_index\FieldConnectionHandler;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\neo4j_connector\Index;
use Drupal\neo4j_connector\IndexItem;
use Drupal\neo4j_connector\Neo4JIndexParam;
use Exception;

class ReferenceField extends AbstractFieldConnectionHandler {

  /**
   * @var string
   */
  protected $entityType;

  public function __construct(EntityInterface $entity, $field_name, FieldConfigInterface $fieldConfig = NULL, $entityType) {
    parent::__construct($entity, $field_name, $fieldConfig);
    $this->entityType = $entityType;
  }

  public function getFieldValueNode($field_value) {
    $index = $this->getFieldValueIndex($field_value);
    $graphNode = neo4j_connector_get_client()->getGraphNodeOfIndex($index);
    if ($graphNode) {
      return $graphNode;
    }

    $indexItem = new IndexItem('entity', $this->entityType . ':' . $field_value);
    return neo4j_connector_get_index()->addNode($indexItem, Index::DO_NOT_INCLUDE_RELATIONSHIP);
  }

  /**
   * @param \Drupal\Core\Field\FieldItemInterface $fieldItem
   * @return mixed
   */
  public function extractFieldValue(FieldItemInterface $fieldItem) {
    try {
      return $fieldItem->get('target_id')->getValue();
    }
    catch (Exception $e) {
      return NULL;
    }
  }

  public function getFieldValueIndex($field_value) {
    return new Neo4JIndexParam('entity_index_' . $this->entityType, 'entity_id', $field_value);
  }

}
