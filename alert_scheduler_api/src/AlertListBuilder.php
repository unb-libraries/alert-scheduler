<?php

namespace Drupal\alert_scheduler_api;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines a class to build a listing of Alert entities.
 *
 * @ingroup alert_scheduler
 */
class AlertListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = $this->t('Title');
    $header['interval'] = $this->t('Scheduled for');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\alert_scheduler_api\Entity\Alert */
    $row['title'] = $entity->label();
    $row['interval'] = sprintf('%s - %s',
      $entity->isVisibleFrom()->format('Y-m-d h:i'),
      $entity->isVisibleUntil()->format('Y-m-d h:i'));

    return $row + parent::buildRow($entity);
  }

}
