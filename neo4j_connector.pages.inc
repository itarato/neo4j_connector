<?php

function neo4j_connector_node_graph_info_page($node) {
  $indexParam = new Neo4JDrupalIndexParam(NEO4J_CONNECTOR_NODE_INDEX, 'entity_id', $node->nid);
  return neo4j_connector_graph_node_info_page_content($indexParam);
}

function neo4j_connector_user_graph_info_page($account) {
  $indexParam = new Neo4JDrupalIndexParam(NEO4J_CONNECTOR_USER_INDEX, 'user_id', $account->uid);
  return neo4j_connector_graph_node_info_page_content($indexParam);
}

function neo4j_connector_term_graph_info_page($term) {
  $indexParam = new Neo4JDrupalIndexParam(NEO4J_CONNECTOR_TERM_INDEX, 'tid', $term->tid);
  return neo4j_connector_graph_node_info_page_content($indexParam);
}

/**
 * @param $node
 * @param $indexParam
 * @return null|string
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
  $info[] = '<pre>start n=node:' . $indexParam->name . '(' . $indexParam->key . '="' . $indexParam->value . '") return n;</pre>';

  return theme('item_list', array('items' => $info));
}

function neo4j_connector_console_page() {
  $form = drupal_get_form('neo4j_connector_console_form');

  $out = drupal_render($form);

  return $out;
}

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
