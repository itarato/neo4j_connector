<?php
/**
 * @file
 * Hooks and API function.
 */

use Drupal\neo4j_connector\IndexItem;

/**
 * Implements hook_neo4j_connector_properties_alter().
 */
function hook_neo4j_connector_properties_alter(array &$properties, IndexItem $indexItem) {
}

/**
 * Implements hook_neo4j_connector_labels_alter().
 */
function hook_neo4j_connector_labels_alter(array &$labels, IndexItem $indexItem) {
}

/**
 * Implements hook_neo4j_connector_graph_node_update().
 */
function hook_neo4j_connector_graph_node_update(\Everyman\Neo4j\Node $graph_node, IndexItem $indexItem) {
}
