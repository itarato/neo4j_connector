<?php
/**
 */

namespace Drupal\neo4j_connector\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class Neo4JAdminForm extends ConfigFormBase {

  public function __construct(ConfigFactory $config_factory) {
    parent::__construct($config_factory);
  }

  public function getFormId() {
    return 'neo4j_connector_admin_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = $this->configFactory->get('neo4j_connector.site');

    $form['host'] = array(
      '#title' => t('Server host'),
      '#type' => 'textfield',
      '#default_value' => $settings->get('host'),
      '#description' => t('Default host of the Neo4J server. Default is localhost. For authentication add the credentials to the domain: &lt;username&gt;:&lt;password&gt;@&lt;domain&gt;.'),
    );

    $form['port'] = array(
      '#title' => t('Server port'),
      '#type' => 'textfield',
      '#default_value' => $settings->get('port'),
      '#description' => t('Port for the Neo4J server. Default is 7474.'),
    );

    $form['index_immediately'] = array(
      '#title' => t('Index immediately'),
      '#type' => 'checkbox',
      '#default_value' => $settings->get('index_immediately'),
      '#description' => t('Set it if you would like to send items to the index immediately and do not want to wait for cron.'),
    );

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('neo4j_connector.site')
      ->set('host', $form_state->getValue('host'))
      ->set('port', $form_state->getValue('port'))
      ->set('index_immediately', $form_state->getValue('index_immediately'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['neo4j_connector.site'];
  }

}
