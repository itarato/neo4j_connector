<?php


namespace Drupal\neo4j_connector\Factory;

use Drupal\neo4j_connector\Neo4JIndexParam;

class Neo4JIndexParamFactory {

  /**
   * @var string
   */
  private $namespace;

  /**
   * @var string
   */
  private $key;

  public function __construct($namespace, $key) {
    $this->namespace = $namespace;
    $this->key = $key;
  }

  /**
   * @param $id
   * @return \Drupal\neo4j_connector\Neo4JIndexParam
   */
  public function create($id) {
    return new Neo4JIndexParam($this->namespace, $this->key, $id);
  }

}
