<?php
/**
 * @file
 */

namespace Drupal\search_api_neo4j;


interface INeo4JDrupalFieldProcessor {

  public function getIndexParam($id);

  public function findGraphNode($id);

  public function createGhostNode($id);

}
