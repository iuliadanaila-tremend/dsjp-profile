<?php

namespace Drupal\dsjp_pledge_migration\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the dsj_pledge_migration_connection queueworker.
 *
 * @QueueWorker(
 *   id = "dsj_pledge_migration_connection",
 *   title = @Translation("Connect pledges & organisations."),
 *   cron = {"time" = 5}
 * )
 */
class MigrationPledgeConnectionQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

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
    if (!empty($data['content']) && !empty($data['oid'])) {
      foreach ($data['content'] as $content) {
        if (!empty($content['pid'])) {
          $entity = $this->entityTypeManager->getStorage('node')->load($content['pid']);
          if ($entity instanceof NodeInterface) {
            $group = $this->entityTypeManager->getStorage('group')->load($data['oid']);
            if ($group instanceof GroupInterface) {
              $this->addGroupContent($group, $entity);
            }
          }
        }
      }
    }
  }

  /**
   * Adds pledges to a group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group object.
   * @param \Drupal\node\NodeInterface $entity
   *   The node object.
   */
  public function addGroupContent(GroupInterface $group, NodeInterface $entity) {
    $pluginId = 'group_node:' . $entity->getType();
    $relation = $group->getContentByEntityId($pluginId, $entity->id());
    if (!$relation) {
      $group->addContent($entity, $pluginId);
    }
  }

}
