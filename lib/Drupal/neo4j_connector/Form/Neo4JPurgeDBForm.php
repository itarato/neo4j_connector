<?php
/**
 * Created by PhpStorm.
 * User: itarato
 * Date: 12/26/13
 * Time: 11:30 AM
 */

namespace Drupal\neo4j_connector\Form;

use Drupal\Core\Form\FormBase;

class Neo4JPurgeDBForm extends FormBase {

  public function getFormId() {
    return 'neo4j_connector_form_purge_all_graph_db';
  }

  public function buildForm(array $form, array &$form_state) {
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Delete all graph data'),
    );

    return $form;
  }

  public function validateForm(array &$form, array &$form_state) {
  }

  public function submitForm(array &$form, array &$form_state) {
    neo4j_connector_purge_db();
    drupal_set_message(t('All relationships and nodes have been deleted.'));
  }

} 