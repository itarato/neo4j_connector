# Neo4J Drupal Connector

This module is a Search API index to the Neo4J graph database. It allows entities with fields creating graph nodes with graph properties on them and establishing relationship between each other.

# The problem

Drupal mostly relies on traditional databases, which performs badly at certain conditions. Imagine you have lot's of references. Your interest is to know (let's say) which node has a certain field value that is connected to another node - no matter how deep is the connection. Or another example, you're on a node interested in which other nodes has similar characteristics (by having similar field values or connect to the same contents). There queries are hard to describe with a relational - or even with a traditional no-sql database. That's where Neo4J and the graph database plays an important role.

On a more practical angle: let's say you have a library, where book's are nodes, visitors are users and borrow records are entities as well referencing books and users together. You could easily create a recommendation engine by searching for connections between books and users at the desired level deep without suffering performance issues.


# The solution

With the Search API's excellent mechanism and UI admins can collect the needed entities with field data as well as select which of those selected fields should make a connection (graph node relationship) to another entity.

The module also provides a raw interface to query the graph backend, as well as a developer API is available for accessing the graph data. Unfortunately at this stage there is no UI for making queries.


# Setup

* Download this module into your Drupal contrib folder
* Install module dependencies with Composer:
  * cd PATH_TO_MODULE
  * composer install
* Make sure the Neo4J server is running and accessible
* Set the Neo4J server information on: `/admin/config/neo4j/settings`
* Install Search API module
* Add a new backend and set it to Neo4J Graph Backend


# Similar modules

The [Neo4J](http://drupal.org/project/neo4j) contrib module is using Neo4J as well, however that is using the Rules module instead of Search API.
