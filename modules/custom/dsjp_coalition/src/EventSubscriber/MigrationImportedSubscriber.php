<?php

namespace Drupal\dsjp_coalition\EventSubscriber;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupInterface;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides an event subscriber for the migration post import event.
 */
class MigrationImportedSubscriber implements EventSubscriberInterface {

  /**
   * The date formatter service property.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * MigrationImportedSubscriber constructor.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter service.
   */
  public function __construct(DateFormatterInterface $dateFormatter) {
    $this->dateFormatter = $dateFormatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      MigrateEvents::POST_IMPORT => 'onPostImport',
    ];
  }

  /**
   * Triggers the migration post import event.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The migration event.
   */
  public function onPostImport(MigrateImportEvent $event) {
    if (strpos($event->getMigration()->id(), 'dsj_coalition_') !== FALSE) {
      $sourceConfig = $event->getMigration()->getSourceConfiguration();
      $gid = $sourceConfig['constants']['group_id'];
      $group = Group::load($gid);
      if (!$group instanceof GroupInterface) {
        return;
      }
      $destination = $event->getMigration()->getDestinationConfiguration();
      $bundle = $destination['default_bundle'] == 'dsj_digital_skills_resource' ? 'dsj_digital_skills' : $destination['default_bundle'];
      $field = 'field_' . $bundle . '_id';
      $datetime = new \DateTime('now', new \DateTimeZone('GMT'));
      $dateFormatted = $this->dateFormatter
        ->format($datetime->getTimestamp(), 'custom', 'Y-m-d\TH:i:s');
      $group->set($field, $dateFormatted);
      $group->save();
    }
  }

}
