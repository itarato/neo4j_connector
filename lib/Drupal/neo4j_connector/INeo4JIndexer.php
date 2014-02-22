<?php

namespace Drupal\neo4j_connector;


interface INeo4JIndexer {

  /**
   * Adds all items to the index.
   */
  public function markAllForIndex();

  /**
   * For indexing fetch data.
   */
  public function getGraphInfoOfIndex();

  /**
   * How many indexed, how many does not.
   */
  public function getStatistics();

} 