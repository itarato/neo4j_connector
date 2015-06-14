<?php
/**
 * @file
 * Field handler classes.
 */

use Everyman\Neo4j\Node;

/**
 * Class Neo4JDrupalRelationEndpointFieldHandler
 * Handles Relation module's endpoint field.
 */
class Neo4JDrupalRelationEndpointFieldHandler extends Neo4JDrupalAbstractFieldHandler {

  /**
   * Implements Neo4JDrupalAbstractFieldHandler::processAllFieldItems().
   */
  public function processAllFieldItems(array $items, stdClass $entity) {
    /** @var Node[] $gnodes */
    $gnodes = array();
    foreach ($items as $idx => $item) {
      $gnode_index = new Neo4JDrupalIndexParam(NEO4J_CONNECTOR_ENTITY_INDEX_PREFIX . $item['entity_type'], 'entity_id', $item['entity_id']);
      $gnode = Neo4JDrupal::sharedInstance()->getGraphNodeOfIndex($gnode_index);
      if (!$gnode) {
        $gnode = neo4j_connector_send_to_index_without_fields($item['entity_type'], $item['entity_id']);
        if (!$gnode) {
          watchdog(__METHOD__, 'Unable to create placeholder graph node of: @entity_type - @id', array('@entity_type' => $item['entity_type'], 'id' => $item['entity_id']), WATCHDOG_ERROR);
          return;
        }
      }
      $gnodes[$idx] = $gnode;
    }

    // Saving the main relation.
    $gnodes[0]->relateTo($gnodes[1], $entity->relation_type)
      ->setProperty(Neo4JDrupal::OWNER, $this->node->getId())
      ->save();
    // Save relationship from the relation entity to the subject entities.
    $this->node->relateTo($gnodes[0], 'RELATION_FROM')
      ->setProperty(Neo4JDrupal::OWNER, $this->node->getId())
      ->save();
    $this->node->relateTo($gnodes[1], 'RELATION_TO')
      ->setProperty(Neo4JDrupal::OWNER, $this->node->getId())
      ->save();
  }

}
