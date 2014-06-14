<?php
/**
 * @file
 */

namespace Drupal\neo4j_connector\Form;

use Drupal\Core\Form\FormBase;

/**
 * Class Neo4JPurgeDBForm
 * @package Drupal\neo4j_connector\Form
 */
class Neo4JPurgeDBForm extends FormBase {

  public function getFormId() {
    return 'neo4j_connector_form_purge_all_graph_db';
  }

  public function buildForm(array $form, array &$form_state) {
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Delete all graph data'),
    );

    $form['desc'] = array(
      '#type' => 'item',
      '#description' => t('Warning - this action will delete all information in the graph database. Cannot be undone.'),
    );

    return $form;
  }

  public function submitForm(array &$form, array &$form_state) {
    neo4j_connector_purge_db();
    drupal_set_message(t('All relationships and nodes have been deleted.'));
  }

}
