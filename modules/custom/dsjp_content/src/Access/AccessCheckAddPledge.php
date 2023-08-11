<?php

namespace Drupal\dsjp_content\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\dsjp_pledge\Service\GroupService;

/**
 * Builds an example page.
 */
class AccessCheckAddPledge implements AccessInterface {

  /**
   * The pledge service.
   *
   * @var \Drupal\dsjp_pledge\Service\GroupService
   */
  protected $pledgeService;

  /**
   * Constructor for the service.
   *
   * @param \Drupal\dsjp_pledge\Service\GroupService $pledgeService
   *   The pledge service.
   */
  public function __construct(GroupService $pledgeService) {
    $this->pledgeService = $pledgeService;
  }

  /**
   * Checks access to the mymodule page.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param string $node_type
   *   Return node type.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function access(AccountInterface $account, string $node_type) {
    $roles = $account->getRoles();
    if ($node_type != "dsj_pledge") {
      return AccessResult::allowed();
    }
    $membership = $this->pledgeService->userHasOrganizationGroupRole($account, [
      'dsj_organization-coordinator',
      'dsj_organization-admin',
    ]);
    if ($membership) {
      return AccessResult::allowed();
    }
    else {
      if (in_array('administrator', $roles) || in_array('dsj_reviewer', $roles)) {
        return AccessResult::allowed();
      }
      else {
        return AccessResult::forbidden();
      }
    }
  }

}
