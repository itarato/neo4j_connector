<?php
/**
 * @file
 * Hooks and API function.
 */

/**
 * Chance to add properties to a graph node.
 *
 * @param $property
 *  Existing property array to populate.
 * @param $entity
 *  Entity object.
 * @param $entity_type
 *  Type of the entity.
 */
function hook_neo4j_connector_entity_properties_alter(&$property, $entity, $entity_type) {
  // Example code:
  if ($entity_type == 'my_entity') {
    $property['foo'] = $entity->bar;
  }
}
