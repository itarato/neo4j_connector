<?php
/**
 * @file
 */

namespace Drupal\neo4j_connector\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use \Everyman\Neo4j\Exception as Neo4J_Exception;

class Neo4JConsoleForm extends FormBase {

  public function getFormId() {
    return 'neo4j_connector_console';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
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

  public function validateForm(array &$form, FormStateInterface $form_state) { }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      $client = neo4j_connector_get_client();
      $result_set = $client->query($form_state['values']['query']);
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
      elseif ($record instanceof \Everyman\Neo4j\Path) {
        foreach ($record->getNodes() as $node) {
          $rows[] = $node->getProperties();
        }
      }
      else {
        $rows[] = $record;
      }
    }
    return $rows;
  }

}
