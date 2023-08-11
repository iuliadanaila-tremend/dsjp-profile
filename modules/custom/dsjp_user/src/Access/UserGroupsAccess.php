<?php

namespace Drupal\dsjp_user\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides access check for the user groups page.
 */
class UserGroupsAccess implements ContainerInjectionInterface {
  /**
   * Route match interface.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * UserGroupsAccess constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   Route match interface.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(RouteMatchInterface $routeMatch, EntityTypeManagerInterface $entityTypeManager) {

    $this->routeMatch = $routeMatch;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Check if current user is the same as the user groups page parameter.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current logged-in user.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access check result.
   */
  public function checkAccess(AccountInterface $account) {
    return AccessResult::allowedIf(
      $account->id() == $this->routeMatch->getRawParameter('user') ||
      in_array('administrator', $account->getRoles()));
  }

  /**
   * Check access for admin and community manager roles.
   */
  public function checkAccessAdminCommunityManager(AccountInterface $account) {
    return AccessResult::allowedIf(
      $account->id() == $this->routeMatch->getRawParameter('user') ||
      in_array('administrator', $account->getRoles()) || in_array('community_manager', $account->getRoles()));
  }

  /**
   * Check access for community manager roles.
   */
  public function checkAccessCommunityManager(AccountInterface $account) {
    return AccessResult::allowedIf(
      $account->id() == $this->routeMatch->getRawParameter('user') ||
      in_array('community_manager', $account->getRoles()));
  }

  /**
   * Add custom rules for group type organisation.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current logged-in user.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access check result.
   */
  public function checkOrganisationAccess(AccountInterface $account) {
    $groupId = $this->routeMatch->getRawParameter('group');
    if ($groupId) {
      $entityObj = $this->entityTypeManager->getStorage('group')->load($groupId);
      if ($entityObj && $entityObj->bundle() == 'dsj_organization') {
        return AccessResult::allowedIf(
          (in_array('administrator', $account->getRoles()) || in_array('community_manager', $account->getRoles())));
      }
    }
    return AccessResult::allowed();
  }

}
