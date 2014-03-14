<?php
/**
 * Created by PhpStorm.
 * User: itarato
 * Date: 12/26/13
 * Time: 11:41 AM
 */

namespace Drupal\neo4j_connector\Form;

use Drupal\Core\Form\FormBase;
use Drupal\neo4j_connector\Neo4JDrupalIndexStat;

class Neo4JIndexForm extends FormBase {

  public function getFormId() {
    return 'neo4j_connector_form_reindex';
  }

  public function buildForm(array $form, array &$form_state) {
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Index'),
    );

    $form['stat'] = array(
      '#type' => 'item',
      '#description' => t('@indexed / @total are in the database.', array(
        '@indexed' => Neo4JDrupalIndexStat::getIndexed(),
        '@total' => Neo4JDrupalIndexStat::getTotal(),
      )),
    );

    return $form;
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
