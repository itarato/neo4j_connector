<?php
/**
 * @file
 * Views filter handler.
 */

/**
 * Class neo4j_connector_raw_filter_handler
 * Custom neo4j raw query filter handler.
 */
class neo4j_connector_raw_filter_handler extends views_handler_filter {

  /**
   * Implements views_handler_filter::query().
   */
  public function query() {
    $neo4j_query = $this->options['neo4j_query'];

    if (!$neo4j_query) {
      return;
    }

    if (($neo4j_argument = $this->options['neo4j_argument']) != '') {
      if (!($arg_nid = (int) arg($neo4j_argument))) {
        return;
      }

      $index = new Neo4JDrupalIndexParam(NEO4J_CONNECTOR_ENTITY_INDEX_PREFIX . 'node', 'entity_id', $arg_nid);
      $context_graph_node = Neo4JDrupal::sharedInstance()->getGraphNodeOfEntity('node', $arg_nid);
      if ($index) {
        $neo4j_query = 'START n=node(' . $context_graph_node->getId() . ') ' . $neo4j_query;
      }
    }

    $result_set = Neo4JDrupal::sharedInstance()->query($neo4j_query);

    $entity_ids = array();
    foreach ($result_set as $result) {
      foreach ($result as $graph_node) {
        if ($entity_id = $graph_node->getProperty('entity_id')) {
          $entity_ids[] = $entity_id;
        }
      }
    }

    if ($entity_ids) {
      $this->query->add_where($this->options['group'], 'nid', $entity_ids, 'IN');
    }
    else {
      $this->query->add_where($this->options['group'], 'nid', 0);
    }
  }

  /**
   * Implements parent::options_form().
   */
  public function options_form(&$form, &$form_state) {
    $form['neo4j_query'] = array(
      '#type' => 'textfield',
      '#title' => t('Query'),
      '#default_value' => $this->options['neo4j_query'],
    );

    $form['neo4j_argument'] = array(
      '#type' => 'select',
      '#title' => t('Contextual argument - args(?)'),
      '#options' => array_merge(array('' => t('-- Select argument number --')), array_combine(range(0, 10), range(0, 10))),
      '#default_value' => $this->options['neo4j_argument'],
    );

    parent::options_form($form, $form_state);
  }

  /**
   * Implements parent::option_definition().
   */
  public function option_definition() {
    $options = parent::option_definition();
    $options['neo4j_query'] = array(
      'default' => '',
    );
    $options['neo4j_argument'] = array(
      'default' => '',
    );
    return $options;
  }

}
