<?php
/**
 * Created by PhpStorm.
 * User: itarato
 * Date: 2/20/14
 * Time: 7:22 AM
 */

namespace Drupal\neo4j_entity_index;


class Neo4JEntityIndexer implements INeo4JIndexer {

  /**
   * Adds all items to the index.
   */
  public function markAllForIndex() {
    $entity_types = Drupal::entityManager()->getDefinitions();

    $operations = array();
    foreach ($entity_types as $type => $info) {
      if (!$info->getKey('id')) {
        // If there is no id entity key than it's probably not for querying. (via entity query)
        // @todo keep an eye on it - maybe there will be a D8 solution sometime.
        continue;
      }
      $operations[] = array('_neo4j_connector_batch_op_mark_for_index', array($type, $info->getKey('id')));
    }

    $batch = array(
      'operations' => $operations,
      'title' => 'Re-indexing entities',
    );

    batch_set($batch);
  }

  /**
   * For indexing fetch data.
   */
  public function getGraphInfoOfIndex() {
    // TODO: Implement getGraphInfoOfIndex() method.
  }

  /**
   * How many indexed, how many does not.
   */
  public function getStatistics() {
    // TODO: Implement getStatistics() method.
  }
}
