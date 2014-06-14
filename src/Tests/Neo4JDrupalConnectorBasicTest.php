<?php
/**
 * @file
 */

namespace Drupal\neo4j_connector\Tests;

use Drupal\neo4j_connector\Neo4JDrupal;
use Drupal\Tests\UnitTestCase;

class Neo4JDrupalConnectorBasicTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'Neo4J Drupal: Basic connector',
      'description' => 'Tests the connector classes.',
      'group' => 'Neo4J Drupal Connector',
    );
  }

  public function testGraphNodeCreation() {
//    $client = $this->getMock('Everyman\Neo4j\Client');
//    $node_index_factory = $this->getMock('Drupal/neo4j_connector/Neo4JDrupalIndexFactory');
//    $query_factory = $this->getMock('Drupal/neo4j_connector/Neo4JDrupalQueryFactory');
//    $connector = new Neo4JDrupal($client, $node_index_factory, $query_factory);
//
////    $entity = $this
////      ->getMockBuilder('Drupal\user\Entity\User')
////      ->disableOriginalConstructor()
////      ->getMock();
//    $entity = $this->getMock('Drupal\Core\Entity\EntityInterface')
//      ->expects($this->any())
//      ->method('entityType')
//      ->will($this->returnValue('foo'));
//
//    $connector->addEntity($entity, array());
  }

}
