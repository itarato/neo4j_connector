<?php
/**
 * @file
 * Index handler.
 */

namespace Drupal\neo4j_connector;

/**
 * Class Index
 * @package Drupal\neo4j_connector
 */
class Index {

  /**
   * Index statuses.
   */
  // Item is not found in the index.
  const STATUS_NOT_EXIST = -1;
  // Item is indexed properly.
  const STATUS_INDEX = 0;
  // Item is marked to be indexed. Will be indexed on the next CRON or manual call.
  const STATUS_INDEXED = 1;
  // Item is marked for deletion. Will be deleted on the next CRON or manual call.
  const STATUS_DELETE = 2;
  // Item is marked for update. Will be updated on the next CRON or manual call.
  const STATUS_UPDATE = 3;

  // Argument flags for self::addNode().
  const INCLUDE_RELATIONSHIP = TRUE;
  const DO_NOT_INCLUDE_RELATIONSHIP = FALSE;

  /**
   * Mark entity for indexing to Neo4J. Calculates index status automatically:
   *  none -> index
   *  index|delete -> update
   *
   * @param IndexItem $indexItem
   */
  public function mark(IndexItem $indexItem) {
    $index_status = $this->getStatus($indexItem);

    switch ($index_status) {
      case self::STATUS_NOT_EXIST:
        $this->markWithStatus($indexItem, self::STATUS_INDEX);
        break;

      case self::STATUS_DELETE:
      case self::STATUS_INDEXED:
        $this->markWithStatus($indexItem, self::STATUS_UPDATE);
        break;

      case self::STATUS_INDEX:
      case self::STATUS_UPDATE:
        // Nothing to do, it's already waiting in line.
        break;

      default:
        watchdog(__METHOD__, 'Unexpected index status @status for: @index', array(
          '@status' => $index_status,
          '@index' => $indexItem,
        ), WATCHDOG_ERROR);
        break;
    }
  }

  /**
   * Mark entity in the index. Valid statuses are defined as self::STATUS_<SUFFIX>.
   *
   * @param IndexItem $indexItem
   * @param $index_status
   *  Index status code.
   */
  public function markWithStatus(IndexItem $indexItem, $index_status) {
    $result = db_query("
      SELECT *
      FROM {neo4j_connector_index}
      WHERE domain = :domain AND id = :id
    ", array(':domain' => $indexItem->getDomain(), ':id' => $indexItem->getId()))->fetchObject();

    $index_item_record = NULL;
    if (!$result) {
      $index_item_record = new \stdClass();
      $index_item_record->domain = $indexItem->getDomain();
      $index_item_record->id = $indexItem->getId();
    }
    else {
      $index_item_record = $result;
    }

    $index_item_record->changed = $_SERVER['REQUEST_TIME'];
    $index_item_record->status = $index_status;

    if (!$result) {
      drupal_write_record('neo4j_connector_index', $index_item_record);
    }
    else {
      drupal_write_record('neo4j_connector_index', $index_item_record, array('domain', 'id'));
    }
  }

  /**
   * Returns the index status of an entity.
   *
   * @param IndexItem $indexItem
   * @return int
   *  Index status code. Examples:
   *    STATUS_NOT_EXIST
   *    STATUS_INDEX
   *    STATUS_INDEXED
   *    STATUS_DELETE
   *    STATUS_UPDATE
   */
  protected function getStatus(IndexItem $indexItem) {
    $result = db_query("
      SELECT status
      FROM {neo4j_connector_index}
      WHERE domain = :domain AND id = :id
    ", array(':domain' => $indexItem->getDomain(), ':id' => $indexItem->getId()))->fetchField();

    return $result === FALSE ? self::STATUS_NOT_EXIST : $result;
  }


  /**
   * Delete entity from the index. At this point the entity must not have the graph node in the db.
   *
   * @param IndexItem $indexItem
   */
  public function delete(IndexItem $indexItem) {
    db_query("
      DELETE FROM {neo4j_connector_index}
      WHERE domain = :domain AND id = :id
    ", array(':domain' => $indexItem->getDomain(), ':id' => $indexItem->getId()));
  }


  /**
   * Delete all index. At this point nothing should be in the graph db.
   */
  public function deleteAll() {
    db_query("DELETE FROM {neo4j_connector_index}");
    watchdog(__METHOD__, 'Index has been purged.', array(), WATCHDOG_INFO);
  }

  /**
   * Processing elements from the index table: indexing or deleting.
   *
   * @param int|NULL $limit
   *  Number of items to process.
   */
  public function processIndex($limit = NULL) {
    if (!$limit) {
      $limit = \Drupal::config('neo4j_connector.site')->get('index_process_limit');
    }

    $records = db_query_range("
      SELECT *
      FROM {neo4j_connector_index}
      WHERE status != :status
      ORDER BY changed ASC
    ", 0, $limit, array(':status' => self::STATUS_INDEXED))->fetchAll();

    foreach ($records as $record) {
      $indexItem = new IndexItem($record->domain, $record->id);

      // It's necessary to re-load the status: when indexing a content it might have a reference that is not indexed.
      // In that case we need to pre-index the referenced item (without fields) in order to be able to reference that.
      // That action will change the referenced item status to UPDATED. Since here the query is already in PHP
      // we have to make sure we fetch the up-to-date status.
      $status = $this->getStatus($indexItem);

      switch ($status) {
        case self::STATUS_INDEX:
          $graph_node = $this->addNode($indexItem);
          break;

        case self::STATUS_UPDATE:
          $graph_node = $this->updateNode($indexItem);
          break;

        case self::STATUS_DELETE:
          $this->deleteNode($indexItem);
          break;

        default:
          watchdog(__METHOD__, 'Unexpected index status @status for index: @index', array(
            '@status' => $status,
            '@index' => $indexItem,
          ), WATCHDOG_ERROR);
          break;
      }

      if (isset($graph_node)) {
        $this->markWithStatus($indexItem, self::STATUS_INDEXED);
      }
    }
  }

  public function addNode(IndexItem $indexItem, $include_relationships = self::INCLUDE_RELATIONSHIP) {
    list($index_param, $properties, $labels) = $this->getNodeInfo($indexItem);
    $graph_node = Neo4JDrupal::sharedInstance()->addNode($properties, $labels, $index_param);

    if ($include_relationships) {
      \Drupal::moduleHandler()->invokeAll('neo4j_connector_graph_node_update', array($graph_node, $indexItem));
    }

    return $graph_node;
  }

  /**
   * @param IndexItem $indexItem
   * @return \Everyman\Neo4j\Node|FALSE
   */
  public function addGhostNode(IndexItem $indexItem) {
    $node = $this->addNode($indexItem, self::DO_NOT_INCLUDE_RELATIONSHIP);

    if ($node) {
      $this->markWithStatus($indexItem, self::STATUS_UPDATE);
      return $node;
    }

    watchdog(__METHOD__, 'Graph node could not be created. Index: @index', array('@index' => $indexItem->getDomain()), WATCHDOG_ERROR);
    return FALSE;
  }

  public function updateNode(IndexItem $indexItem) {
    list($index_param, $properties, $labels) = $this->getNodeInfo($indexItem);
    $graph_node = Neo4JDrupal::sharedInstance()->updateNode($properties, $labels, $index_param);
    \Drupal::moduleHandler()->invokeAll('neo4j_connector_graph_node_update', array($graph_node, $indexItem));
    return $graph_node;
  }

  public function deleteNode(IndexItem $indexItem) {
    $index_info = neo4j_connector_index_info();
    $callback = $index_info[$indexItem->getDomain()]['index param callback'];
    $indexParam = $callback($indexItem);
    neo4j_connector_get_client()->deleteNode($indexParam);
    $this->delete($indexItem);
  }

  /**
   */
  public function getNodeInfo(IndexItem $indexItem) {
    $index_info = neo4j_connector_index_info();
    $callback = $index_info[$indexItem->getDomain()]['index param callback'];
    $index_param = $callback($indexItem);

    $properties = array();
    \Drupal::moduleHandler()->alter('neo4j_connector_properties', $properties, $indexItem);

    $labels = array();
    \Drupal::moduleHandler()->alter('neo4j_connector_labels', $labels, $indexItem);

    return array($index_param, $properties, $labels);
  }

}
