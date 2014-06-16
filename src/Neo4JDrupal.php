<?php
/**
 * @file
 * Neo4J connector classes and interfaces.
 */

namespace Drupal\neo4j_connector;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Index\NodeIndex;
use Everyman\Neo4j\Label;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\PropertyContainer;

/**
 * Class Neo4JDrupal
 * Main connector to the Neo4J database.
 */
class Neo4JDrupal {

  /**
   * Shared instance.
   *
   * @var Neo4JDrupal
   */
  protected static $sharedInstance;

  /**
   * Main DB client.
   *
   * @var Client
   */
  public $client;

  /**
   * Class of graph node index. Should be NodeIndex or subtype of it.
   *
   * @var Neo4JDrupalIndexFactory
   */
  public $nodeIndexFactory;

  /**
   * Class of the query. Should be Query or subtype of it.
   *
   * @var Neo4JDrupalQueryFactory
   */
  public $queryFactory;

  /**
   * Property name on the relationship that contains the owner graph node's ID.
   */
  const OWNER = 'owner-id';

  const DEFAULT_INDEX_KEY = 'id';

  /**
   * Constructor.
   * Use Neo4JDrupal::sharedInstance() instead.
   */
  public function __construct(Client $client, Neo4JDrupalIndexFactory $node_index_factory, Neo4JDrupalQueryFactory $query_factory) {
    $this->client = $client;
    $this->nodeIndexFactory = $node_index_factory;
    $this->queryFactory = $query_factory;
  }

  /**
   * Directly creates a shared instance which is used throughout the system.
   * Use this if you need a special setup.
   *
   * @param Client $client
   * @param $node_index_class
   * @param $query_class
   * @return Neo4JDrupal
   */
  public static function createSharedInstance(Client $client, Neo4JDrupalIndexFactory $node_index_class, Neo4JDrupalQueryFactory $query_class) {
    self::$sharedInstance = new Neo4JDrupal($client, $node_index_class, $query_class);
    return self::$sharedInstance;
  }

  /**
   * Singleton instance. It's the best to use this, unless you want some tricks.
   *
   * @return Neo4JDrupal
   */
  public static function sharedInstance() {
    if (!self::$sharedInstance) {
      $config = \Drupal::config('neo4j_connector.site');
      $client = new Client($config->get('host'), $config->get('port'));
      self::$sharedInstance = new Neo4JDrupal($client, new Neo4JDrupalIndexFactory(), new Neo4JDrupalQueryFactory());
    }

    return self::$sharedInstance;
  }

  /**
   * Request an index on the fly.
   *
   * @param $index_name string
   * @return NodeIndex
   */
  public function getIndex($index_name) {
    static $indexes = array();

    if (!isset($indexes[$index_name])) {
      $node_index_factory = $this->nodeIndexFactory;
      $indexes[$index_name] = $node_index_factory->create($this->client, $index_name);
      $indexes[$index_name]->save();
    }

    return $indexes[$index_name];
  }

  /**
   * Execute a query.
   *
   * @param $template
   *  Query string.
   * @param array $vars
   *  Variables.
   * @return \Everyman\Neo4j\Query\ResultSet
   */
  public function query($template, $vars = array()) {
    $query_factory = $this->queryFactory;
    $query = $query_factory->create($this->client, $template, $vars);
    return $query->getResultSet();
  }

  /**
   * Adds a new index to the DB.
   * Also takes care about fields.
   *
   * @param array $properties
   *  Properties array to store on the graph node.
   * @param $labels
   *  Array of label strings.
   * @param Neo4JIndexParam $index_param
   *  Index to locate the new node.
   *
   * @return Node
   *  Created graph node object.
   */
  public function addNode(array $properties, array $labels = array(), Neo4JIndexParam $index_param = NULL) {
    $graph_node = $this->addGraphNode($properties, $index_param);

    // Labels.
    $label_objects = array();
    foreach ($labels as $label_string) {
      $label_objects[] = new Label($this->client, $label_string);
    }
    if ($label_objects) {
      $graph_node->addLabels($label_objects);
    }

    return $graph_node;
  }

  /**
   * Create graph node.
   *
   * @param array $properties
   *  Array of properties.
   * @param Neo4JIndexParam $indexParam
   *  Index.
   * @return Node
   */
  public function addGraphNode(array $properties, Neo4JIndexParam $indexParam = NULL) {
    $node = $this->client->makeNode();
    $node->setProperties($properties);
    $node->save();

    if ($indexParam) {
      $this->getIndex($indexParam->name)->add($node, $indexParam->key, $indexParam->value);
    }

    watchdog(__METHOD__, 'Graph node has been created: @id', array('@id' => $node->getId()));

    return $node;
  }

  /**
   * Delete a graph node using the index.
   *
   * @param Neo4JIndexParam $indexParam
   *  Index.
   */
  public function deleteNode(Node $graph_node, $index_machine_name = NULL) {
    $this->deleteRelationships($graph_node);

    if ($index_machine_name) {
      $this->getIndex($index_machine_name)->remove($graph_node);
    }
    $graph_node->delete();
    watchdog(__METHOD__, 'Graph node has been deleted: @nid', array('@nid' => $graph_node->getId()));
  }

  /**
   * Remove all relationships from a graph node.
   */
  public function deleteRelationships(Node $node) {
    $relationships = $node->getRelationships();
    foreach ($relationships as $relationship) {
      $relationship->delete();
    }
  }

  /**
   * Update a graph node.
   * Removes and reestablish all relationships (fields and references).
   *
   */
  public function updateNode(array $properties, array $labels = array(), Node $graph_node) {
    // @todo possible duplication of AddNode - wrap it
    $graph_node->setProperties($properties);
    $graph_node->save();

    // Labels.
    $label_objects = array();
    foreach ($labels as $label_string) {
      $label_objects[] = new Label($this->client, $label_string);
    }
    $graph_node->addLabels($label_objects);

    $relationships = $graph_node->getRelationships();
    foreach ($relationships as $relationship) {
      if ($graph_node->getId() != $relationship->getProperty(Neo4JDrupal::OWNER)) {
        // The relationship does not belong to the graph node. Keep it.
        continue;
      }
      $relationship->delete();
    }

    return $graph_node;
  }

  /**
   * Fetch the graph node identified by the index.
   *
   * @param Neo4JIndexParam $index_param
   *  Index parameter.
   * @return bool|Node
   */
  public function getGraphNodeOfIndex(Neo4JIndexParam $index_param) {
    $prop_cont = $this->getIndex($index_param->name)->findOne($index_param->key, $index_param->value);
    if ($prop_cont) {
      return $this->client->getNode($prop_cont->getId());
    }
    return FALSE;
  }

  public function connectOrCreate(Node $host_node, Neo4JIndexParam $guest_index_param, $index_domain, $index_id, $relation_name) {
    $guest_node = $this->getGraphNodeOfIndex($guest_index_param);

    if (!$guest_node) {
      $indexItem = new IndexItem($index_domain, $index_id);
      $guest_node = neo4j_connector_get_index()->addNode($indexItem, Index::DO_NOT_INCLUDE_RELATIONSHIP);
    }

    if ($guest_node) {
      $host_node->relateTo($guest_node, $relation_name)
        ->setProperty(Neo4JDrupal::OWNER, $host_node->getId())
        ->save();
      return $guest_node;
    }

    watchdog(__CLASS__, 'Unable to connect to reference. Domain: @domain, id: @id.', array(
      '@domain' => $index_domain,
      '@id' => $index_id,
    ), WATCHDOG_WARNING);
    return NULL;
  }

  public function getNodeByIndex($index_name, $value) {
    return $this->getIndex($index_name)->findOne(self::DEFAULT_INDEX_KEY, $value);
  }

}
