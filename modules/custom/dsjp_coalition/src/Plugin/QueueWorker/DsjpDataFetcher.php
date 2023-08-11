<?php

namespace Drupal\dsjp_coalition\Plugin\QueueWorker;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\dsjp_coalition\DanishMigrationService;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the dsj_data_fetcher queueworker.
 *
 * @QueueWorker(
 *   id = "dsj_data_fetcher",
 *   title = @Translation("Call National Coalition API to get latest data."),
 *   cron = {"time" = 5}
 * )
 */
class DsjpDataFetcher extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The http client service.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The queue factory service.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queue;

  /**
   * The json serialization service.
   *
   * @var \Drupal\Component\Serialization\Json
   */
  protected $json;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The migration plugin manager service.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManager
   */
  protected $migrationManager;

  /**
   * The current page.
   *
   * @var int
   */
  protected $page;

  /**
   * The number of pages available.
   *
   * @var int
   */
  protected $totalPages;

  /**
   * The migration service.
   *
   * @var \Drupal\dsjp_coalition\MigrationServiceInterface
   */
  protected $migrationService;

  /**
   * The Drupal migration service.
   *
   * @var \Drupal\dsjp_coalition\DrupalMigrationService
   */
  protected $drupalService;

  /**
   * The wordpress migration service.
   *
   * @var \Drupal\dsjp_coalition\WordpressMigrationService
   */
  protected $wordpressService;

  /**
   * The slovenian service property.
   *
   * @var \Drupal\dsjp_coalition\SloveniaMigrationService
   */
  protected $sloveniaService;

  /**
   * The austrian migration service.
   *
   * @var \Drupal\dsjp_coalition\AustriaMigrationService
   */
  protected $austriaService;

  /**
   * The sweden migration service.
   *
   * @var \Drupal\dsjp_coalition\SwedenMigrationService
   */
  protected $swedenService;

  /**
   * The danish migration service.
   *
   * @var \Drupal\dsjp_coalition\DanishMigrationService
   */
  protected $danishService;

  /**
   * The portugal migration service.
   *
   * @var \Drupal\dsjp_coalition\PortugalMigrationService
   */
  protected $portugalService;

  /**
   * Date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition);
    $instance->httpClient = $container->get('http_client');
    $instance->json = $container->get('serialization.json');
    $instance->logger = $container->get('logger.factory');
    $instance->migrationManager = $container->get('plugin.manager.migration');
    $instance->dateFormatter = $container->get('date.formatter');
    $instance->drupalService = $container->get('dsjp_coalition.migration_service_drupal');
    $instance->wordpressService = $container->get('dsjp_coalition.migration_service_wordpress');
    $instance->austriaService = $container->get('dsjp_coalition.migration_service_austria');
    $instance->sloveniaService = $container->get('dsjp_coalition.migration_service_slovenia');
    $instance->swedenService = $container->get('dsjp_coalition.migration_service_sweden');
    $instance->danishService = $container->get('dsjp_coalition.migration_service_danish');
    $instance->portugalService = $container->get('dsjp_coalition.migration_service_portugal');

    return $instance;
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   */
  public function processItem($data) {
    switch ($data['source_type']) {
      case 'drupal':
        $this->migrationService = $this->drupalService;
        break;

      case 'wordpress':
        $this->migrationService = $this->wordpressService;
        break;

      case 'austria':
        $this->migrationService = $this->austriaService;
        break;

      case 'slovenia':
        $this->migrationService = $this->sloveniaService;
        break;

      case 'sweden':
        $this->migrationService = $this->swedenService;
        break;

      case 'danish':
        $this->migrationService = $this->danishService;
        break;

      case 'portugal':
        $this->migrationService = $this->portugalService;
        break;
    }
    $this->totalPages = $this->migrationService->getTotalPages($data);
    // Get number of pages we have to call.
    if ($this->totalPages > 0) {
      $page = 1;
      do {
        $dataArr = $this->migrationService->fetchData($page, $data);
        $this->executeMigration($dataArr, $data);

        // Don't run the migration if we found an already imported content.
        if ($this->migrationService instanceof DanishMigrationService) {
          $hasImportData = array_search($this->dateFormatter->format((int) $data['last_imported'], 'custom', 'Y-m-d\TH:i:s\Z'), array_column($dataArr, 'Modified'));
          if ($hasImportData != FALSE) {
            $page = $this->totalPages;
          }
        }
        $page++;
      } while ($page <= $this->totalPages);
    }
  }

  /**
   * Executes the migration.
   *
   * @param array $data
   *   Data to pass to migration.
   * @param array $coalitionData
   *   The coalition details.
   */
  protected function executeMigration(array $data, array $coalitionData) {
    $now = time();
    $constants = [
      'group_id' => $coalitionData['group_id'],
      'group_owner' => $coalitionData['group_owner'],
      'language' => $coalitionData['default_language'],
      'file_destination' => 'public://' . $coalitionData['to_content_type'] . '/' . $this->dateFormatter->format($now, 'custom', 'Y') . '-' . $this->dateFormatter->format($now, 'custom', 'm') . '/',
      'file_location' => $this->migrationService->getBasePath($coalitionData['api_base_url']),
      'news_article_type' => $coalitionData['news_article_type'],
    ];
    try {
      foreach ($data as &$item) {
        $item['gid'] = $constants['group_id'];
      }

      $migration = $this->migrationManager->createInstance('dsj_coalition_' . $coalitionData['source_type'] . '_' . $coalitionData['to_content_type'],
        [
          'source' => [
            'data_rows' => $data,
            'constants' => $constants,
          ],
        ]);
      $executable = new MigrateExecutable($migration, new MigrateMessage());
      $executable->import();
    }
    catch (MigrateException $e) {
      $this->logger->get('dsjp_coalition')->error($e->getMessage());
    }
    catch (PluginException $e) {
      $this->logger->get('dsjp_coalition')->error($e->getMessage());
    }
  }

}
