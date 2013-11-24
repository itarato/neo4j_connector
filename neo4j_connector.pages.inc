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
  $indexParam = new Neo4JDrupalIndexParam(NEO4J_CONNECTOR_ENTITY_INDEX_PREFIX . 'node', 'entity_id', $node->nid);
  return neo4j_connector_graph_node_info_page_content($indexParam);
}

/**
 * Page callback of the user graph tab page.
 *
 * @param $account
 *  User object.
 * @return string
 */
function neo4j_connector_user_graph_info_page($account) {
  $indexParam = new Neo4JDrupalIndexParam(NEO4J_CONNECTOR_ENTITY_INDEX_PREFIX . 'user', 'entity_id', $account->uid);
  return neo4j_connector_graph_node_info_page_content($indexParam);
}

/**
 * Page callback og the term graph tab page.
 *
 * @param $term
 *  Term object.
 * @return string
 */
function neo4j_connector_term_graph_info_page($term) {
  $indexParam = new Neo4JDrupalIndexParam(NEO4J_CONNECTOR_ENTITY_INDEX_PREFIX . 'taxonomy_term', 'entity_id', $term->tid);
  return neo4j_connector_graph_node_info_page_content($indexParam);
}

/**
 * Helper to generate a graph info about an entity.
 *
 * @param $indexParam
 *  Index that identifies the graph node.
 * @return string
 */
function neo4j_connector_graph_node_info_page_content(Neo4JDrupalIndexParam $indexParam) {
  $graph_node = $indexParam->getNode();

  if (!$graph_node) {
    return t('Cannot find associated graph node. Terribly sorry.');
  }

  $info = array();
  $info[] = t('Graph node ID: <strong>@nodeid</strong>', array('@nodeid' => $graph_node->getId()));
  $data_browser_link = 'http://localhost:7474/webadmin/#/data/search/' . $graph_node->getId() . '/';
  $info[] = t('Data browser link') . ': ' . l($data_browser_link, $data_browser_link);
  $info[] = '<pre>START n=node:' . $indexParam->name . '(' . $indexParam->key . '="' . $indexParam->value . '") RETURN n;</pre>';

  return theme('item_list', array('items' => $info));
}

/**
 * Page callback of the console page.
 */
function neo4j_connector_console_page() {
  $form = drupal_get_form('neo4j_connector_console_form');

  $out = drupal_render($form);

  return $out;
}

/**
 * Console form.
 */
function neo4j_connector_console_form($form, $form_state) {
  $form['query'] = array(
    '#type' => 'textarea',
    '#title' => t('Query'),
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Execute'),
  );

  return $form;
}

/**
 * Submit handler of the console form.
 *
 * @see neo4j_connector_console_form()
 */
function neo4j_connector_console_form_submit($form, $form_state) {
  try {
    $result_set = Neo4JDrupal::sharedInstance()->query($form_state['values']['query']);
    $rows = array();
    foreach ($result_set as $result) {
      foreach ($result as $row) {
        $rows[] = $row->getId();
      }
    }
    dpm($rows);
  }
  catch (\Everyman\Neo4j\Exception $e) {
    dpm($e);
  }
}

/**
 * Page callback of the admin settings page.
 */
function neo4j_connector_admin_settings_page() {
  $out = '';

  $settings_form = drupal_get_form('neo4j_connector_admin_settings_form');
  $out .= drupal_render($settings_form);

  $delete_all_form = drupal_get_form('neo4j_connector_form_purge_all_graph_db');
  $out .= drupal_render($delete_all_form);

  $mark_for_index_form = drupal_get_form('neo4j_connector_form_mark_for_index');
  $out .= drupal_render($mark_for_index_form);

  $reindex_form = drupal_get_form('neo4j_connector_form_reindex');
  $out .= drupal_render($reindex_form);

  return $out;
}

/**
 * Delete all graph node form.
 */
function neo4j_connector_form_purge_all_graph_db($form) {
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Delete all graph data'),
  );

  return $form;
}

/**
 * Submit handler of the delete all graph node form.
 *
 * @see neo4j_connector_form_purge_all_graph_db()
 */
function neo4j_connector_form_purge_all_graph_db_submit($form, $form_state) {
  // Delete all relationships.
  Neo4JDrupal::sharedInstance()->query("
    START n = node(*)
    MATCH (n)-[r]->()
    DELETE r
  ");

  // Delete all nodes.
  Neo4JDrupal::sharedInstance()->query("
    START n = node(*)
    DELETE n
  ");

  neo4j_connector_index_delete_all();

  drupal_set_message(t('All relationships and nodes have been deleted.'));
}

/**
 * Admin settings form.
 *
 * @param $form
 *  Form array.
 * @param $form_state
 *  Form state array.
 * @return mixed
 *  Form array.
 */
function neo4j_connector_admin_settings_form($form, $form_state) {
  try {
    Neo4JDrupal::sharedInstance()->client->getServerInfo();
    drupal_set_message(t('Connection with Neo4J has been established.'));
  }
  catch (Exception $e) {
    drupal_set_message(t('Cannot connect to the Neo4J database. Please, check the connection details.'), 'warning');
  }

  $form['neo4j_connector_host'] = array(
    '#title' => t('Server host'),
    '#type' => 'textfield',
    '#default_value' => variable_get('neo4j_connector_host', 'localhost'),
  );

  $form['neo4j_connector_port'] = array(
    '#title' => t('Server port'),
    '#type' => 'textfield',
    '#default_value' => variable_get('neo4j_connector_port', '7474'),
  );

  return system_settings_form($form);
}

function neo4j_connector_form_mark_for_index($form, $form_state) {
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Mark all entities to the index'),
  );

  return $form;
}

function neo4j_connector_form_mark_for_index_submit($form, $form_state) {
  $entity_types = entity_get_info();

  $operations = array();
  foreach ($entity_types as $type => $info) {
    $operations[] = array('_neo4j_connector_batch_op_mark_for_index', array($type));
  }

  $batch = array(
    'operations' => $operations,
    'title' => 'Re-indexing entities',
    'file' => 'neo4j_connector.pages.inc',
  );

  batch_set($batch);
}

function neo4j_connector_form_reindex($form, $form_state) {
  $index_stat = neo4j_connector_index_get_stat();
  $form['stat'] = array(
    '#markup' => t('@indexed / @total are in the database.', array(
      '@indexed' => $index_stat['indexed'],
      '@total' => $index_stat['total'],
    )),
    '#prefix' => '<div>',
    '#suffix' => '</div>',
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Re-index'),
  );

  return $form;
}

function neo4j_connector_form_reindex_submit($form, $form_state) {
  $batch = array(
    'operations' => array(
      array('_neo4j_connector_batch_op_reindex', array()),
    ),
    'title' => 'Send index to Neo4J',
  );
  batch_set($batch);
}
