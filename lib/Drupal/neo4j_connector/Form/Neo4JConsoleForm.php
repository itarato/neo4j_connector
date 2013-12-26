<?php
/**
 * Created by PhpStorm.
 * User: itarato
 * Date: 12/26/13
 * Time: 12:42 PM
 */

namespace Drupal\neo4j_connector\Form;

use Drupal\Core\Form\FormBase;

class Neo4JConsoleForm extends FormBase {

  public function getFormId() {
    return 'neo4j_connector_console';
  }

  public function buildForm(array $form, array &$form_state) {
    $form['query'] = array(
      '#type' => 'textarea',
      '#title' => t('Query'),
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Execute'),
    );

    return $form;
  }

  public function validateForm(array &$form, array &$form_state) {

  }

  public function submitForm(array &$form, array &$form_state) {
    try {
      $result_set = Neo4JDrupal::sharedInstance()->query($form_state['values']['query']);
      $rows = array();
      foreach ($result_set as $result) {
        foreach ($result as $row) {
          $rows[] = $row->getId();
        }
      }
      dpm($rows);
    }
    catch (\Everyman\Neo4j\Exception $e) {
      dpm($e);
    }
  }

} 