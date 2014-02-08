<?php
/**
 * @file
 * Neo4J connector classes and interfaces.
 */

namespace Drupal\neo4j_connector;

use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Field;
use Everyman\Neo4j\Client;
use Everyman\Neo4j\Index\NodeIndex;
use Everyman\Neo4j\Label;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Cypher\Query;

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
   * Adds a new Drupal entity to the DB.
   * Also takes care about fields.
   *
   * @param $entity
   *  Entity object.
   * @param array $properties
   *  Properties array to store on the graph node.
   * @param Neo4JDrupalIndexParam $index_param
   *  Index to locate the new node.
   * @param $labels
   *  Array of label strings.
   * @param $add_fields
   *  Boolean flag - if processing fields is necessary. Default is TRUE.
   *
   * @return Node
   *  Created graph node object.
   */
  public function addEntity($entity, array $properties, Neo4JDrupalIndexParam $index_param = NULL, array $labels = array(), $add_fields = TRUE) {
    $node = $this->addGraphNode($properties, $index_param);
    $label_objects = array();
    foreach ($labels as $label_string) {
      $label_objects[] = new Label($this->client, $label_string);
    }
    $node->addLabels($label_objects);

    if ($add_fields) {
      $this->addEntityFields($entity, $node);
    }

    return $node;
  }

  /**
   * Create graph node.
   *
   * @param array $properties
   *  Array of properties.
   * @param Neo4JDrupalIndexParam $indexParam
   *  Index.
   * @return Node
   */
  public function addGraphNode(array $properties, Neo4JDrupalIndexParam $indexParam = NULL) {
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
   * @param Neo4JDrupalIndexParam $indexParam
   *  Index.
   */
  public function deleteEntity(Neo4JDrupalIndexParam $indexParam) {
    $this->deleteRelationships($indexParam);

    if ($graph_node = $this->getGraphNodeOfIndex($indexParam)) {
      $this->getIndex($indexParam->name)->remove($graph_node);
      $graph_node->delete();
      watchdog(__METHOD__, 'Graph node has been deleted: @nid', array('@nid' => $graph_node->getId()));
    }
  }

  /**
   * Remove all relationships from a graph node.
   *
   * @param Neo4JDrupalIndexParam $indexParam
   *  Index.
   */
  public function deleteRelationships(Neo4JDrupalIndexParam $indexParam) {
    if ($node = $this->getGraphNodeOfIndex($indexParam)) {
      $relationships = $node->getRelationships();
      foreach ($relationships as $relationship) {
        $relationship->delete();
      }
    }
  }

  /**
   * Update a Drupal entity.
   * Removes and reestablish all relationships (fields and references).
   *
   * @param $entity
   *  Drupal entity.
   * @param array $properties
   *  Properties to store.
   * @param Neo4JDrupalIndexParam $index_param
   *  Index.
   *
   * @return Node
   */
  public function updateEntity($entity, array $properties, Neo4JDrupalIndexParam $index_param = NULL) {
    $gnode = $this->getGraphNodeOfIndex($index_param);
    $gnode->setProperties($properties);
    $gnode->save();

    $relationships = $gnode->getRelationships();
    foreach ($relationships as $relationship) {
      $relationship->delete();
    }

    $this->addEntityFields($entity, $gnode);
    return $gnode;
  }

  /**
   * Create field relationships of an entity.
   *
   * @param $entity
   *  Drupal entity.
   * @param $node
   *  Graph node.
   */
  public function addEntityFields(EntityInterface $entity, Node $node) {
    $field_instances = Field::fieldInfo()->getBundleInstances($entity->entityType(), $entity->bundle());
    foreach ($field_instances as $field_instance) {
      $field_info = Field::fieldInfo()->getField($entity->entityType(), $field_instance->field_name);
      if ($neo4jFieldHandler = Neo4JDrupalFieldHandlerFactory::getInstance($field_info, $node)) {
        $neo4jFieldHandler->processFieldData($entity, $field_instance->field_name);
      }
    }
  }

  /**
   * Fetch a graph node of a Drupal entity.
   *
   * @param $entity_type
   *  Entity type string.
   * @param $id
   *  Entity id integer.
   * @return bool|Node
   *  Graph node object, or FALSE if not exist.
   */
  public function getGraphNodeOfEntity($entity_type, $id) {
    $index_param = new Neo4JDrupalIndexParam(NEO4J_CONNECTOR_ENTITY_INDEX_PREFIX . $entity_type, 'entity_id', $id);
    return $this->getGraphNodeOfIndex($index_param);
  }

  /**
   * Fetch the graph node identified by the index.
   *
   * @param Neo4JDrupalIndexParam $index_param
   *  Index parameter.
   * @return bool|Node
   */
  public function getGraphNodeOfIndex(Neo4JDrupalIndexParam $index_param) {
    $prop_cont = $this->getIndex($index_param->name)->findOne($index_param->key, $index_param->value);
    if ($prop_cont) {
      return $this->client->getNode($prop_cont->getId());
    }
    return FALSE;
  }

}
