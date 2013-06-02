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
