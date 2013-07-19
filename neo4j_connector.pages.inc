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
  $indexParam = new Neo4JDrupalIndexParam(NEO4J_CONNECTOR_NODE_INDEX, 'entity_id', $node->nid);
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
  $indexParam = new Neo4JDrupalIndexParam(NEO4J_CONNECTOR_USER_INDEX, 'user_id', $account->uid);
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
  $indexParam = new Neo4JDrupalIndexParam(NEO4J_CONNECTOR_TERM_INDEX, 'tid', $term->tid);
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

  $delete_all_form = drupal_get_form('neo4j_connector_form_purge_all_graph_db');
  $out .= drupal_render($delete_all_form);

  $regenerate_nodes_form = drupal_get_form('neo4j_connector_form_regenerate_nodes');
  $out .= drupal_render($regenerate_nodes_form);

  $regenerate_users_form = drupal_get_form('neo4j_connector_form_regenerate_users');
  $out .= drupal_render($regenerate_users_form);

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

  drupal_set_message(t('All relationships and nodes have been deleted.'));
}

/**
 * Regenerate all nodes form.
 */
function neo4j_connector_form_regenerate_nodes($form) {
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Regenerate nodes'),
  );

  return $form;
}

/**
 * Submit handler of the renegerate all nodes form.
 *
 * @see neo4j_connector_form_regenerate_nodes()
 */
function neo4j_connector_form_regenerate_nodes_submit($form, $form_submit) {
  $nids = db_query("
    SELECT nid
    FROM {node}
    ORDER BY nid ASC
  ")->fetchCol();

  $count = count($nids);
  $queue = DrupalQueue::get('neo4j_connector_node_regenerate');
  while (count($nids) > 0) {
    $queue->createItem(array_splice($nids, 0, 100));
  }

  drupal_set_message(t('@count nodes are added to the regenerate queue. Don\'t forget to run cron to apply the changes.', array('@count' => $count)));
}

/**
 * Regenerate users form.
 */
function neo4j_connector_form_regenerate_users($form) {
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Regenerate users'),
  );

  return $form;
}

/**
 * Submit handler for the regenerate users form.
 *
 * @see neo4j_connector_form_regenerate_users()
 */
function neo4j_connector_form_regenerate_users_submit($form, $form_submit) {
  $uids = db_query("
    SELECT uid
    FROM {users}
    ORDER BY uid ASC
  ")->fetchCol();

  $count = count($uids);
  $queue = DrupalQueue::get('neo4j_connector_user_regenerate');
  while (count($uids) > 0) {
    $queue->createItem(array_splice($uids, 0, 100));
  }

  drupal_set_message(t('@count users are added to the regenerate queue. Don\'t forget to run cron to apply the changes.', array('@count' => $count)));
}
