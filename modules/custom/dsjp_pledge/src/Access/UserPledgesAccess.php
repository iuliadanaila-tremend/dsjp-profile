<?php

namespace Drupal\dsjp_pledge\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\dsjp_pledge\Service\GroupService;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides access functionality for pledge related functionality.
 */
class UserPledgesAccess implements ContainerInjectionInterface {

  /**
   * Route match interface.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface   *
   */
  private $routeMatch;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * Dsjp Pledge group service.
   *
   * @var \Drupal\dsjp_pledge\Service\GroupService
   */
  private $groupService;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private $currentUser;

  /**
   * UserGroupsAccess constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   Route match interface.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Current user.
   * @param \Drupal\dsjp_pledge\Service\GroupService $groupService
   *   Group service.
   */
  public function __construct(RouteMatchInterface $routeMatch, EntityTypeManagerInterface $entityTypeManager, AccountProxyInterface $currentUser, GroupService $groupService) {

    $this->routeMatch = $routeMatch;
    $this->entityTypeManager = $entityTypeManager;
    $this->groupService = $groupService;
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('dsjp_pledge.group_service')
    );
  }

  /**
   * Custom access checker for pledge pages.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current logged in user.
   *
   * @return \Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden
   *   Returns the access result based on user roles.
   */
  public function checkRoleAccess(AccountInterface $account) {
    if ($account->id() != $this->routeMatch->getRawParameter('user')) {
      return AccessResult::forbidden();
    }
    $membership = $this->groupService->userHasOrganizationGroupRole($account, [
      'dsj_organization-coordinator',
      'dsj_organization-admin',
    ]);
    if ($membership) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

  /**
   * Custom access checker for pledge pages.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current logged in user.
   *
   * @return \Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden
   *   Returns the access result based on user roles.
   */
  public function checkGeneralPageAccess(AccountInterface $account) {
    if ($account->id() != $this->routeMatch->getRawParameter('user') || !array_intersect('dsj_reviewer', $this->currentUser->getRoles())) {
      return AccessResult::forbidden();
    }
    return AccessResult::allowed();
  }

  /**
   * Custom access for pledges page based on group type.
   *
   * @return \Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden
   *   Returns the access result based on group type.
   */
  public function checkGroupNodeAccess() {
    // Hide pledges page on groups for non organizations.
    $group = $this->routeMatch->getParameter('group');
    if ($group instanceof GroupInterface && $group->bundle() != 'dsj_organization') {
      return AccessResult::forbidden();
    }

    return AccessResult::allowed();
  }

}
