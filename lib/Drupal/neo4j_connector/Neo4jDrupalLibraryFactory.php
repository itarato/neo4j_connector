<?php
/**
 * @file
 */

namespace Drupal\neo4j_connector;

use Everyman\Neo4j\Client;

class Neo4jDrupalLibraryFactory {

  public function create() {
    $config = \Drupal::config('neo4j_connector.site');
    return new Client($config->get('host'), $config->get('port'));
  }

}
