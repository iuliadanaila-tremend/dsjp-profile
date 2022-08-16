<?php

namespace Drupal\dsjp_user\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides access check for the user groups page.
 */
class UserGroupsAccess {

  /**
   * Check if current user is the same as the user groups page parameter.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current logged in user.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function checkAccess(AccountInterface $account) {
    return AccessResult::allowedIf(
      $account->id() == \Drupal::routeMatch()->getRawParameter('user') ||
      in_array('administrator', $account->getRoles()));
  }

}
