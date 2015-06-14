<?php
/**
 * @file
 */

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Transport\Curl;

class Neo4JDrupalDefaultAdapterFactory implements Neo4JDrupalAdapterFactoryInterface {

  /**
   * {@inheritdoc}
   */
  public function get() {
    $host = variable_get(NEO4J_CONNECTOR_VAR_HOST, 'localhost');
    $port = variable_get(NEO4J_CONNECTOR_VAR_PORT, '7474');
    $transport = new Curl($host, $port);

    $username = variable_get(NEO4J_CONNECTOR_VAR_USERNAME);
    $password = variable_get(NEO4J_CONNECTOR_VAR_PASSWORD);
    if ($username && $password) {
      $transport->setAuth($username, $password);
    }

    $client = new Client($transport);

    $neo4jDrupal = new Neo4JDrupal($client, 'Everyman\Neo4j\Index\NodeIndex', 'Everyman\Neo4j\Cypher\Query');

    return $neo4jDrupal;
  }

}
