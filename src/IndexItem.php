<?php
/**
 * @file
 */

namespace Drupal\neo4j_connector;

/**
 * Class IndexItem
 * @package Drupal\neo4j_connector
 */
class IndexItem {

  /**
   * @var string
   */
  protected $domain;

  /**
   * @var string
   */
  protected $id;

  /**
   * Constructor.
   *
   * @param null $domain
   * @param null $id
   */
  public function __construct($domain = NULL, $id = NULL) {
    $this->domain = $domain;
    $this->id = $id;
  }

  /**
   * @return string
   */
  public function getDomain() {
    return $this->domain;
  }

  /**
   * @param string $domain
   */
  public function setDomain($domain) {
    $this->domain = $domain;
  }

  /**
   * @return string
   */
  public function getId() {
    return $this->id;
  }

  /**
   * @param string $id
   */
  public function setId($id) {
    $this->id = $id;
  }

  public function __toString() {
    return "IndexItem [{$this->domain},{$this->id}]";
  }

}
