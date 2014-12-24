<?php
/**
 * @file
 */

namespace Drupal\neo4j_connector\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class Neo4JMarkForIndexForm extends FormBase {

  public function getFormId() {
    return 'neo4j_connector_form_mark_for_index';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $neo4j_connector_index = NULL) {
    $form['#title'] = t('Mark all %index for index', array('%index' => $neo4j_connector_index));

    $form['index'] = array(
      '#type' => 'value',
      '#value' => $neo4j_connector_index,
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Proceed'),
    );

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $index = neo4j_connector_index_info_load($form_state['values']['index']);
    call_user_func($index['index marker callback']);
    $form_state['redirect'] = 'admin/config/neo4j/index';
  }

}
