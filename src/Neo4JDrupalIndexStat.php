<?php
/**
 * @file
 */

namespace Drupal\neo4j_connector;

class Neo4JDrupalIndexStat {

  public static function getTotal() {
    return \Drupal::database()->query("SELECT COUNT(*) FROM {neo4j_connector_index}")->fetchField();
  }

  public static function getIndexed() {
    return \Drupal::database()->query("SELECT COUNT(*) FROM {neo4j_connector_index} WHERE status = :status", array(':status' => Index::STATUS_INDEXED))->fetchField();
  }

  public static function getNotIndexed() {
    return \Drupal::database()->query("SELECT COUNT(*) FROM {neo4j_connector_index} WHERE status != :status", array(':status' => Index::STATUS_INDEXED))->fetchField();
  }

}
