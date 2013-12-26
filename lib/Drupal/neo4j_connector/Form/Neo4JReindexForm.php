<?php
/**
 * Created by PhpStorm.
 * User: itarato
 * Date: 12/26/13
 * Time: 11:41 AM
 */

namespace Drupal\neo4j_connector\Form;


use Drupal\Core\Form\FormBase;

class Neo4JReindexForm extends FormBase {

  public function getFormId() {
    return 'neo4j_connector_form_reindex';
  }

  public function buildForm(array $form, array &$form_state) {
    $index_stat = neo4j_connector_index_get_stat();
    $form['stat'] = array(
      '#markup' => t('@indexed / @total are in the database.', array(
        '@indexed' => $index_stat['indexed'],
        '@total' => $index_stat['total'],
      )),
      '#prefix' => '<div>',
      '#suffix' => '</div>',
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Re-index'),
      // Markup has no nesting. This div makes sure it's the first child and properly aligned.
      '#prefix' => '<div>',
      '#suffix' => '</div>',
    );

    return $form;
  }

  public function validateForm(array &$form, array &$form_state) {

  }

  public function submitForm(array &$form, array &$form_state) {
    $batch = array(
      'operations' => array(
        array('_neo4j_connector_batch_op_reindex', array()),
      ),
      'title' => 'Send index to Neo4J',
    );
    batch_set($batch);
  }

} 