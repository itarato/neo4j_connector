# Neo4J Drupal Connector

The module attempts to connect Drupal with the [Neo4J database](http://www.neo4j.org/). It is a concept module, the end goal is not yet finalized.

# What the module does?

When you create a node it will create a corresponding graph node. You can use fields, such as text, number, taxonomy term reference, entityreference, user reference and node reference. Those properties and connections will be added to the graph:

* Entities are graph nodes (having some properties)
* Fields are separate graph nodes (having their main value as a property)

For node, user and taxonomy term entities it creates an extra tab on the entity page [Graph info] where the graph node info is presented.

Provides an admin interface to enter queries: /admin/config/neo4j/console
Provides an admin generator page where is possible to purge the graph database or regenerating all users and nodes: /admin/config/neo4j.

A views plugin is under development.

# Setup

* Download [Neo4J database driver](http://www.neo4j.org/download)
* Install Drupal and add this module
* With Composer install the PHP Driver. [Follow the instructions](https://github.com/jadell/neo4jphp).
