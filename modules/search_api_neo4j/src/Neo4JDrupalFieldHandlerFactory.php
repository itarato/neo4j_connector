<?php
/**
 * @file
 */

namespace Drupal\search_api_neo4j;

use Drupal\field\FieldConfigInterface;
use Drupal\search_api\Item\FieldInterface;
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
  public static function getInstance(Node $graph_node, FieldInterface $field) {
//    $module_name = $field_info->module;
    list(,$field_type) = explode(':', $field->getOriginalType());
    switch ($field_type) {
      case 'entity_reference':
        list($host_item_key, $field_name) = explode('|', $field->getFieldIdentifier());
        list(,$entity_type) = explode(':', $host_item_key);
//        $field_instances = \Drupal::entityManager()->getFieldDefinitions($entity_type, $bundle);
        $field_info = \Drupal\field\Entity\FieldConfig::loadByName($entity_type, $field_name);
        foreach ($field->getValues() as $value) {
          // item <- find item - or create item
          $index_param = Neo4JIndexParamFactory::fromFieldAndValue($field_info, $value);

          // connect items
        }

//        $target_type = $field_info->settings['target_type'];
//        $indexParam = new Neo4JIndexParam(NEO4J_CONNECTOR_ENTITY_INDEX_PREFIX . $target_type, 'entity_id');
//        return new Neo4JDrupalReferenceFieldHandler($graph_node, $field_info, $indexParam, $target_type);
//
//      case 'node_reference':
//        $indexParam = new Neo4JIndexParam(NEO4J_CONNECTOR_ENTITY_INDEX_PREFIX . 'node', 'entity_id');
//        return new Neo4JDrupalReferenceFieldHandler($graph_node, $field_info, $indexParam, 'node');
//
//      case 'user_reference':
//        $indexParam = new Neo4JIndexParam(NEO4J_CONNECTOR_ENTITY_INDEX_PREFIX . 'user', 'entity_id');
//        return new Neo4JDrupalReferenceFieldHandler($graph_node, $field_info, $indexParam, 'user');
//
//      case 'text':
//        return new Neo4JDrupalSimpleValueFieldHandler($graph_node, $field_info, 'text_field_index');
//
//      case 'number':
//        return new Neo4JDrupalSimpleValueFieldHandler($graph_node, $field_info, 'number_field_index');
//
//      case 'taxonomy':
//        $indexParam = new Neo4JIndexParam(NEO4J_CONNECTOR_ENTITY_INDEX_PREFIX . 'taxonomy_term', 'entity_id');
//        return new Neo4JDrupalReferenceFieldHandler($graph_node, $field_info, $indexParam, 'taxonomy_term');
    }

    return NULL;
  }

}
