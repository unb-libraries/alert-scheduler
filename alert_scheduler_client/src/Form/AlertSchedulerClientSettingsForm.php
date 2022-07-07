<?php

namespace Drupal\alert_scheduler_client\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for alert-scheduler settings.
 *
 * @package Drupal\alert_scheduler_client\Form
 */
class AlertSchedulerClientSettingsForm extends ConfigFormBase {

  /**
   * {@inheritDoc}
   */
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

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(ALERT_SCHEDULER__SETTINGS_CLIENT);

    $base_url = $config->get('base_url');
    $default_base_url = '/api/alerts';
    $form['base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Request URL'),
      '#description' => $this->t('Base URL of the REST Resource from which to request calendar hours.'),
      '#default_value' => isset($base_url) ? $base_url : $default_base_url,
    ];

    $refresh_interval = $config->get('refresh_interval');
    $default_refresh_interval = 60;
    $form['refresh_interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Refresh Interval'),
      '#description' => $this->t('Time interval between refreshing an hours view (in seconds).'),
      '#default_value' => isset($refresh_interval) ? $refresh_interval / 1000 : $default_refresh_interval,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritDoc}
   */
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
