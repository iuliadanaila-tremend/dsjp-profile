<?php

namespace Drupal\dsjp_pledge;

use Drupal\Core\Entity\ContentEntityInterface;
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

}
