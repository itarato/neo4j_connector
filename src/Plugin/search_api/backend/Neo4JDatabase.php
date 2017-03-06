<?php

namespace Drupal\neo4j_connector\Plugin\search_api\backend;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\search_api\Annotation\SearchApiBackend;
use Drupal\search_api\Backend\BackendPluginBase;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\SearchApiException;
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

  /**
   * Indexes the specified items.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The search index for which items should be indexed.
   * @param \Drupal\search_api\Item\ItemInterface[] $items
   *   An array of items to be indexed, keyed by their item IDs.
   *
   * @return string[]
   *   The IDs of all items that were successfully indexed.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if indexing was prevented by a fundamental configuration error.
   */
  public function indexItems(IndexInterface $index, array $items) {
    $relationshipMapping = neo4j_connector_get_server_mapping_configuration();
    $indexedKeys = [];

    foreach ($items as $indexItemID => $item) {
      $id = $item->getId();

      $properties = [];
      foreach ($item->getFields() as $field) {
        $values = $field->getValues();
        $prop_id = $field->getPropertyPath();
        $properties[$prop_id] = @$values[0];
      }

      $indexParam = neo4j_connector_entity_index_factory()->create($id);
      $graphNode = neo4j_connector_get_client()->updateNode($properties, [$item->getDatasourceId()], $indexParam);

      neo4j_connector_get_client()->deleteRelationships($indexParam, [], Relationship::DirectionOut);
      foreach ($item->getFields() as $field) {
        if (!isset($relationshipMapping[$field->getPropertyPath()])) continue;
        $refEntityType = $relationshipMapping[$field->getPropertyPath()];

        foreach ($field->getValues() as $value) {
          $refEntity = \Drupal::entityTypeManager()->getStorage($refEntityType)->load($value);
          $regLangCode = $refEntity->language()->getId();
          $entity_key = "entity:$refEntityType/$value:$regLangCode";
          $indexParam = neo4j_connector_entity_index_factory()->create($entity_key);
          if (!($rhsNode = neo4j_connector_get_client()->getGraphNodeOfIndex($indexParam))) {
            $rhsNode = neo4j_connector_get_client()->updateNode([], [], $indexParam);
          }

          if (!$rhsNode) {
            // @todo add logging (and logger to the class).
            continue;
          }

          $graphNode->relateTo($rhsNode, $field->getFieldIdentifier())->save();
        }
      }

      if ($graphNode) $indexedKeys[] = $id;
    }

    return $indexedKeys;
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
      // Delete outbound rels.
      neo4j_connector_get_client()->query("MATCH (n)-[r]->() WHERE n.drupal_id = '{id}' DELETE r", ['id' => $item_id]);
      // Delete inbound rels.
      neo4j_connector_get_client()->query("MATCH ()-[r]->(n) WHERE n.drupal_id = '{id}' DELETE r", ['id' => $item_id]);
      // Delete node itself.
      neo4j_connector_get_client()->query("MATCH (n) WHERE n.drupal_id = '{id}' DELETE n", ['id' => $item_id]);
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
    // TODO: Implement search() method.
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
    // TODO: Implement buildConfigurationForm() method.
    return [];
  }

  /**
   * Form validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the plugin form as built
   *   by static::buildConfigurationForm().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form. Calling code should pass on a subform
   *   state created through
   *   \Drupal\Core\Form\SubformState::createForSubform().
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement validateConfigurationForm() method.
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the plugin form as built
   *   by static::buildConfigurationForm().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form. Calling code should pass on a subform
   *   state created through
   *   \Drupal\Core\Form\SubformState::createForSubform().
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitConfigurationForm() method.
  }

  public function defaultConfiguration() {
    return [];
  }

}
