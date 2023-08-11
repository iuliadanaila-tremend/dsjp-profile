<?php

namespace Drupal\dsjp_pledge_migration\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\group\Entity\GroupInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the dsj_pledge_user_fetcher queueworker.
 *
 * @QueueWorker(
 *   id = "dsj_pledge_user_fetcher",
 *   title = @Translation("Connect user & pledges."),
 *   cron = {"time" = 5}
 * )
 */
class MigrationPledgeQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $organisations = array_filter(array_column($data['data'], 'oid'));
    $this->setEntityOwner($organisations, 'group', $data['uid']);
    $pledges = array_filter(array_column($data['data'], 'pid'));
    $this->setEntityOwner($pledges, 'node', $data['uid']);
    $initiatives = array_filter(array_column($data['data'], 'piid'));
    $this->setEntityOwner($initiatives, 'node', $data['uid']);
  }

  /**
   * Sets the entity owner id.
   *
   * @param array $entities
   *   Array of entity ids.
   * @param string $entityType
   *   The entity type id.
   * @param mixed $uid
   *   The user id.
   */
  public function setEntityOwner(array $entities, $entityType, $uid) {
    foreach ($entities as $id) {
      if (!empty($id)) {
        $entity = $this->entityTypeManager->getStorage($entityType)->load($id);
        if ($entity instanceof EntityInterface) {
          $entity->setOwnerId($uid);
          // Use this variable for not sending
          // notifications when linking the author.
          $entity->isAutoAssign = TRUE;
          $entity->save();
          if ($entity instanceof GroupInterface) {
            $user = $this->entityTypeManager->getStorage('user')->load($uid);
            if ($user instanceof UserInterface) {
              $entity->addMember($user, ['group_roles' => 'dsj_organization-admin']);
            }
          }
        }
      }
    }
  }

}
