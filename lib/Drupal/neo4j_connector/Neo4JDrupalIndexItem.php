<?php
/**
 * @file
 */

namespace Drupal\neo4j_connector;


class Neo4JDrupalIndexItem {

  public $labels;

  public $properties;

  public $indexParam;

  public function __construct(array $properties, array $labels, Neo4JDrupalIndexParam $index_param) {
    $this->labels = $labels;
    $this->properties = $properties;
    $this->indexParam = $index_param;
  }

  /**
   * @param $domain
   * @param $id
   *
   * @return Neo4JDrupalIndexItem
   */
  public static function createFromIndexDomainAndID($domain, $id) {
//    \Drupal::moduleHandler()->invokeAll('neo4j_connector_', $args);
//    $index_param = new Neo4JDrupalIndexParam(NEO4J_CONNECTOR_ENTITY_INDEX_PREFIX . $entity->entityType(), 'entity_id', $entity->id());

    $properties = array();
    \Drupal::moduleHandler()->alter('neo4j_connector_entity_properties', $properties, $domain, $id);

    $labels = array();
    \Drupal::moduleHandler()->alter('neo4j_connector_entity_labels', $labels, $domain, $id);

    return new Neo4JDrupalIndexItem($properties, $labels, $index_param);
  }

}
