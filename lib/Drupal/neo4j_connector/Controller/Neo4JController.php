<?php
/**
 * Created by PhpStorm.
 * User: itarato
 * Date: 12/25/13
 * Time: 12:50 PM
 */

namespace Drupal\neo4j_connector\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Neo4JController extends ControllerBase implements ContainerInjectionInterface {

  public function __construct() {
  }

  public function adminSettings() {
//    try {
//      Neo4JDrupal::sharedInstance()->client->getServerInfo();
//      drupal_set_message(t('Connection with Neo4J has been established.'));
//    }
//    catch (Exception $e) {
//      drupal_set_message(t('Cannot connect to the Neo4J database. Please, check the connection details.'), 'warning');
//    }

    $settings_form = \Drupal::formBuilder()->getForm('Drupal\neo4j_connector\Form\Neo4JAdminForm');

    return drupal_render($settings_form);
  }

  public static function create(ContainerInterface $container) {
    return new static();
  }

}
