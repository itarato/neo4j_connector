<?php
/**
 * @file
 * Tests.
 */

namespace Drupal\neo4j_connector\Test;

use Drupal\simpletest\WebTestBase;

require_once __DIR__ . '/neo4j_connector.test_tool.inc';

class Neo4JDrupalConnectorTest extends WebTestBase {

  protected $neo4j_drupal_connector;

  public static function getInfo() {
    return array(
      'name' => 'Neo4J Drupal Connector Test',
      'description' => '',
      'group' => 'Neo4J Drupal Connector',
    );
  }

  public function setUp() {
    parent::setUp('neo4j_connector');
    $this->neo4j_drupal_connector = Neo4JDrupal::createSharedInstance(
      new MockNeo4JDrupalClient(),
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
