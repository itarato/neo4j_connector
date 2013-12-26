<?php
/**
 * @file
 * Neo4J module page content and forms.
 */

/**
 * Page callback of the node graph tab page.
 *
 * @param $node
 *  Node object
 * @return string
 */
function neo4j_connector_node_graph_info_page($node) {
  return neo4j_connector_graph_info_page_content('node', $node->nid);
}

/**
 * Page callback of the user graph tab page.
 *
 * @param $account
 *  User object.
 * @return string
 */
function neo4j_connector_user_graph_info_page($account) {
  return neo4j_connector_graph_info_page_content('user', $account->uid);
}

/**
 * Page callback og the term graph tab page.
 *
 * @param $term
 *  Term object.
 * @return string
 */
function neo4j_connector_term_graph_info_page($term) {
  return neo4j_connector_graph_info_page_content('taxonomy_term', $term->tid);
}

/**
 * Helper to generate a graph info about an entity.
 *
 * @param $entity_type
 * @param $id
 * @return null|string
 */
function neo4j_connector_graph_info_page_content($entity_type, $id) {
  $graph_node = Neo4JDrupal::sharedInstance()->getGraphNodeOfEntity($entity_type, $id);

  if (!$graph_node) {
    return t('Cannot find associated graph node.');
  }

  $info = array();
  $info[] = t('Graph node ID: <strong>@nodeid</strong>', array('@nodeid' => $graph_node->getId()));
  $data_browser_link = 'http://localhost:7474/webadmin/#/data/search/' . $graph_node->getId() . '/';
  $info[] = t('Data browser link') . ': ' . l($data_browser_link, $data_browser_link);
  $info[] = '<pre>START n=node:' . NEO4J_CONNECTOR_ENTITY_INDEX_PREFIX . $entity_type . '(entity_id="' . $id . '") RETURN n;</pre>';

  return theme('item_list', array('items' => $info));
}
