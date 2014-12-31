<?php
/**
 * @file
 */

namespace Drupal\neo4j_entity_index;

use Drupal\Core\Entity\EntityInterface;
use Drupal\field\FieldConfigInterface;
use Drupal\neo4j_entity_index\FieldConnectionHandler\AbstractFieldConnectionHandler;
use Drupal\neo4j_entity_index\FieldConnectionHandler\BasicField;
use Drupal\neo4j_entity_index\FieldConnectionHandler\ReferenceField;

/**
 * Class Neo4JDrupalFieldHandlerFactory
 * Factory to create field handler instances.
 */
class FieldConnectionHandlerFactory {

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
          return new BasicField($entity, $field_name, $field_info);

        case 'comment':
          return new ReferenceField($entity, $field_name, $field_info, 'comment');

        case 'image':
          return new ReferenceField($entity, $field_name, $field_info, 'file');

        case 'taxonomy_term_reference':
          return new ReferenceField($entity, $field_name, $field_info, 'taxonomy_term');
      }
    }

    return NULL;
  }

}
