<?php
/**
 * @file
 */

namespace Drupal\neo4j_connector\Form;

use Drupal\Core\Form\FormBase;
use Drupal\neo4j_connector\Neo4JDrupal;
use \Everyman\Neo4j\Exception as Neo4J_Exception;

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

  public function validateForm(array &$form, array &$form_state) { }

  public function submitForm(array &$form, array &$form_state) {
    try {
      $result_set = Neo4JDrupal::sharedInstance()->query($form_state['values']['query']);
      $rows = $this->extractQueryResult($result_set);
      if (\Drupal::moduleHandler()->moduleExists('devel')) {
        dpm($rows);
      }
    }
    catch (Neo4J_Exception $e) {
      if (\Drupal::moduleHandler()->moduleExists('devel')) {
        dpm($e);
      }
      else {
        drupal_set_message(t('Unable to execute the query.'));
      }
    }
  }

  private function extractQueryResult(\Everyman\Neo4j\Query\ResultSet $result_set) {
    $rows = array();
    for (;$result_set->valid() && ($current = $result_set->current()); $result_set->next()) {
      $rows[] = $this->extractQueryRow($current);
    }
    return $rows;
  }

  private function extractQueryRow(\Everyman\Neo4j\Query\Row $row) {
    $rows = array();
    foreach ($row as $record) {
      if ($record instanceof \Everyman\Neo4j\Node) {
        $rows[] = $record->getProperties();
      }
      elseif ($record instanceof \Everyman\Neo4j\Relationship) {
        $rows[] = $record->getType();
      }
      elseif ($record instanceof \Everyman\Neo4j\Query\Row) {
        $rows[] = $this->extractQueryRow($record);
      }
      else {
        $rows[] = $record;
      }
    }
    return $rows;
  }

}
