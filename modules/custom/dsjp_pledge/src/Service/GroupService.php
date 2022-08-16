<?php

namespace Drupal\dsjp_pledge\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\dsjp_pledge\GroupServiceInterface;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Entity\GroupInterface;

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
   * Constructs a GroupService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
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

}
