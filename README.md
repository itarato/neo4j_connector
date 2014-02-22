# Neo4J Drupal Connector

The module attempts to connect Drupal with the [Neo4J database](http://www.neo4j.org/). It is a concept module, the end goal is not yet finalized.

# The problem

Drupal mostly relies on traditional databases, which performs badly at certain conditions. Imagine you have lot's of references. Your interest is to know (let's say) which node has a certain field value that is connected to another node - no matter how deep is the connection. Or another example, you're on a node interested in which other nodes has similar characteristics (by having similar field values or connect to the same contents). There queries are hard to describe with a relational - or even with a traditional no-sql database. That's where Neo4J and the graph database plays an important role.


# The solution

This module doesn't intend to change the backend to a graph database, it wouldn't serve Drupal as a whole. In fact it's an overlay. It maintains all entities with certain properties which you can select as well as their field data. Then you can create queries against the graph database (directly to the graph db engine or through views) and filter your content. So you only query it when you need graph query, then retain the content IDs and use that to present content or feed Views with it. This graph backend is just another search engine such as Solr.


# What the module does?

When you create a new entity (node, term, user, etc) it will create a corresponding graph node. You can use fields, such as text, number, taxonomy term reference, entityreference, user reference and node reference. Those properties and connections will be added to the graph:

* entities are graph nodes (having some properties)
* fields are also (separate) graph nodes (having their main value as a property)
* connection between entities and their fields are relationships

For node, user and taxonomy term entities it creates an extra tab on the entity page [Graph info] where the graph node info is presented (just for convenience to lookup the graph index).

Provides an admin interface to enter queries: /admin/config/neo4j/console
Provides an admin generator page where is possible to purge the graph database or reindexing all entities: /admin/config/neo4j.

A views plugin is under development - a demo version is already in the repository.


# Setup

* Download [Neo4J database driver](http://www.neo4j.org/download)
* Install Drupal and add this module
* With Composer install the PHP Driver. [Follow the instructions](https://github.com/jadell/neo4jphp).
  * cd PATH_TO_MODULE/
  * composer install (or php composer.phar install)
* Run the Neo4J database server:
  * cd PATH_TO_NEO4J/bin
  * ./neo4j start
* Verify it's running:
  * (by default:) http://localhost:7474/browser/


# Drush commands

* **drush neo-purge** Purge the whole graph database along with the index.
* **drush neo-idx-stat** Shows index statistics.
* **drush neo-to-idx** Send entities to index.
* **drush neo-index** Index all marked content.
