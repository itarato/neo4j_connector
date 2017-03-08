<?php
/**
 * @file
 * Main controller.
 */

namespace Drupal\neo4j_connector\Controller;

use Drupal;
use Drupal\Core\Controller\ControllerBase;

class Neo4JController extends ControllerBase {

  public function adminSettings() {
    try {
      $client = neo4j_connector_get_client();
      $client->client->getServerInfo();
      drupal_set_message(t('Connection with Neo4J has been established.'));
    }
    catch (\Exception $e) {
      drupal_set_message(t('Cannot connect to the Neo4J database. Please, check the connection details.'), 'warning');
    }

    $settings_form = Drupal::formBuilder()->getForm('Drupal\neo4j_connector\Form\Neo4JAdminForm');
    return $settings_form;
  }

}
