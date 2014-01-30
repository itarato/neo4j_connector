<?php
/**
 * @file
 */

namespace Drupal\neo4j_connector;

use Everyman\Neo4j\Index\NodeIndex;
use Everyman\Neo4j\Client;

class Neo4JDrupalIndexFactory {

  public static function create(Client $client, $name, array $config = array()) {
    return new NodeIndex($client, $name, $config);
  }

}
