<?php
/**
 * @file
 */

namespace Drupal\neo4j_connector;

class Neo4JDrupalIndexStat {

  public static function getTotal() {
    return db_query("SELECT COUNT(*) FROM {neo4j_connector_index}")->fetchField();
  }

  public static function getIndexed() {
    return db_query("SELECT COUNT(*) FROM {neo4j_connector_index} WHERE status = :status", array(':status' => NEO4J_CONNECTOR_INDEX_STATUS_INDEXED))->fetchField();
  }

  public static function getNotIndexed() {
    return db_query("SELECT COUNT(*) FROM {neo4j_connector_index} WHERE status != :status", array(':status' => NEO4J_CONNECTOR_INDEX_STATUS_INDEXED))->fetchField();
  }

}
