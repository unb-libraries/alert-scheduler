<?php

namespace Drupal\alert_scheduler_api;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Alert entity.
 *
 * @see \Drupal\alert_scheduler_api\Entity\Alert.
 */
class AlertAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view scheduled alert entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit scheduled alert entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete scheduled alert entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add scheduled alert entities');
  }

}
