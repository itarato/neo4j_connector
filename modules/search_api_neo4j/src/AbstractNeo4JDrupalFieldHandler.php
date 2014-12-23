<?php
/**
 * Created by PhpStorm.
 * User: itarato
 * Date: 7/29/14
 * Time: 10:15 AM
 */

namespace Drupal\search_api_neo4j;


use Everyman\Neo4j\PropertyContainer;

abstract class AbstractNeo4JDrupalFieldHandler implements INeo4JDrupalFieldProcessor {

  public function process($items, PropertyContainer $origin_graph_node) {
    foreach ($items as $item) {
      $id = $this->getIdFromItem($item);
      $field_graph_node = $this->findGraphNode($id);
      if (!$field_graph_node) {
        $field_graph_node = $this->createGhostNode($id);
      }

      $client = neo4j_connector_get_client();
      $client->connectOrCreate($origin_graph_node, $this->getIndexParam($id));
    }
  }

  abstract protected function getIdFromItem($item);

}
