<?php
/**
 * @file
 * Main controller.
 */

namespace Drupal\neo4j_connector\Controller;

use Drupal;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Neo4JController extends ControllerBase {

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

    $settings_form = Drupal::formBuilder()->getForm('Drupal\neo4j_connector\Form\Neo4JAdminForm');
    return $settings_form;
  }

  public function indexSettings() {
    $index_info = neo4j_connector_index_info();

    if (!$index_info) {
      // Exit - no index.
      return array(
        '#markup' => t('No index enabled. Enable neo4j_entity_index or other index modules.'),
      );
    }

    $rows = array();
    foreach ($index_info as $key => $index) {
      $mark_link = Drupal::l(t('Mark all for index'), new Url('neo4j_connector.index.purge', ['neo4j_connector_index' => $key]));
      $setting_path = Drupal::l(t('settings'), new Url($index['settings path']));
      $rows[] = array(
        $index['label'],
        $mark_link,
        isset($index['settings path']) ? $setting_path : '',
      );
    }

    $purge_form = Drupal::formBuilder()->getForm('Drupal\neo4j_connector\Form\Neo4JPurgeDBForm');
    $reindex_form = Drupal::formBuilder()->getForm('Drupal\neo4j_connector\Form\Neo4JIndexForm');

    $indexes = array(
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => array(t('Index'), NULL, t('Settings')),
    );

    return array(
      $indexes,
      $purge_form,
      $reindex_form,
    );
  }

  public static function create(ContainerInterface $container) {
    return new static();
  }

}
