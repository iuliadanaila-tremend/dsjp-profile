<?php

namespace Drupal\dsjp_pledge;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Interface to describe of service for working with groups.
 */
interface GroupServiceInterface {

  const GROUP_TYPE_ORGANIZATION = 'dsj_organization';
  const GROUP_TYPE_NATIONAL_COALITION = 'dsj_national_coalition';

  /**
   * Load Group by entity with type.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   An entity which may be within one or more groups.
   * @param string $type
   *   Name of group type.
   *
   * @return \Drupal\group\Entity\GroupInterface|null
   *   Return single group if it exists.
   */
  public function loadGroupByEntity(ContentEntityInterface $entity, string $type): ?GroupInterface;

  /**
   * Load multiple Group by entity with type.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   An entity which may be within one or more groups.
   * @param string $type
   *   Name of group type.
   *
   * @return \Drupal\group\Entity\GroupInterface[]
   *   Return array of GroupInterface entities.
   */
  public function loadMultipleGroupsByEntity(ContentEntityInterface $entity, string $type = ''): array;

  /**
   * Check if entity has a group with type.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   An entity which may be within one or more groups.
   * @param string $type
   *   Name of group type.
   *
   * @return bool
   *   Return TRUE if a Group exists.
   */
  public function hasGroupByEntity(ContentEntityInterface $entity, string $type): bool;

  /**
   * Load group content entity by a group type.
   *
   * @param \Drupal\Core\Session\AccountInterface $entity
   *   The account to load by.
   * @param string $type
   *   The group type.
   *
   * @return \Drupal\group\Entity\GroupContentInterface|null
   *   Returns the group content entity on success, NULL otherwise.
   */
  public function loadMembershipByUser(AccountInterface $entity, string $type): ?GroupContentInterface;

  /**
   * Checks if a users has organizations roles needed.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user to check the roles for.
   * @param array $rolesToCheck
   *   The role(s) to check.
   * @param array|null $forceRoles
   *   List roles to force return TRUE if the current user has someone.
   *
   * @return bool
   *   Return TRUE if any role is found.
   */
  public function userHasOrganizationGroupRole(AccountInterface $account, array $rolesToCheck, array $forceRoles = NULL): bool;

  /**
   * Checks if a user has a pending group invitation.
   *
   * @param \Drupal\Core\Session\AccountInterface $entity
   *   The account to load by.
   * @param string $type
   *   The group type.
   * @param int $groupId
   *   The group id.
   *
   * @return bool
   *   Return TRUE if any role is found.
   */
  public function userHasPendingInvitation(AccountInterface $entity, string $type, int $groupId);

}
