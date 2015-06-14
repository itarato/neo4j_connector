<?php
/**
 * @file
 * Helper tools for tests.
 */

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Index\NodeIndex;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Cypher\Query;

class Neo4JDrupalTestHistory {

  public static $history = array();

  const TAG_DEFAULT = 'default';

  public static function record($class, $method, array $arguments, $tag = self::TAG_DEFAULT) {
    if (
      !isset(self::$history[$class]) ||
      !isset(self::$history[$class][$method])
    ) {
      self::$history[$class][$method] = array();
    }

    self::$history[$class][$method][] = array(
      'arguments' => $arguments,
      'tag' => $tag,
    );
  }

  public static function getCallsFor($class, $method, $delta = NULL) {
    if (
      !isset($history[$class]) ||
      !isset($history[$class][$method]) ||
      ($delta !== NULL && !isset($history[$class][$method][$delta]))
    ) {
      return FALSE;
    }

    return $delta !== NULL ? self::$history[$class][$method][$delta] : self::$history[$class][$method];
  }

}

class MockNeo4JDrupalClient extends Client {

  public function makeNode() {
    Neo4JDrupalTestHistory::record(__CLASS__, __FUNCTION__, func_get_args());
    return new MockNeo4JDrupalNode($this);
  }

  public function getNode($id) {
    Neo4JDrupalTestHistory::record(__CLASS__, __FUNCTION__, func_get_args());
    return new MockNeo4JDrupalNode($this);
  }

}

class MockNeo4JDrupalNodeIndex extends NodeIndex {

}

class MockNeo4JDrupalNode extends Node {

}

class MockNeo4JDrupalQuery extends Query {

}

class Neo4JDrupalTestFactory implements Neo4JDrupalAdapterFactoryInterface {

  /**
   * @return Neo4JDrupal
   */
  public function get() {
    return new Neo4JDrupal(new MockNeo4JDrupalClient(), 'MockNeo4JDrupalNodeIndex', 'MockNeo4JDrupalQuery');
  }

}
