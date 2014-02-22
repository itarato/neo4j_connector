<?php
/**
 * @file
 */

namespace Drupal\neo4j_connector;

interface INeo4JSingleton {

  /**
   * @return INeo4JSingleton
   */
  public static function getInstance();

} 