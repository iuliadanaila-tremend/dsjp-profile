<?php

namespace Drupal\dsjp_pledge\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\dsjp_pledge\GroupServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides specific access control for the user entity type.
 *
 * @EntityReferenceSelection(
 *   id = "default:user_pledge_organization",
 *   label = @Translation("User organization selection"),
 *   entity_types = {"user"},
 *   group = "default",
 *   weight = 1
 * )
 */
class UserPledgeOrganization extends DefaultSelection {

  /**
   * The group service property.
   *
   * @var \Drupal\dsjp_pledge\GroupServiceInterface
   */
  protected $groupService;

  /**
   * The database connection service property.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->groupService = $container->get('dsjp_pledge.group_service');
    $instance->database = $container->get('database');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $query = $this->database->select('users_field_data', 'u')
      ->fields('u', ['uid'])
      ->where("CONCAT_WS(' ', u.field_dsj_firstname, ' ', u.field_dsj_lastname) LIKE :input", [
        ':input' => '%' . \Drupal::database()->escapeLike($match) . '%',
      ])
      ->condition('u.uid', $this->currentUser->id(), '!=')
      ->range(0, 10)
      ->execute();
    $result = $query->fetchCol();
    if (empty($result)) {
      return [];
    }

    $options = [];
    $entities = $this->entityTypeManager->getStorage('user')->loadMultiple($result);
    foreach ($entities as $entity_id => $entity) {
      $isMember = $this->groupService->hasGroupByEntity($entity, GroupServiceInterface::GROUP_TYPE_ORGANIZATION);
      if (!$isMember) {
        $firstname = $entity->get('field_dsj_firstname')->getString();
        $lastname = $entity->get('field_dsj_lastname')->getString();
        $bundle = $entity->bundle();

        $options[$bundle][$entity_id] = $firstname . ' ' . $lastname;
      }
    }

    return $options;
  }

}
