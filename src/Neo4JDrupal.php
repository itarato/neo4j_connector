<?php
/**
 * @file
 * Neo4J connector classes and interfaces.
 */

namespace Drupal\neo4j_connector;

use Drupal\Core\Logger\LoggerChannelTrait;
use Everyman\Neo4j\Client;
use Everyman\Neo4j\Index\NodeIndex;
use Everyman\Neo4j\Label;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\PropertyContainer;
use Everyman\Neo4j\Relationship;

/**
 * Class Neo4JDrupal
 * Main connector to the Neo4J database.
 */
class Neo4JDrupal {

  use LoggerChannelTrait;

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
   * @param array $labels
   *  Array of label strings.
   * @param Neo4JIndexParam $indexParam
   *  Index to locate the new node.
   * @return \Everyman\Neo4j\Node Created graph node object.
   * Created graph node object.
   *
   * @throws \Everyman\Neo4j\Exception
   * @throws \Exception
   */
  public function updateNode(array $properties = array(), array $labels = array(), Neo4JIndexParam $indexParam = NULL) {
    if (!($graphNode = $this->getGraphNodeOfIndex($indexParam))) {
      $isNew = TRUE;
      $graphNode = $this->client->makeNode();
    }
    else {
      $isNew = FALSE;
    }

    $graphNode->setProperties($properties);
    $graphNode->save();

    if (!$graphNode || !$graphNode->getId()) {
      throw new \Exception('Graph node has not been created.');
    }

    // Labels.
    if ($labels) {
      $label_objects = array();
      foreach ($labels as $label_string) {
        $label_objects[] = new Label($this->client, $label_string);
      }
      $graphNode->addLabels($label_objects);
    }

    // Index.
    if ($isNew && $indexParam) {
      $this->getIndex($indexParam->name)->add($graphNode, $indexParam->key, $indexParam->value);
    }

    return $graphNode;
  }

  /**
   * Delete a graph node using the index.
   *
   * @param Neo4JIndexParam $indexParam
   *  Index.
   */
  public function deleteNode(Neo4JIndexParam $indexParam) {
    $this->deleteRelationships($indexParam);

    if ($graph_node = $this->getGraphNodeOfIndex($indexParam)) {
      $this->getIndex($indexParam->name)->remove($graph_node);
      $graph_node->delete();
      \Drupal::logger(__CLASS__)->info('Graph node has been deleted: @nid', ['@nid' => $graph_node->getId()]);
    }
  }

  /**
   * Remove all relationships from a graph node.
   *
   * @param Neo4JIndexParam $indexParam
   *  Index.
   * @param array $types
   * @param string $dir
   *  Everyman\Neo4j\Relationship::Direction*
   */
  public function deleteRelationships(Neo4JIndexParam $indexParam, $types = [], $dir = Relationship::DirectionAll) {
    if (!($node = $this->getGraphNodeOfIndex($indexParam)) || !($node instanceof Node)) {
      $this->getLogger(__CLASS__)->warning('Cannot find node for deleting its relationships.');
    }

    /** @var Relationship[] $relationships */
    $relationships = $node->getRelationships($types, $dir);
    foreach ($relationships as $relationship) {
      $relationship->delete();
    }
  }

  /**
   * Fetch the graph node identified by the index.
   *
   * @param Neo4JIndexParam $index_param
   *  Index parameter.
   * @return PropertyContainer|NULL
   */
  public function getGraphNodeOfIndex(Neo4JIndexParam $index_param) {
    return $this->getIndex($index_param->name)->findOne($index_param->key, $index_param->value);
  }

}
