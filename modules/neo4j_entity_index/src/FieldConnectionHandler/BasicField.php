<?php
/**
 * @file
 */

namespace Drupal\neo4j_entity_index\FieldConnectionHandler;

use Drupal;
use Drupal\Core\Field\FieldItemInterface;

class BasicField extends AbstractFieldConnectionHandler {

  public function getFieldValueNode($field_value) {
    $index = $this->getFieldValueIndex($field_value);
    $fieldValueNode = neo4j_connector_get_client()->getGraphNodeOfIndex($index);

    if (!$fieldValueNode) {
      $properties = $this->getFieldValueProperties($field_value);
      $labels = $this->getFieldValueLabels($field_value);
      try {
        $fieldValueNode = neo4j_connector_get_client()->updateNode($properties, $labels, $index);
      } catch (\Exception $e) {
        Drupal::logger(__CLASS__)
          ->error('Node was not created: ' . $e->getMessage());
      }
    }

    return $fieldValueNode;
  }

  /**
   * @param $field_value
   * @return array
   */
  public function getFieldValueProperties($field_value) {
    return [
      'value' => $field_value,
      'field_name' => $this->field_name,
    ];
  }

  /**
   * @param $field_value
   * @return array
   */
  public function getFieldValueLabels($field_value) {
    return [
      'field',
      $this->field_name,
    ];
  }

  /**
   * @param \Drupal\Core\Field\FieldItemInterface $fieldItem
   * @return mixed
   */
  public function extractFieldValue(FieldItemInterface $fieldItem) {
    return $fieldItem->getString();
  }

}
