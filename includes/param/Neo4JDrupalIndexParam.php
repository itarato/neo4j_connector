<?php
/**
 * @file
 */

/**
 * Class Neo4JDrupalIndexParam
 * Defines a unique locator in the graph db. Used to identify Drupal items.
 */
class Neo4JDrupalIndexParam {

  /**
   * Name of the index.
   *
   * @var string
   */
  public $name;

  /**
   * Key name.
   *
   * @var string
   */
  public $key;

  /**
   * Value.
   *
   * @var string|number
   */
  public $value;

  /**
   * Constructor.
   *
   * @param string $name
   * @param string $key
   * @param string $value
   */
  public function __construct($name = NULL, $key = NULL, $value = NULL) {
    $this->name = $name;
    $this->key = $key;
    $this->value = $value;
  }

}
