<?php

namespace Drupal\grequest;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\grequest\Plugin\GroupContentEnabler\GroupMembershipRequest;
use Drupal\group\Entity\GroupType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a helper class for updating status fields.
 */
class GroupMembershipRequestInstallHelper implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * InstallHelper constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Get a new status value.
   *
   * @param int $status
   *   Old status value.
   *
   * @return string
   *   New status value.
   */
  public function getStatus($status) {
    switch ($status) {
      case 0:
        return GroupMembershipRequest::REQUEST_PENDING;

      case 1:
        return GroupMembershipRequest::REQUEST_APPROVED;

      case 2:
        return GroupMembershipRequest::REQUEST_REJECTED;
    }

    return NULL;
  }

  /**
   * Get roles list for group type.
   *
   * @param \Drupal\group\Entity\GroupType $group_type
   *   Group type.
   *
   * @return array
   *   List of roles.
   */
  public function getRoles(GroupType $group_type) {
    $storage = $this->entityTypeManager->getStorage('group_role');
    $group_type_id = $group_type->id();
    $properties = [
      'group_type' => $group_type_id,
      'permissions_ui' => TRUE,
    ];

    $roles = $storage->loadByProperties($properties);

    $outsider_roles = $storage->loadSynchronizedByGroupTypes([$group_type_id]);
    return array_merge($roles, $outsider_roles);
  }

}
