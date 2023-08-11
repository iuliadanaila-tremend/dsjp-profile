<?php

namespace Drupal\dsjp_pledge_migration\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate_tools\MigrateExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the dsj_pledge_user_fetcher queueworker.
 *
 * @QueueWorker(
 *   id = "dsjp_pledge_migration_import_queue",
 *   title = @Translation("Migrates pledge data."),
 *   cron = {"time" = 5}
 * )
 */
class MigrationPledgeImportQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The migration manager service property.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManager
   */
  protected $migrationManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->migrationManager = $container->get('plugin.manager.migration');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $migration = $this->migrationManager->createInstance($data['id'],
      [
        'source' => [
          'path' => $data['path'],
          'constants' => [
            'path_to_csv' => $data['path'],
            'file_location' => $data['file_location'] ?: '',
            'file_destination' => $data['file_destination'] ?: '',
          ],
        ],
      ]);
    $executable = new MigrateExecutable($migration, new MigrateMessage(), [
      'update' => 1,
    ]);
    $executable->import();
  }

}
