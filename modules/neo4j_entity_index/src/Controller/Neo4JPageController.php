<?php
/**
 * Created by PhpStorm.
 * User: itarato
 * Date: 1/1/14
 * Time: 9:41 PM
 */

namespace Drupal\neo4j_entity_index\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Class Neo4JPageController
 * @package Drupal\neo4j_entity_index\Controller
 */
class Neo4JPageController extends ControllerBase {

  /**
   * Page callback of the node graph tab page.
   *
   * @param $node
   *  Node object
   * @return string
   */
  public function nodeGraphInfo(EntityInterface $node) {
    return $this->graphInfoPageContent('node', $node->id());
  }

  /**
   * Page callback of the user graph tab page.
   *
   * @param EntityInterface $user
   *  User object.
   * @return string
   */
  public function userGraphInfo(EntityInterface $user) {
    return $this->graphInfoPageContent('user', $user->id());
  }

  /**
   * Page callback og the term graph tab page.
   *
   * @param EntityInterface $term
   *  Term object.
   * @return string
   */
  public function termGraphInfo(EntityInterface $taxonomy_term) {
    return $this->graphInfoPageContent('taxonomy_term', $taxonomy_term->id());
  }

  /**
   * Helper to generate a graph info about an entity.
   *
   * @param string $entity_type
   * @param string $id
   * @return null|string
   */
  protected function graphInfoPageContent($entity_type, $id) {
    $graph_node = neo4j_entity_index_get_graph_node_for_type_and_id($entity_type, $id);

    if (!$graph_node) {
      return t('Cannot find associated graph node.');
    }

    $info = array();
    $info[] = t('Graph node ID: <strong>@nodeid</strong>', array('@nodeid' => $graph_node->getId()));
    $info[] = ['#markup' => '<pre>MATCH (n:' . $entity_type . ') WHERE n.entity_id = "' . $id . '" RETURN n;</pre>'];

    return array(
      '#theme' => 'item_list',
      '#items' => $info,
    );
  }

}
