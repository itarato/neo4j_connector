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

  public function buildForm(array $form, array &$form_state) {
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Mark all entities to the index'),
    );

    return $form;
  }

  public function validateForm(array &$form, array &$form_state) {
  }

  public function submitForm(array &$form, array &$form_state) {
    neo4j_connector_send_content_to_index();
  }

}
