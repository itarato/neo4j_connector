<?php

namespace Drupal\neo4j_connector;


interface INeo4JIndexer extends INeo4JSingleton {

  /**
   * Mark items for indexing.
   */
  public function markAllForIndex();

  /**
   * Index all items.
   */
  public function indexAll();

  /**
   * For indexing fetch data.
   */
  public function getGraphInfoOfIndex();

  /**
   * How many indexed, how many does not.
   */
  public function getStatistics();

}
