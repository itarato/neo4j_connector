<?php

function neo4j_connector_node_graph_info_page($node) {
  $propCont = Neo4JDrupal::sharedInstance()->getNodeOfEntity($node);

  if (!$propCont) {
    drupal_set_message(t('Cannot find associated graph node. Terribly sorry.', 'warning'));
    return;
  }

  $info = array();

  $info[] = t('Node ID: <strong>@nodeid</strong>', array('@nodeid' => $propCont->getId()));

  $data_browser_link = 'http://localhost:7474/webadmin/#/data/search/' . $propCont->getId() . '/';
  $info[] = t('Data browser link') . ': ' . l($data_browser_link, $data_browser_link);

  $info[] = '<pre>start n=node:entity_index(entity_id="' . $node->nid . '") return n;</pre>';

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
