<?php
/**
 * @file
 * Drush operations.
 */

/**
 * Implements hook_drush_command().
 */
function neo4j_connector_drush_command() {
  $commands = array();

  $commands['neo4j-connector-purge-db'] = array(
    'description' => 'Removes all the data and relationship from the Neo4J DB.',
    'examples' => array(
      'drush neo-purge' => 'Delete everything.',
    ),
    'aliases' => array('neo-purge', 'ncpd'),
  );

  $commands['neo4j-connector-index-statistics'] = array(
    'description' => 'Get statistics about the index.',
    'examples' => array(
      'drush neo-idx-stat' => 'Get the index statistics.',
    ),
    'aliases' => array('neo-idx-stat', 'ncis'),
  );

  $commands['neo4j-connector-send-to-index'] = array(
    'description' => 'Send content to the index.',
    'examples' => array(
      'drush neo-to-idx' => 'Send content to the index.',
    ),
    'aliases' => array('neo-to-idx', 'ncsti'),
  );

  $commands['neo4j-connector-index'] = array(
    'description' => 'Index content.',
    'arguments' => array(
      'limit' => 'Maximum number of items to index. Default is the batch default setting (100).',
    ),
    'examples' => array(
      'drush neo-index' => 'Index content',
      'drush neo-index --limit=10' => 'Index 10 pieces of content',
    ),
    'aliases' => array('neo-index', 'nci'),
  );

  return $commands;
}

function drush_neo4j_connector_purge_db() {
  composer_manager_register_autoloader();
  neo4j_connector_purge_db();
  drush_print('Neo4J database has been purged.');
}

function drush_neo4j_connector_index_statistics() {
  $stat = neo4j_connector_index_get_stat();

  drush_print('Index statistics:');
  foreach ($stat as $name => $value) {
    drush_print('> ' . $name . ': ' . $value);
  }
}

function drush_neo4j_connector_index() {
  $limit = drush_get_option('limit', NULL);
  composer_manager_register_autoloader();
  neo4j_connector_index_process_index($limit);
}

function drush_neo4j_connector_send_to_index() {
  neo4j_connector_send_content_to_index();
  drush_backend_batch_process();
}
