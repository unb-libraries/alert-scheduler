<?php

namespace Drupal\alert_scheduler_api\Entity;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Alert entity.
 *
 * @ingroup alert_scheduler
 *
 * @ContentEntityType(
 *   id = "scheduled_alert",
 *   label = @Translation("Alert"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\alert_scheduler_api\AlertListBuilder",
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "add" = "Drupal\alert_scheduler_api\Form\AlertForm",
 *       "edit" = "Drupal\alert_scheduler_api\Form\AlertForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\alert_scheduler_api\AlertAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "\Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "scheduled_alert",
 *   admin_permission = "administer alerts",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "add-form" = "/alert-scheduler/alerts/create",
 *     "edit-form" = "/alert-scheduler/alerts/{scheduled_alert}/edit",
 *     "delete-form" = "/alert-scheduler/alerts/{scheduled_alert}/delete",
 *     "collection" = "/alert-scheduler/alerts",
 *   }
 * )
 */
class Alert extends ContentEntityBase {

  use EntityChangedTrait;

  protected $timezoneCorrectedInterval;

  public function getTitle() {
    return $this->get('title')->value;
  }

  public function getMessage() {
    return $this->get('body')->value;
  }

  public function isVisibleFrom() {
    if (!isset($this->timezoneCorrectedInterval)) {
      $this->timezoneCorrectedInterval = $this->getScheduledInterval();
    }
    return $this->timezoneCorrectedInterval['from'];
  }

  public function isVisibleUntil() {
    if (!isset($this->timezoneCorrectedInterval)) {
      $this->timezoneCorrectedInterval = $this->getScheduledInterval();
    }
    return $this->timezoneCorrectedInterval['to'];
  }

  protected function getScheduledInterval() {
    try {
      $timezone = new \DateTimeZone(\Drupal::currentUser()->getTimeZone());
    }
    catch (\Exception $e) {
      $timezone = new \DateTimeZone(\Drupal::config('system.date')->get('timezone')['default']);
    }
    $interval = $this->get('interval')->first();
    return [
      'from' => (new DrupalDateTime($interval->value, 'UTC'))->setTimezone($timezone),
      'to' => (new DrupalDateTime($interval->end_value, 'UTC'))->setTimezone($timezone),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the Alert.'))
      ->setRequired(TRUE)
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'weight' => 0,
      ]);

    $fields['body'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Message'))
      ->setDescription(t('Content of the alert.'))
      ->setRequired(FALSE)
      ->setDisplayOptions('form', [
        'weight' => 1,
      ]);

    $fields['interval'] = BaseFieldDefinition::create('daterange')
      ->setLabel(t('Publish on'))
      ->setDescription(t('Time interval during which the alert will be visible.'))
      ->setDisplayOptions('form', [
        'weight' => 3,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
