<?php

namespace Drupal\neo4j_connector\Plugin\search_api\processor;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Annotation\SearchApiProcessor;
use Drupal\search_api\Processor\FieldsProcessorPluginBase;

/**
 * Class MappingProcessor
 * @package Drupal\neo4j_connector\Plugin\search_api\processor
 *
 * @SearchApiProcessor(
 *   id="neo4j_connector_mapping_processor",
 *   label=@Translation("Mapping processor"),
 *   description=@Translation("Defines relationship information between items"),
 *   stages={
 *    "preprocess_index"=0
 *   }
 * )
 */
class MappingProcessor extends FieldsProcessorPluginBase {

  const ID = 'neo4j_connector_mapping_processor';

  const KEY_FIELD_MAPPING = 'field_mapping';

  public function defaultConfiguration() {
    return [self::KEY_FIELD_MAPPING => []];
  }

  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = ['' => t('- no mapping -')];
    foreach (\Drupal::entityTypeManager()->getDefinitions() as $entityDefinition) {
      if (!($entityDefinition instanceof ContentEntityType)) continue;
      $options[$entityDefinition->id()] = $entityDefinition->getLabel();
    }

    $mapping = $this->getConfiguration()[self::KEY_FIELD_MAPPING];

    $form[self::KEY_FIELD_MAPPING]['#tree'] = TRUE;
    foreach ($this->getIndex()->getFields() as $field) {
      $form[self::KEY_FIELD_MAPPING][$field->getPropertyPath()] = [
        '#title' => $field->getLabel(),
        '#type' => 'select',
        '#options' => $options,
        '#default_value' => @$mapping[$field->getPropertyPath()],
      ];
    }

    return $form;
  }

}
