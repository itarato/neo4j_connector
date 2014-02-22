<?php
/**
 * Created by PhpStorm.
 * User: itarato
 * Date: 12/26/13
 * Time: 11:38 AM
 */

namespace Drupal\neo4j_connector\Form;


use Drupal\Core\Form\FormBase;

class Neo4JMarkForIndexForm extends FormBase {

  public function getFormId() {
    return 'neo4j_connector_form_mark_for_index';
  }

  public function buildForm(array $form, array &$form_state, array $neo4j_connector_index = NULL) {
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

  public function validateForm(array &$form, array &$form_state) {
  }

  public function submitForm(array &$form, array &$form_state) {
    $index = neo4j_connector_index_load($form_state['values']['index']);
    $indexer = $index['class']::getInstance();
    $indexer->markAllForIndex();
  }

}
