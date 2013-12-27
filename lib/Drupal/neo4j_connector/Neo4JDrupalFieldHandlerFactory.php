<?php
/**
 * Created by PhpStorm.
 * User: itarato
 * Date: 12/27/13
 * Time: 12:46 AM
 */

namespace Drupal\neo4j_connector;

use Everyman\Neo4j\Node;

/**
 * Class Neo4JDrupalFieldHandlerFactory
 * Factory to create field handler instances.
 */
class Neo4JDrupalFieldHandlerFactory {

  /**
   * Create an instance of the appropriate field handler.
   *
   * @param $module_name
   *  Name of the module that defines the field.
   * @param Node $graph_node
   *  Graph node.
   * @return Neo4JDrupalAbstractFieldHandler|NULL
   */
  public static function getInstance($module_name, Node $graph_node) {
    switch ($module_name) {

      case 'entityreference':
        $indexParam = new Neo4JDrupalIndexParam(NEO4J_CONNECTOR_ENTITY_INDEX_PREFIX . 'node', 'entity_id');
        return new Neo4JDrupalReferenceFieldHandler($graph_node, $module_name, 'NODE_REFERENCE', $indexParam, 'target_id', 'node');

      case 'node_reference':
        $indexParam = new Neo4JDrupalIndexParam(NEO4J_CONNECTOR_ENTITY_INDEX_PREFIX . 'node', 'entity_id');
        return new Neo4JDrupalReferenceFieldHandler($graph_node, $module_name, 'NODE_REFERENCE', $indexParam, 'nid', 'node');

      case 'user_reference':
        $indexParam = new Neo4JDrupalIndexParam(NEO4J_CONNECTOR_ENTITY_INDEX_PREFIX . 'user', 'entity_id');
        return new Neo4JDrupalReferenceFieldHandler($graph_node, $module_name, 'USER_REFERENCE', $indexParam, 'uid', 'user');

      case 'text':
        return new Neo4JDrupalSimpleValueFieldHandler($graph_node, $module_name, 'HAS_TEXT', 'text_field_index', 'value');

      case 'number':
        return new Neo4JDrupalSimpleValueFieldHandler($graph_node, $module_name, 'HAS_NUMBER', 'number_field_index', 'value');

      case 'taxonomy':
        $indexParam = new Neo4JDrupalIndexParam(NEO4J_CONNECTOR_ENTITY_INDEX_PREFIX . 'taxonomy_term', 'entity_id');
        return new Neo4JDrupalReferenceFieldHandler($graph_node, $module_name, 'TAXONOMY_TERM_REFERENCE', $indexParam, 'tid', 'taxonomy_term');
    }

    return NULL;
  }

}
