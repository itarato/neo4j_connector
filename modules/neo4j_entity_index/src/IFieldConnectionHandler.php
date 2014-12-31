<?php
/**
 * @file
 */

namespace Drupal\neo4j_entity_index;

use Everyman\Neo4j\Node;

interface IFieldConnectionHandler {

  public function connect(Node $graphNode);

}
