<?php
/**
 * @file
 */

namespace Drupal\search_api_neo4j;

use Drupal\field\FieldConfigInterface;
use Everyman\Neo4j\Node;
use Drupal\neo4j_connector\Neo4JIndexParam;

/**
 * Class Neo4JDrupalFieldHandlerFactory
 * Factory to create field handler instances.
 */
class Neo4JDrupalFieldHandlerFactory {

  /**
   * Create an instance of the appropriate field handler.
   *
   * @todo maybe it could be registered as any other drupal service, eg .. blabla->get("bla.bla")
   *
   * @param $field_info
   *  Field info object.
   * @param Node $graph_node
   *  Graph node.
   * @return Neo4JDrupalAbstractFieldHandler|NULL
   */
  public static function getInstance(FieldConfigInterface $field_info, Node $graph_node) {
    $module_name = $field_info->module;
    switch ($module_name) {
      case 'entity_reference':
        $target_type = $field_info->settings['target_type'];
        $indexParam = new Neo4JIndexParam(NEO4J_CONNECTOR_ENTITY_INDEX_PREFIX . $target_type, 'entity_id');
        return new Neo4JDrupalReferenceFieldHandler($graph_node, $field_info, $indexParam, $target_type);

      case 'node_reference':
        $indexParam = new Neo4JIndexParam(NEO4J_CONNECTOR_ENTITY_INDEX_PREFIX . 'node', 'entity_id');
        return new Neo4JDrupalReferenceFieldHandler($graph_node, $field_info, $indexParam, 'node');

      case 'user_reference':
        $indexParam = new Neo4JIndexParam(NEO4J_CONNECTOR_ENTITY_INDEX_PREFIX . 'user', 'entity_id');
        return new Neo4JDrupalReferenceFieldHandler($graph_node, $field_info, $indexParam, 'user');

      case 'text':
        return new Neo4JDrupalSimpleValueFieldHandler($graph_node, $field_info, 'text_field_index');

      case 'number':
        return new Neo4JDrupalSimpleValueFieldHandler($graph_node, $field_info, 'number_field_index');

      case 'taxonomy':
        $indexParam = new Neo4JIndexParam(NEO4J_CONNECTOR_ENTITY_INDEX_PREFIX . 'taxonomy_term', 'entity_id');
        return new Neo4JDrupalReferenceFieldHandler($graph_node, $field_info, $indexParam, 'taxonomy_term');
    }

    return NULL;
  }

}
