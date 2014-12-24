<?php
/**
 * @file
 */

namespace Drupal\neo4j_entity_index\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class Neo4JEntityIndexSettingsForm extends ConfigFormBase {

  public function __construct(ConfigFactory $config_factory) {
    parent::__construct($config_factory);
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'neo4j_entity_index_settings';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = $this->config('neo4j_entity_index.global');

    $entity_types = \Drupal::entityManager()->getDefinitions();

    $options = array();
    foreach ($entity_types as $type => $info) {
      $options[$type] = $info->getLabel();
    }

    $default_values = $settings->get('entity_types');
    $form['entity_types'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Entity types'),
      '#options' => $options,
      '#default_value' => $default_values ?: array(),
      '#description' => t('Entity types to index.'),
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
    );

    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('neo4j_entity_index.global')
      ->set('entity_types', $form_state->getValue('entity_types'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
