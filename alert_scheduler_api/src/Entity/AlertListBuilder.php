<?php

namespace Drupal\alert_scheduler_api\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\lib_unb_custom_entity\Entity\EntityListBuilder;

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
    return [
      'title' => $this->t('Title'),
      'interval' => $this->t('Scheduled for'),
    ] + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $alert \Drupal\alert_scheduler_api\Entity\AlertInterface */
    $alert = $entity;

    $start = $alert->getInterval()->start()->format('Y-m-d h:i');
    $end = $alert->getInterval()->end()->format('Y-m-d h:i');

    return [
        'title' => $alert->label(),
        'interval' => "{$start} - {$end}",
      ] + parent::buildRow($entity);
  }

  /**
   * {@inheritDoc}
   */
  protected function buildCreateAction() {
    return parent::buildCreateAction() + [
        '#attributes' => [
          'class' => [
            'btn',
            'btn-success',
            'mb-3',
          ],
        ],
      ];
  }

}
