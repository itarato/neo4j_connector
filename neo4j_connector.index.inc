<?php
/**
 * @file
 * Indexing mechanism.
 */

define('NEO4J_CONNECTOR_INDEX_STATUS_NOT_EXIST', -1);
define('NEO4J_CONNECTOR_INDEX_STATUS_INDEX', 0);
define('NEO4J_CONNECTOR_INDEX_STATUS_INDEXED', 1);
define('NEO4J_CONNECTOR_INDEX_STATUS_DELETE', 2);
define('NEO4J_CONNECTOR_INDEX_STATUS_UPDATE', 3);

function neo4j_connector_index_update_in_index($entity_type, $entity_id) {
  $index_status = neo4j_connector_index_get_index_status($entity_type, $entity_id);

  switch ($index_status) {
    case NEO4J_CONNECTOR_INDEX_STATUS_NOT_EXIST:
      _neo4j_connector_index_mark($entity_type, $entity_id, NEO4J_CONNECTOR_INDEX_STATUS_INDEX);
      break;

    case NEO4J_CONNECTOR_INDEX_STATUS_DELETE:
    case NEO4J_CONNECTOR_INDEX_STATUS_INDEXED:
      _neo4j_connector_index_mark($entity_type, $entity_id, NEO4J_CONNECTOR_INDEX_STATUS_UPDATE);
      break;

    case NEO4J_CONNECTOR_INDEX_STATUS_INDEX:
    case NEO4J_CONNECTOR_INDEX_STATUS_UPDATE:
      // Nothing to do, it's already waiting in line.
      break;

    default:
      watchdog(__FUNCTION__, 'Unexpected index status @status for entity: @entity_type-@entity_id', array(
        '@status' => $index_status,
        '@entity_type' => $entity_type,
        '@entity_id' => $entity_id,
      ), WATCHDOG_ERROR);
      break;
  }
}

function neo4j_connector_index_remove_from_index($entity_type, $entity_id) {
  $index_status = neo4j_connector_index_get_index_status($entity_type, $entity_id);

  switch ($index_status) {
    case NEO4J_CONNECTOR_INDEX_STATUS_NOT_EXIST:
    case NEO4J_CONNECTOR_INDEX_STATUS_DELETE:
      // Do nothing, it does not exist already.
      break;

    default:
      _neo4j_connector_index_mark($entity_type, $entity_id, NEO4J_CONNECTOR_INDEX_STATUS_DELETE);
      break;
  }
}

function neo4j_connector_index_get_index_status($entity_type, $entity_id) {
  $result = db_query("
    SELECT status
    FROM {neo4j_connector_index}
    WHERE entity_type = :entity_type AND entity_id = :entity_id
  ", array(':entity_type' => $entity_type, ':entity_id' => $entity_id))->fetchField();

  return $result === FALSE ? NEO4J_CONNECTOR_INDEX_STATUS_NOT_EXIST : $result;
}

function neo4j_connector_index_delete($entity_type, $entity_id) {
  db_query("
    DELETE FROM {neo4j_connector_index}
    WHERE entity_type = :entity_type AND entity_id = :entity_id
  ", array(':entity_type' => $entity_type, ':entity_id' => $entity_id));
}

function neo4j_connector_index_delete_all() {
  db_query("DELETE FROM {neo4j_connector_index}");
}

function _neo4j_connector_index_mark($entity_type, $entity_id, $index_status) {
  $result = db_query("
    SELECT *
    FROM {neo4j_connector_index}
    WHERE entity_type = :entity_type AND entity_id = :entity_id
  ", array(':entity_type' => $entity_type, ':entity_id' => $entity_id))->fetchObject();

  $index_item = NULL;
  if (!$result) {
    $index_item = new stdClass();
    $index_item->entity_type = $entity_type;
    $index_item->entity_id = $entity_id;
  }
  else {
    $index_item = $result;
  }

  $index_item->changed = $_SERVER['REQUEST_TIME'];
  $index_item->status = $index_status;

  if (!$result) {
    drupal_write_record('neo4j_connector_index', $index_item);
  }
  else {
    drupal_write_record('neo4j_connector_index', $index_item, array('entity_type', 'entity_id'));
  }
}

function neo4j_connector_index_process_index($limit = NULL) {
  if (!$limit) {
    $limit = variable_get('neo4j_connector_index_process_limit', 100);
  }

  $records = db_query_range("
    SELECT *
    FROM {neo4j_connector_index}
    WHERE status != :status
    ORDER BY changed ASC
  ", 0, $limit, array(':status' => NEO4J_CONNECTOR_INDEX_STATUS_INDEXED))->fetchAll();

  foreach ($records as $record) {
    $entity = entity_load_single($record->entity_type, $record->entity_id);

    switch ($record->status) {
      case NEO4J_CONNECTOR_INDEX_STATUS_INDEX:
        $graph_node = neo4j_connector_add_entity_to_neo4j($entity, $record->entity_type);
        break;

      case NEO4J_CONNECTOR_INDEX_STATUS_UPDATE:
        $indexParam = new Neo4JDrupalIndexParam(NEO4J_CONNECTOR_ENTITY_INDEX_PREFIX . $record->entity_type, 'entity_id', $record->entity_id);

        $properties = array();
        drupal_alter('neo4j_connector_entity_properties', $properties, $entity, $record->entity_type);

        $graph_node = Neo4JDrupal::sharedInstance()->updateEntity($entity, $record->entity_type, $properties, $indexParam);
        break;

      case NEO4J_CONNECTOR_INDEX_STATUS_DELETE:
        $indexParam = new Neo4JDrupalIndexParam(NEO4J_CONNECTOR_ENTITY_INDEX_PREFIX . $record->entity_type, 'entity_id', $record->entity_id);
        Neo4JDrupal::sharedInstance()->deleteEntity($entity, $record->entity_type, $indexParam);

        neo4j_connector_index_delete($record->entity_type, $record->entity_id);
        break;

      default:
        watchdog(__FUNCTION__, 'Unexpected index status @status for entity: @entity_type-@entity_id', array(
          '@status' => $record->status,
          '@entity_type' => $record->entity_type,
          '@entity_id' => $record->entity_id,
        ), WATCHDOG_ERROR);
        break;
    }

    if (isset($graph_node)) {
      $record->remote_id = $graph_node->getId();
      $record->status = NEO4J_CONNECTOR_INDEX_STATUS_INDEXED;
      $record->changed = $_SERVER['REQUEST_TIME'];
      drupal_write_record('neo4j_connector_index', $record, array('entity_type', 'entity_id'));
    }
  }
}

function neo4j_connector_index_get_stat() {
  $stat = array();

  $stat['total'] = db_query("SELECT COUNT(*) FROM {neo4j_connector_index}")->fetchField();

  $stat['indexed'] = db_query("
    SELECT COUNT(*)
    FROM {neo4j_connector_index}
    WHERE status = :status
  ", array(':status' => NEO4J_CONNECTOR_INDEX_STATUS_INDEXED))->fetchField();

  $stat['non-indexed'] = $stat['total'] - $stat['indexed'];

  return $stat;
}