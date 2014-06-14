<?php
/**
 * @file
 * Tests.
 */

namespace Drupal\neo4j_connector\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\neo4j_connector\Neo4JDrupal;

require_once __DIR__ . '/neo4j_connector.test_tool.inc';

class Neo4JDrupalConnectorTest extends WebTestBase {

  protected $neo4j_drupal_connector;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('neo4j_connector');

  public static function getInfo() {
    return array(
      'name' => 'Neo4J Drupal Connector Test',
      'description' => '',
      'group' => 'Neo4J Drupal Connector',
    );
  }

  public function setUp() {
    parent::setUp();

    $mock_Neo4JDrupalClient = \PHPUnit_Framework_MockObject_Generator::getMock('Everyman\Neo4j\Client');

    $this->neo4j_drupal_connector = Neo4JDrupal::createSharedInstance(
      $mock_Neo4JDrupalClient,
      'MockNeo4JDrupalNodeIndex',
      'MockNeo4JDrupalQuery'
    );
  }

  public function testGraphNodeCreatedAndIndexed() {
    $this->drupalCreateNode();

    $clientHistory = Neo4JDrupalTestHistory::getCallsFor(
      'MockNeo4JDrupalClient',
      'makeNode'
    );
    $this->assertTrue($clientHistory === FALSE, 'No items in the DB. Only marked for indexing.');

    neo4j_connector_index_process_index();

    $clientHistory = Neo4JDrupalTestHistory::getCallsFor(
      'MockNeo4JDrupalClient',
      'makeNode'
    );
    $this->assertTrue(is_array($clientHistory) && count($clientHistory) > 0, 'The API called the graph node creation.');
  }

  public function testGraphNodeRead() {

  }

}
