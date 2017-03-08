<?php

namespace Drupal\neo4j_connector\Plugin\search_api\backend;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\neo4j_connector\Neo4JIndexParam;
use Drupal\neo4j_connector\Plugin\search_api\processor\MappingProcessor;
use Drupal\search_api\Annotation\SearchApiBackend;
use Drupal\search_api\Backend\BackendPluginBase;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\SearchApiException;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Relationship;
use Exception;

/**
 * Class Neo4JDatabase
 * @package Drupal\neo4j_connector\Plugin\search_api\backend
 *
 * @SearchApiBackend(
 *   id="neo4j_connector_search_api_backend",
 *   label=@Translation("Neo4j Graph Backend"),
 *   description=@Translation("Indexes items as graph nodes."),
 * )
 */
class Neo4JDatabase extends BackendPluginBase implements PluginFormInterface {

  use LoggerChannelTrait;
  use PluginFormTrait;

  /**
   * Indexes the specified items.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The search index for which items should be indexed.
   * @param ItemInterface[] $items
   *   An array of items to be indexed, keyed by their item IDs.
   *
   * @return string[]
   *   The IDs of all items that were successfully indexed.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if indexing was prevented by a fundamental configuration error.
   */
  public function indexItems(IndexInterface $index, array $items) {
    $indexedKeys = [];
    foreach ($items as $item) {
      $indexedKeys[] = $this->indexItem($item);
    }

    return array_filter($indexedKeys);
  }

  /**
   * Deletes the specified items from the index.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index from which items should be deleted.
   * @param string[] $item_ids
   *   The IDs of the deleted items.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if an error occurred while trying to delete the items.
   */
  public function deleteItems(IndexInterface $index, array $item_ids) {
    foreach ($item_ids as $item_id) {
      $indexParam = neo4j_connector_entity_index_factory()->create($item_id);
      neo4j_connector_get_client()->deleteNode($indexParam);
    }
  }

  /**
   * Deletes all the items from the index.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index for which items should be deleted.
   * @param string|null $datasource_id
   *   (optional) If given, only delete items from the datasource with the
   *   given ID.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if an error occurred while trying to delete indexed items.
   */
  public function deleteAllIndexItems(IndexInterface $index, $datasource_id = NULL) {
    try {
      neo4j_connector_purge_db();
    }
    catch (Exception $e) {
      throw new SearchApiException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * Executes a search on this server.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The query to execute.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if an error prevented the search from completing.
   */
  public function search(QueryInterface $query) {
    throw new SearchApiException('Search feature is not implemented on graph databases via SearchAPI.');
  }

  /**
   * Form constructor.
   *
   * Plugin forms are embedded in other forms. In order to know where the plugin
   * form is located in the parent form, #parents and #array_parents must be
   * known, but these are not available during the initial build phase. In order
   * to have these properties available when building the plugin form's
   * elements, let this method return a form element that has a #process
   * callback and build the rest of the form in the callback. By the time the
   * callback is executed, the element's #parents and #array_parents properties
   * will have been set by the form API. For more documentation on #parents and
   * #array_parents, see \Drupal\Core\Render\Element\FormElement.
   *
   * @param array $form
   *   An associative array containing the initial structure of the plugin form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form. Calling code should pass on a subform
   *   state created through
   *   \Drupal\Core\Form\SubformState::createForSubform().
   *
   * @return array
   *   The form structure.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  public function defaultConfiguration() {
    return [];
  }

  /**
   * @param $item
   * @return string
   */
  protected function indexItem(ItemInterface $item) {
    $id = $item->getId();
    $indexParam = neo4j_connector_entity_index_factory()->create($id);

    $graphNode = $this->createGraphNode($item, $indexParam);

    neo4j_connector_get_client()->deleteRelationships($indexParam, [], Relationship::DirectionOut);
    $this->makeRelationships($item, $graphNode);

    return $graphNode ? $id : NULL;
  }

  /**
   * @param ItemInterface $item
   * @param Neo4JIndexParam $indexParam
   * @return Node
   */
  protected function createGraphNode(ItemInterface $item, Neo4JIndexParam $indexParam) {
    $properties = [];
    foreach ($item->getFields() as $field) {
      if (!($values = $field->getValues())) continue;

      $prop_id = $field->getPropertyPath();
      $fieldName = $field->getFieldIdentifier();
      $fieldDef = FieldStorageConfig::loadByName($item->getDatasource()
        ->getEntityTypeId(), $fieldName);
      $properties[$prop_id] = $fieldDef && $fieldDef->getCardinality() != 1 ? $values : $values[0];
    }

    return neo4j_connector_get_client()->updateNode($properties, [$item->getDatasourceId()], $indexParam);
  }

  /**
   * @param string $refEntityType
   * @param \Drupal\search_api\Item\FieldInterface $field
   * @param \Everyman\Neo4j\Node $graphNode
   */
  protected function createGraphRelationship($refEntityType, FieldInterface $field, Node $graphNode) {
    foreach ($field->getValues() as $value) {
      $refEntity = \Drupal::entityTypeManager()
        ->getStorage($refEntityType)
        ->load($value);
      $regLangCode = $refEntity->language()->getId();
      $entity_key = "entity:$refEntityType/$value:$regLangCode";
      $indexParam = neo4j_connector_entity_index_factory()->create($entity_key);

      if (!($rhsNode = neo4j_connector_get_client()->getGraphNodeOfIndex($indexParam))) {
        $rhsNode = neo4j_connector_get_client()->updateNode([], [], $indexParam);
      }

      if (!$rhsNode) {
        $this->getLogger(__CLASS__)
          ->warning('Cannot find or create target graph node: ' . $entity_key);
        continue;
      }

      $graphNode->relateTo($rhsNode, $field->getFieldIdentifier())->save();
      $this->getLogger(__CLASS__)
        ->notice('Relationship between ' . $graphNode->getId() . ' and ' . $rhsNode->getId() . ' has been established.');
    }
  }

  /**
   * @param \Drupal\search_api\Item\ItemInterface $item
   * @param Node $graphNode
   */
  protected function makeRelationships(ItemInterface $item, $graphNode) {
    if (($mappingProcessor = $item->getIndex()->getProcessor(MappingProcessor::ID))) {
      static $relationshipMapping;
      if (!$relationshipMapping) {
        $relationshipMapping = $mappingProcessor->getConfiguration()[MappingProcessor::KEY_FIELD_MAPPING];
      }

      foreach ($item->getFields() as $field) {
        $sourceFieldKey = $field->getFieldIdentifier();
        if (empty($relationshipMapping[$sourceFieldKey])) {
          continue;
        }
        $this->createGraphRelationship($relationshipMapping[$sourceFieldKey], $field, $graphNode);
      }
    }
  }

}
