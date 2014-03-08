<?php
/**
 * @file
 * Main controller.
 */

namespace Drupal\neo4j_connector\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\neo4j_connector\Neo4JDrupal;

class Neo4JController extends ControllerBase implements ContainerInjectionInterface {

  public function __construct() {
  }

  public function adminSettings() {
    try {
      $client = neo4j_connector_get_client();
      $client->client->getServerInfo();
      drupal_set_message(t('Connection with Neo4J has been established.'));
    }
    catch (\Exception $e) {
      drupal_set_message(t('Cannot connect to the Neo4J database. Please, check the connection details.'), 'warning');
    }

    $index_info = neo4j_connector_index_info();

    if (!$index_info) {
      // Exit - no index.
      return t('No index enabled. Enable neo4j_entity_index or other index modules.');
    }

    $rows = array();
    foreach ($index_info as $key => $index) {
      $rows[] = array(
        $index['label'],
        l(t('Mark all for index'), 'admin/config/neo4j/settings/index/' . $key . '/mark_for_index'),
      );
    }

    $settings_form = \Drupal::formBuilder()->getForm('Drupal\neo4j_connector\Form\Neo4JAdminForm');
    $purge_form = \Drupal::formBuilder()->getForm('Drupal\neo4j_connector\Form\Neo4JPurgeDBForm');
    $reindex_form = \Drupal::formBuilder()->getForm('Drupal\neo4j_connector\Form\Neo4JIndexForm');

    $indexes = array(
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => array(t('Index'), NULL),
    );

    return drupal_render($settings_form) .
      drupal_render($indexes) .
      drupal_render($purge_form) .
      drupal_render($reindex_form);
  }

  public static function create(ContainerInterface $container) {
    return new static();
  }

}
