<?php
/**
 * @file
 */

namespace Drupal\neo4j_connector;

use Everyman\Neo4j\Client;

/**
 * Class Neo4JDrupalLibraryFactory
 * @package Drupal\neo4j_connector
 */
class Neo4JDrupalLibraryFactory {

  /**
   * @return \Everyman\Neo4j\Client
   */
  public function create() {
    $config = \Drupal::config('neo4j_connector.site');
    return new Client($config->get('host'), $config->get('port'));
  }

}
