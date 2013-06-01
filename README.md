# Neo4J Drupal Connector

It is a development module, means not serving any useful functionality, nor is
an API. It simply represents the usage of the Neo4J PHP driver.

# What the module does?

After the successful install you need to start the neo4j server. When you
create a node it will create a corresponding graph node. You can use fields,
such as text, number, taxonomy term reference or entityreference. Those properties
and connections will be added to the graph.

# Setup

* Download Neo4J database driver: http://www.neo4j.org/download
* Install Drupal and add this module
* With Composer install the PHP Driver. For instructions follow: https://github.com/jadell/neo4jphp
