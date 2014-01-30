<?php
/**
 * @file
 */

namespace Drupal\neo4j_connector;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;

class Neo4JDrupalQueryFactory {

  public static function create(Client $client, $template, array $vars = array()) {
    return new Query($client, $template, $vars);
  }

}
