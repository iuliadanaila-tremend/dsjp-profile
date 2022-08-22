<?php

namespace Drupal\dsjp_coalition\EventSubscriber;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for migration node post save.
 */
class NodeMigrationSaveSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * NodeMigrationSaveSubscriber constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager interface.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   The logger channel.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, LoggerChannelFactoryInterface $loggerChannelFactory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $loggerChannelFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[MigrateEvents::POST_ROW_SAVE][] = ['onPostRowSave'];

    return $events;
  }

  /**
   * Save the node in a group if it is a coalition migration.
   *
   * @param \Drupal\migrate\Event\MigratePostRowSaveEvent $event
   *   The import event object.
   */
  public function onPostRowSave(MigratePostRowSaveEvent $event) {
    $migration = $event->getMigration();
    // Check if the migration is from the dsj_coalition module.
    if (strpos($migration->getBaseId(), 'dsj_coalition_') === 0) {
      $nid = $event->getDestinationIdValues();
      $source = $migration->getSourceConfiguration();
      $groupId = $source['constants']['group_id'];
      // After getting the group id and nid from the migration configuration,
      // load them and save the node on the group.
      if (!empty($groupId)) {
        try {
          $group = $this->entityTypeManager->getStorage('group')->load($groupId);
          if ($group instanceof GroupInterface) {
            $node = $this->entityTypeManager->getStorage('node')->load(reset($nid));
            if ($node instanceof NodeInterface) {
              $group->addContent($node, 'group_node:' . $migration->getDestinationConfiguration()['default_bundle']);
            }
          }
        }
        catch (InvalidPluginDefinitionException $e) {
          $this->logger->get('dsj_coalition')->error($e->getMessage());
        }
        catch (PluginNotFoundException $e) {
          $this->logger->get('dsj_coalition')->error($e->getMessage());
        }
      }
    }
  }

}
