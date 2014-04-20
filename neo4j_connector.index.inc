<?php
/**
 * @file
 * Indexing mechanism.
 */

use Drupal\neo4j_connector\Neo4JDrupal;
use Drupal\neo4j_connector\IndexItem;

/**
 * Index statuses.
 */
// Item is not found in the index.
define('NEO4J_CONNECTOR_INDEX_STATUS_NOT_EXIST', -1);
// Item is indexed properly.
define('NEO4J_CONNECTOR_INDEX_STATUS_INDEX', 0);
// Item is marked to be indexed. Will be indexed on the next CRON or manual call.
define('NEO4J_CONNECTOR_INDEX_STATUS_INDEXED', 1);
// Item is marked for deletion. Will be deleted on the next CRON or manual call.
define('NEO4J_CONNECTOR_INDEX_STATUS_DELETE', 2);
// Item is marked for update. Will be updated on the next CRON or manual call.
define('NEO4J_CONNECTOR_INDEX_STATUS_UPDATE', 3);

define('NEO4J_CONNECTOR_INCLUDE_RELATIONSHIP', TRUE);
define('NEO4J_CONNECTOR_DO_NOT_INCLUDE_RELATIONSHIP', FALSE);

/**
 * Mark entity for indexing to Neo4J.
 *
 * @param IndexItem $indexItem
 */
function neo4j_connector_index_mark_for_index(IndexItem $indexItem) {
  $index_status = neo4j_connector_index_get_index_status($indexItem);

  switch ($index_status) {
    case NEO4J_CONNECTOR_INDEX_STATUS_NOT_EXIST:
      neo4j_connector_index_mark_for_index_with_status($indexItem, NEO4J_CONNECTOR_INDEX_STATUS_INDEX);
      break;

    case NEO4J_CONNECTOR_INDEX_STATUS_DELETE:
    case NEO4J_CONNECTOR_INDEX_STATUS_INDEXED:
      neo4j_connector_index_mark_for_index_with_status($indexItem, NEO4J_CONNECTOR_INDEX_STATUS_UPDATE);
      break;

    case NEO4J_CONNECTOR_INDEX_STATUS_INDEX:
    case NEO4J_CONNECTOR_INDEX_STATUS_UPDATE:
      // Nothing to do, it's already waiting in line.
      break;

    default:
      watchdog(__FUNCTION__, 'Unexpected index status @status for: @index', array(
        '@status' => $index_status,
        '@index' => $indexItem,
      ), WATCHDOG_ERROR);
      break;
  }
}

/**
 * Returns the index status of an entity.
 *
 * @param IndexItem $indexItem
 * @return int
 *  Index status code. Examples:
 *    NEO4J_CONNECTOR_INDEX_STATUS_NOT_EXIST
 *    NEO4J_CONNECTOR_INDEX_STATUS_INDEX
 *    NEO4J_CONNECTOR_INDEX_STATUS_INDEXED
 *    NEO4J_CONNECTOR_INDEX_STATUS_DELETE
 *    NEO4J_CONNECTOR_INDEX_STATUS_UPDATE
 */
function neo4j_connector_index_get_index_status(IndexItem $indexItem) {
  $result = db_query("
    SELECT status
    FROM {neo4j_connector_index}
    WHERE domain = :domain AND id = :id
  ", array(':domain' => $indexItem->getDomain(), ':id' => $indexItem->getId()))->fetchField();

  return $result === FALSE ? NEO4J_CONNECTOR_INDEX_STATUS_NOT_EXIST : $result;
}

/**
 * Delete entity from the index. At this point the entity must not have the graph node in the db.
 *
 * @param IndexItem $indexItem
 */
function neo4j_connector_index_delete(IndexItem $indexItem) {
  db_query("
    DELETE FROM {neo4j_connector_index}
    WHERE domain = :domain AND id = :id
  ", array(':domain' => $indexItem->getDomain(), ':id' => $indexItem->getId()));
}

/**
 * Delete all index. At this point nothing should be in the graph db.
 */
function neo4j_connector_index_delete_all() {
  db_query("DELETE FROM {neo4j_connector_index}");
  watchdog(__FUNCTION__, 'Index has been purged.', array(), WATCHDOG_INFO);
}

/**
 * Mark entity in the index. Valid statuses are defined as NEO4J_CONNECTOR_INDEX_STATUS_<SUFFIX>.
 *
 * @param IndexItem $indexItem
 * @param $index_status
 *  Index status code.
 */
function neo4j_connector_index_mark_for_index_with_status(IndexItem $indexItem, $index_status) {
  $result = db_query("
    SELECT *
    FROM {neo4j_connector_index}
    WHERE domain = :domain AND id = :id
  ", array(':domain' => $indexItem->getDomain(), ':id' => $indexItem->getId()))->fetchObject();

  $index_item_record = NULL;
  if (!$result) {
    $index_item_record = new stdClass();
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
 * Processing elements from the index table: indexing or deleting.
 *
 * @param int|NULL $limit
 *  Number of items to process.
 */
function neo4j_connector_index_process_index($limit = NULL) {
  if (!$limit) {
    $limit = \Drupal::config('neo4j_connector.site')->get('index_process_limit');
  }

  $records = db_query_range("
    SELECT *
    FROM {neo4j_connector_index}
    WHERE status != :status
    ORDER BY changed ASC
  ", 0, $limit, array(':status' => NEO4J_CONNECTOR_INDEX_STATUS_INDEXED))->fetchAll();

  foreach ($records as $record) {
    $indexItem = new IndexItem($record->domain, $record->id);

    // It's necessary to re-load the status: when indexing a content it might have a reference that is not indexed.
    // In that case we need to pre-index the referenced item (without fields) in order to be able to reference that.
    // That action will change the referenced item status to UPDATED. Since here the query is already in PHP
    // we have to make sure we fetch the up-to-date status.
    $status = neo4j_connector_index_get_index_status($indexItem);

    switch ($status) {
      case NEO4J_CONNECTOR_INDEX_STATUS_INDEX:
        $graph_node = neo4j_connector_index_add_node($indexItem);
        break;

      case NEO4J_CONNECTOR_INDEX_STATUS_UPDATE:
        $graph_node = neo4j_connector_index_update_node($indexItem);
        break;

      case NEO4J_CONNECTOR_INDEX_STATUS_DELETE:
        neo4j_connector_index_delete_node($indexItem);
        break;

      default:
        watchdog(__FUNCTION__, 'Unexpected index status @status for entity: @entity_type-@entity_id', array(
          '@status' => $status,
          '@domain' => $record->domain,
          '@id' => $record->id,
        ), WATCHDOG_ERROR);
        break;
    }

    if (isset($graph_node)) {
      $record->remote_id = $graph_node->getId();
      $record->status = NEO4J_CONNECTOR_INDEX_STATUS_INDEXED;
      $record->changed = $_SERVER['REQUEST_TIME'];
      drupal_write_record('neo4j_connector_index', $record, array('domain', 'id'));
    }
  }
}

function neo4j_connector_index_add_node(IndexItem $indexItem, $include_relationships = NEO4J_CONNECTOR_INCLUDE_RELATIONSHIP) {
  list($index_param, $properties, $labels) = neo4j_connector_index_get_node_info_from_index($indexItem);
  $graph_node = Neo4JDrupal::sharedInstance()->addNode($properties, $labels, $index_param);

  if ($include_relationships) {
    \Drupal::moduleHandler()->invokeAll('neo4j_connector_graph_node_update', array($graph_node, $indexItem));
  }

  return $graph_node;
}

function neo4j_connector_index_update_node(IndexItem $indexItem) {
  list($index_param, $properties, $labels) = neo4j_connector_index_get_node_info_from_index($indexItem);
  $graph_node = Neo4JDrupal::sharedInstance()->updateNode($properties, $labels, $index_param);
  \Drupal::moduleHandler()->invokeAll('neo4j_connector_graph_node_update', array($graph_node, $indexItem));
  return $graph_node;
}

function neo4j_connector_index_delete_node(IndexItem $indexItem) {
  $index_info = neo4j_connector_index_info();
  $callback = $index_info[$indexItem->getDomain()]['index param callback'];
  $indexParam = $callback($indexItem);
  neo4j_connector_get_client()->deleteNode($indexParam);
  neo4j_connector_index_delete($indexItem);
}

/**
 */
function neo4j_connector_index_get_node_info_from_index(IndexItem $indexItem) {
  $index_info = neo4j_connector_index_info();
  $callback = $index_info[$indexItem->getDomain()]['index param callback'];
  $index_param = $callback($indexItem);

  $properties = array();
  \Drupal::moduleHandler()->alter('neo4j_connector_properties', $properties, $indexItem);

  $labels = array();
  \Drupal::moduleHandler()->alter('neo4j_connector_labels', $labels, $indexItem);

  return array($index_param, $properties, $labels);
}

/**
 * @param IndexItem $indexItem
 * @return \Everyman\Neo4j\Node|FALSE
 */
function neo4j_connector_index_add_ghost_node(IndexItem $indexItem) {
  $node = neo4j_connector_index_add_node($indexItem, NEO4J_CONNECTOR_DO_NOT_INCLUDE_RELATIONSHIP);

  if ($node) {
    neo4j_connector_index_mark_for_index_with_status($indexItem, NEO4J_CONNECTOR_INDEX_STATUS_UPDATE);
    return $node;
  }

  watchdog(__FUNCTION__, 'Graph node could not be created. Index: @index', array('@index' => $indexItem->getDomain()), WATCHDOG_ERROR);
  return FALSE;
}
