<?php

namespace Drupal\alert_scheduler_api\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\datetime_plus\Datetime\DateIntervalPlus;
use Drupal\datetime_plus\DependencyInjection\StorageTimeTrait;
use Drupal\datetime_plus\DependencyInjection\SystemTimeTrait;

/**
 * Defines the Alert entity.
 *
 * @ingroup alert_scheduler
 *
 * @ContentEntityType(
 *   id = "scheduled_alert",
 *   label = @Translation("Banner alert"),
 *   label_plural = @Translation("Banner alerts"),
 *   label_collection = @Translation("Banner alerts"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\alert_scheduler_api\Entity\AlertListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "add" = "Drupal\alert_scheduler_api\Form\AlertForm",
 *       "edit" = "Drupal\alert_scheduler_api\Form\AlertForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\lib_unb_custom_entity\Entity\EntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "\Drupal\lib_unb_custom_entity\Entity\Routing\HtmlRouteProvider",
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
 *     "collection" = "/alert-scheduler/alerts",
 *     "add-form" = "/alert-scheduler/alerts/add",
 *     "edit-form" = "/alert-scheduler/alerts/{scheduled_alert}/edit",
 *     "delete-form" = "/alert-scheduler/alerts/{scheduled_alert}/delete",
 *   }
 * )
 */
class Alert extends ContentEntityBase implements AlertInterface {

  use EntityChangedTrait;
  use SystemTimeTrait;
  use StorageTimeTrait;

  /**
   * The interval during which the alert is visible.
   *
   * @var \Drupal\datetime_plus\Datetime\DateIntervalPlus
   */
  protected $interval;

  public function getTitle() {
    return $this->get('title')->value;
  }

  public function getMessage() {
    return $this->get('body')->value;
  }

  /**
   * {@inheritDoc}
   */
  public function getInterval() {
    if (!isset($this->interval) || is_array($this->interval)) {
      $start = static::storageTime()
        ->createFromFormat(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $this->get('interval')->value)
        ->setTimezone(static::systemTime()->getTimeZone());
      $end = static::storageTime()
        ->createFromFormat(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $this->get('interval')->end_value)
        ->setTimezone(static::systemTime()->getTimeZone());
      $this->interval = new DateIntervalPlus($start, $end);
    }
    return $this->interval;
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
