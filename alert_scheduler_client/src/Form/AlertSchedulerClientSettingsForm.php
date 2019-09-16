<?php

namespace Drupal\alert_scheduler_client\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class AlertSchedulerClientSettingsForm extends ConfigFormBase {

  public function getFormId() {
    return 'alert_scheduler_client_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      ALERT_SCHEDULER__SETTINGS_CLIENT,
    ];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(ALERT_SCHEDULER__SETTINGS_CLIENT);

    $base_url = $config->get('base_url');
    $default_base_url = '/api/alerts/';
    $form['base_url'] = [
      '#type' => 'textfield',
      '#title' => 'Request URL',
      '#description' => 'Base URL of the REST Resource from which to request calendar hours.',
      '#default_value' => isset($base_url) ? $base_url : $default_base_url,
    ];

    $refresh_interval = $config->get('refresh_interval');
    $default_refresh_interval = 60;
    $form['refresh_interval'] = [
      '#type' => 'number',
      '#title' => 'Refresh Interval',
      '#description' => 'Time interval between refreshing an hours view (in seconds).',
      '#default_value' => isset($refresh_interval) ? $refresh_interval / 1000 : $default_refresh_interval,
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $base_url = $form_state->getValue('base_url');
    $refresh_interval = $form_state->getValue('refresh_interval');
    $this->configFactory->getEditable(ALERT_SCHEDULER__SETTINGS_CLIENT)
      ->set('base_url', $base_url)
      ->set('refresh_interval', $refresh_interval * 1000)
      ->save();
    parent::submitForm($form, $form_state);
  }
}