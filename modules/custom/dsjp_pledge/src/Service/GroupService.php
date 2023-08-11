<?php

namespace Drupal\dsjp_pledge\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\dsjp_pledge\GroupServiceInterface;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\GroupMembershipLoaderInterface;

/**
 * Helper service for working with groups.
 */
class GroupService implements GroupServiceInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The group membership loader service property.
   *
   * @var \Drupal\group\GroupMembershipLoaderInterface
   */
  protected $groupMembershipLoader;

  /**
   * Constructs a GroupService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\group\GroupMembershipLoaderInterface $groupMembershipLoader
   *   The group membership loader service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, GroupMembershipLoaderInterface $groupMembershipLoader) {
    $this->entityTypeManager = $entityTypeManager;
    $this->groupMembershipLoader = $groupMembershipLoader;
  }

  /**
   * {@inheritDoc}
   */
  public function loadGroupByEntity(ContentEntityInterface $entity, string $type): ?GroupInterface {
    $group_contents = $this->entityTypeManager
      ->getStorage('group_content')
      ->loadByEntity($entity);
    if (!empty($group_contents)) {
      foreach ($group_contents as $entity) {
        if ($entity instanceof GroupContentInterface) {
          $group = $entity->getGroup();
          if ($group instanceof GroupInterface && $group->getGroupType()->id() == $type) {
            return $group;
          }
        }
      }
    }

    return NULL;
  }

  /**
   * {@inheritDoc}
   */
  public function loadMultipleGroupsByEntity(ContentEntityInterface $entity, string $type = ''): array {
    $group_contents = $this->entityTypeManager
      ->getStorage('group_content')
      ->loadByEntity($entity);
    $groups = [];

    if (!empty($group_contents)) {
      foreach ($group_contents as $entity) {
        if ($entity instanceof GroupContentInterface) {
          $group = $entity->getGroup();
          if ($group instanceof GroupInterface) {
            if (!empty($type) && $group->getGroupType()->id() == $type) {
              $groups[] = $group;
            }
            elseif (empty($type)) {
              $groups[] = $group;
            }
          }
        }
      }
    }

    return $groups;
  }

  /**
   * {@inheritDoc}
   */
  public function hasGroupByEntity(ContentEntityInterface $entity, string $type): bool {
    $group_contents = $this->entityTypeManager
      ->getStorage('group_content')
      ->loadByEntity($entity);
    if (!empty($group_contents)) {
      foreach ($group_contents as $entity) {
        if ($entity instanceof GroupContentInterface) {
          $group = $entity->getGroup();
          if ($group instanceof GroupInterface && $group->getGroupType()->id() == $type) {
            if ($type == $this::GROUP_TYPE_ORGANIZATION && $group->get('moderation_state')->value == 'rejected') {
              continue;
            }
            else {
              return TRUE;
            }
          }
        }
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMembershipByUser(AccountInterface $entity, string $type): ?GroupContentInterface {
    $memberships = $this->groupMembershipLoader->loadByUser($entity);
    if (!empty($memberships)) {
      foreach ($memberships as $membership) {
        $entity = $membership->getGroupContent();
        if ($entity instanceof GroupContentInterface) {
          $group = $entity->getGroup();
          if ($group instanceof GroupInterface && $group->getGroupType()->id() == $type) {
            if ($type == $this::GROUP_TYPE_ORGANIZATION && $group->get('moderation_state')->value == 'rejected') {
              continue;
            }
            else {
              return $entity;
            }
          }
        }
      }
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function userHasOrganizationGroupRole($account, $rolesToCheck, $forceRoles = NULL): bool {
    /** @var \Drupal\user\UserInterface $account */
    $account = $this->entityTypeManager->getStorage('user')->load($account->id());
    if (!empty($forceRoles)) {
      foreach ($forceRoles as $role) {
        if ($account->hasRole($role)) {
          return TRUE;
        }
      }
    }
    $membership = $this->loadMembershipByUser($account, GroupServiceInterface::GROUP_TYPE_ORGANIZATION);
    if (!empty($membership)) {
      $roles = $membership->get('group_roles')->getValue();
      if (!empty($roles)) {
        foreach ($roles as $role) {
          if (in_array($role['target_id'], $rolesToCheck)) {
            return TRUE;
          }
        }
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function userHasPendingInvitation($account, $type, $groupId) {
    $pluginId = "";
    if ($type == self::GROUP_TYPE_ORGANIZATION) {
      $pluginId = 'group_content_type_3c3fbd310e6d6';
    }
    $group_contents = $this->entityTypeManager
      ->getStorage('group_content')
      ->loadByEntity($account);
    foreach ($group_contents as $group_content) {
      if ($group_content->getGroup()->getGroupType()->id() == $type && $group_content->getGroupContentType()->id() == $pluginId
          && $group_content->getGroup()->id() == $groupId) {
        $status = $group_content->get('grequest_status')->value;
        if (!empty($status) && $status != 'rejected') {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

}
