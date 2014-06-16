<?php
/**
 * Created by PhpStorm.
 * User: itarato
 * Date: 6/15/14
 * Time: 10:45 AM
 */

namespace Drupal\search_api_neo4j\Plugin\SearchApi\Backend;

use Drupal\neo4j_connector\Neo4JIndexParam;
use Drupal\search_api\Backend\BackendPluginBase;
use Drupal\search_api\Utility\Utility;

/**
 * @SearchApiBackend(
 *   id = "search_api_neo4j",
 *   label = @Translation("Neo4J graph database"),
 *   description = @Translation("Index items into graph database.")
 * )
 */
class SearchApiNeo4JBackend extends BackendPluginBase {

  /**
   * Indexes the specified items.
   *
   * @param \Drupal\search_api\Index\IndexInterface $index
   *   The search index for which items should be indexed.
   * @param \Drupal\search_api\Item\ItemInterface[] $items
   *   An array of items to be indexed, keyed by their item IDs.
   *
   *   The value of fields with the "tokens" type is an array of tokens. Each
   *   token is an array containing the following keys:
   *   - value: The word that the token represents.
   *   - score: A score for the importance of that word.
   *
   * @return string[]
   *   The IDs of all items that were successfully indexed.
   *
   * @throws \Drupal\search_api\Exception\SearchApiException
   *   If indexing was prevented by a fundamental configuration error.
   *
   * @see \Drupal\Core\Render\Element::child()
   */
  public function indexItems(\Drupal\search_api\Index\IndexInterface $index, array $items) {
    $indexed_keys = array();
    $client = neo4j_connector_get_client();
    foreach ($items as $item_key => $item) {
      // Delete from index if exist.
      $graph_node = $client->getNodeByIndex($index->machine_name, $item_key);
      if ($graph_node) {
        list($properties, $labels) = $this->getNodeInfo($item);
        $client->updateNode($properties, $labels, $graph_node);
        \Drupal::moduleHandler()->invokeAll('neo4j_connector_graph_node_update', array($graph_node, $item));
      }
      else {
        list($properties, $labels) = $this->getNodeInfo($item);
        $index_param = new Neo4JIndexParam($index->machine_name, $client::DEFAULT_INDEX_KEY, $item_key);
        $client->addNode($properties, $labels, $index_param);
      }
      $indexed_keys[] = $item_key;
    }

    return $indexed_keys;
  }

  /**
   * Deletes the specified items from the index.
   *
   * @param \Drupal\search_api\Index\IndexInterface $index
   *   The index for which items should be deleted.
   * @param string[] $ids
   *   An array of item IDs.
   *
   * @throws \Drupal\search_api\Exception\SearchApiException
   *   If an error occurred while trying to delete the items.
   */
  public function deleteItems(\Drupal\search_api\Index\IndexInterface $index, array $ids) {
    $client = neo4j_connector_get_client();
    foreach ($ids as $id) {
      $graph_node = $client->getNodeByIndex($index->machine_name, $id);
      if ($graph_node) {
        $client->deleteNode($graph_node);
      }
    }
  }

  /**
   * Deletes all the items from the index.
   *
   * @param \Drupal\search_api\Index\IndexInterface $index
   *   The index for which items should be deleted.
   *
   * @throws \Drupal\search_api\Exception\SearchApiException
   *   If an error occurred while trying to delete the items.
   */
  public function deleteAllIndexItems(\Drupal\search_api\Index\IndexInterface $index) {
    neo4j_connector_purge_db();
  }

  /**
   * Executes a search on this server.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The query to execute.
   *
   * @return \Drupal\search_api\Query\ResultSetInterface
   *   An associative array containing the search results.
   *
   * @throws \Drupal\search_api\Exception\SearchApiException
   *   If an error prevented the search from completing.
   */
  public function search(\Drupal\search_api\Query\QueryInterface $query) {
    // TODO: Implement search() method.
    $results = Utility::createSearchResultSet($query);
    $results->setResultCount(0);

    return $results;
  }

  public function getNodeInfo(\Drupal\search_api\Item\ItemInterface $item) {
    $properties = array();
    \Drupal::moduleHandler()->alter('neo4j_connector_properties', $properties, $item);

    $labels = array();
    \Drupal::moduleHandler()->alter('neo4j_connector_labels', $labels, $item);

    return array($properties, $labels);
  }

}