<?php
/**
 * @file
 */

namespace Drupal\search_api_neo4j;


use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\neo4j_connector\Neo4JIndexParam;

class Neo4JIndexParamFactory {

  /**
   * @param \Drupal\search_api\Item\ItemInterface $index_item
   * @return Neo4JIndexParam|null
   */
  public static function from(\Drupal\search_api\Item\ItemInterface $index_item) {
    $original_object = $index_item->getOriginalObject();
    if ($original_object instanceof EntityInterface) {
      return new Neo4JIndexParam('entity', $original_object->getEntityTypeId(), $original_object->id());
    }

    return NULL;
  }

  public static function fromFieldConfigAndValue(FieldConfig $field_config, $value) {
    if ($field_config->getType() == 'taxonomy_term_reference') {
      return new Neo4JIndexParam('entity', 'taxonomy_term', $value);
    }

    return NULL;
  }

} 