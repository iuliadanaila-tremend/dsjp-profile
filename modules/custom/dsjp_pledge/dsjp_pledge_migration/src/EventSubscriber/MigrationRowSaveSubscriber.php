<?php

namespace Drupal\dsjp_pledge_migration\EventSubscriber;

use Drupal\Core\Database\Connection;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for migration node post save.
 */
class MigrationRowSaveSubscriber implements EventSubscriberInterface {

  /**
   * The database service property.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * MigrationRowSaveSubscriber constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection service.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
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
    $data = [];
    $migration = $event->getMigration();
    // Check if the migration is from the dsj_coalition module.
    switch ($migration->id()) {
      case 'dsj_pledge':
        $source = $event->getRow()->getSource();
        $data = [
          'mail' => $source['mail'],
          'pid' => $event->getDestinationIdValues()[0],
        ];
        break;

      case 'dsj_pledge_initiative':
        $source = $event->getRow()->getSource();
        $data = [
          'mail' => $source['mail'],
          'piid' => $event->getDestinationIdValues()[0],
        ];
        break;

      case 'dsj_pledge_organisation':
        $source = $event->getRow()->getSource();
        $data = [
          'mail' => $source['mail'],
          'oid' => $event->getDestinationIdValues()[0],
        ];
        break;
    }
    if (!empty($data)) {
      $this->database->insert('dsj_pledge_users')
        ->fields($data)->execute();
    }
  }

}
