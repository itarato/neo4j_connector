<?php
/**
 * Created by PhpStorm.
 * User: itarato
 * Date: 6/15/14
 * Time: 10:45 AM
 */

use \Drupal\search_api\Backend\BackendPluginBase;

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
    // TODO: Implement indexItems() method.
    return array();
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
    // TODO: Implement deleteItems() method.
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
    // TODO: Implement deleteAllIndexItems() method.
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
    return NULL;
  }

}