<?php

namespace Drupal\activitystream;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for Activity Stream item entities.
 */
class ActivityStreamItemAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    if ($account->hasPermission('administer activitystream')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $service = $entity->get('service')->value ?? '';

    if ($operation === 'view') {
      return AccessResult::allowedIfHasPermission($account, "view any $service activitystream")
        ->cachePerPermissions();
    }

    return AccessResult::allowedIfHasPermission($account, "edit any $service activitystream")
      ->cachePerPermissions();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResultInterface {
    return AccessResult::allowedIfHasPermission($account, 'administer activitystream')
      ->cachePerPermissions();
  }

}
