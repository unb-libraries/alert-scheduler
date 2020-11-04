<?php

namespace Drupal\alert_scheduler_api\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for "scheduled_alert" entities.
 *
 * @package Drupal\alert_scheduler_api\Entity
 */
interface AlertInterface extends ContentEntityInterface {

  /**
   * Retrieve the alert title.
   *
   * @return string
   *   A string.
   */
  public function getTitle();

  /**
   * Retrieve the alert message.
   *
   * @return string
   *   A string.
   */
  public function getMessage();

  /**
   * Retrieve the interval during which the alert is visible.
   *
   * @return \Drupal\datetime_plus\Datetime\DateIntervalPlus
   *   A date interval object.
   */
  public function getInterval();

}
