<?php
/**
 */

namespace Drupal\neo4j_connector\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\Context\ContextInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Neo4JAdminForm extends ConfigFormBase {

  public function __construct(ConfigFactory $config_factory) {
    parent::__construct($config_factory);
  }

  public function getFormId() {
    return 'neo4j_connector_admin_settings';
  }

  public function buildForm(array $form, array &$form_state) {
    $settings = $this->configFactory->get('neo4j_connector.site');

    $form['host'] = array(
      '#title' => t('Server host'),
      '#type' => 'textfield',
      '#default_value' => $settings->get('host'),
    );

    $form['port'] = array(
      '#title' => t('Server port'),
      '#type' => 'textfield',
      '#default_value' => $settings->get('port'),
    );

    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, array &$form_state) {
  }

  public function submitForm(array &$form, array &$form_state) {
    $this->configFactory->get('neo4j_connector.site')
      ->set('host', $form_state['values']['host'])
      ->set('port', $form_state['values']['port'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
