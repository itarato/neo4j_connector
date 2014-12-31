<?php
/**
 * @file
 */

namespace Drupal\neo4j_entity_index\FieldConnectionHandler;

use Drupal\Core\Entity\EntityInterface;
use Drupal\field\FieldConfigInterface;

/**
 * Class Neo4JDrupalFieldHandlerFactory
 * Factory to create field handler instances.
 */
class Factory {

  /**
   * Create an instance of the appropriate field handler.
   *
   * @todo maybe it could be registered as any other drupal service, eg .. blabla->get("bla.bla")
   *
   * @return AbstractFieldConnectionHandler
   */
  public static function getInstance($field_name, EntityInterface $entity, FieldConfigInterface $field_info = NULL) {
    if ($field_info !== NULL) {
      switch ($field_info->getType()) {
        case 'text_with_summary':
        case 'datetime':
        case 'list_integer':
        case 'list_string':
        case 'integer':
        case 'string':
          return new BasicField($entity, $field_name, $field_info);

        case 'comment':
          return new ReferenceField($entity, $field_name, $field_info, 'comment');

        case 'image':
          return new ReferenceField($entity, $field_name, $field_info, 'file');

        case 'taxonomy_term_reference':
          return new ReferenceField($entity, $field_name, $field_info, 'taxonomy_term');
      }
    }
    else {
      switch ($field_name) {
        case 'created':
        case 'changed':
          return new BasicField($entity, $field_name, $field_info);

        case 'uid':
          return new ReferenceField($entity, $field_name, $field_info, 'user');
      }
    }

    return NULL;
  }

}
