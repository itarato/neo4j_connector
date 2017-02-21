<?php
/**
 * @file
 */

namespace Drupal\neo4j_connector\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use \Everyman\Neo4j\Exception as Neo4J_Exception;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Path;
use Everyman\Neo4j\Query\Row;
use Everyman\Neo4j\Relationship;

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
    $queryRaw = $form_state->getValue('query');

    try {
      $client = neo4j_connector_get_client();
      $result_set = $client->query($queryRaw);
      $rows = $this->extractQueryResult($result_set);
      drupal_set_message(t('Query: @query', ['@query' => $queryRaw]));
      drupal_set_message(['#markup' => '<pre>' . var_export($rows, TRUE) . '</pre>']);
    }
    catch (Neo4J_Exception $e) {
      drupal_set_message(t('Unable to execute the query.'), 'warning');
      $this->logger(__CLASS__)->warning('Query failed: ' . $queryRaw);
    }
  }

  private function extractQueryResult(\Everyman\Neo4j\Query\ResultSet $result_set) {
    $rows = array();
    for (;$result_set->valid() && ($current = $result_set->current()); $result_set->next()) {
      $rows[] = $this->extractQueryRow($current);
    }
    return $rows;
  }

  private function extractQueryRow(Row $row) {
    $rows = array();
    foreach ($row as $record) {
      if ($record instanceof Node) {
        $rows[] = $record->getProperties();
      }
      elseif ($record instanceof Relationship) {
        $rows[] = $record->getType();
      }
      elseif ($record instanceof Row) {
        $rows[] = $this->extractQueryRow($record);
      }
      elseif ($record instanceof Path) {
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
