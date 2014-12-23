<?php
/**
 */

namespace Drupal\search_api_neo4j;

use Drupal\neo4j_connector\Neo4JIndexParam;

class Neo4JDrupalTaxonomyReferenceFieldHandler extends AbstractNeo4JDrupalFieldHandler {

  protected $entityTypeID;

  public function __construct() {
    $this->entityTypeID = 'taxonomy_term';
  }

  public function getIndexParam($id) {
    return new Neo4JIndexParam('entity', $this->entityTypeID, $id);
  }

  public function findGraphNode($id) {
    $client = neo4j_connector_get_client();
    return $client->getGraphNodeOfIndex($this->getIndexParam($id));
  }

  public function createGhostNode($id) {
    $client = neo4j_connector_get_client();
    $client->addNode(array(), array(), $this->getIndexParam($id));
  }

}
