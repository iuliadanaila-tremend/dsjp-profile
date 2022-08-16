<?php

namespace Drupal\dsjp_translation\Plugin\tmgmt\Translator;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\dsjp_translation\Etranslation;
use Drupal\message_notify\MessageNotifier;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\Translator\AvailableResult;
use Drupal\tmgmt\TranslatorInterface;
use Drupal\tmgmt\TranslatorPluginBase;
use Drupal\tmgmt_local\LocalTaskInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Translator using the eTranslation service.
 *
 * @TranslatorPlugin(
 *   id = "etranslation",
 *   label = @Translation("eTranslation"),
 *   description = @Translation("Allows the users to send translation requests to eTranslation."),
 *   ui = "\Drupal\dsjp_translation\EtranslationUI",
 *   default_settings = {},
 *   map_remote_languages = TRUE
 * )
 */
class EtranslationTranslator extends TranslatorPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The config factory service property.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger channger factory service property.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The etranslation service property.
   *
   * @var \Drupal\dsjp_translation\Etranslation
   */
  protected $eTranslation;

  /**
   * The entity type manager service property.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Message notified service property.
   *
   * @var \Drupal\message_notify\MessageNotifier
   */
  protected $messageNotifier;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('logger.factory'),
      $container->get('dsjp_translation.etranslation'),
      $container->get('entity_type.manager'),
      $container->get('message_notify.sender')
    );
  }

  /**
   * EtranslationTranslator constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   The logger channel service.
   * @param \Drupal\dsjp_translation\Etranslation $eTranslation
   *   The etranslation service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\message_notify\MessageNotifier $messageNotifier
   *   The message notifier service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactoryInterface $configFactory,
    LoggerChannelFactoryInterface $loggerChannelFactory,
    Etranslation $eTranslation,
    EntityTypeManagerInterface $entityTypeManager,
    MessageNotifier $messageNotifier
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $configFactory;
    $this->logger = $loggerChannelFactory;
    $this->eTranslation = $eTranslation;
    $this->entityTypeManager = $entityTypeManager;
    $this->messageNotifier = $messageNotifier;
  }

  /**
   * {@inheritdoc}
   */
  public function checkAvailable(TranslatorInterface $translator) {
    $config = $this->configFactory->get('tmgmt.translator.etranslation')->get('settings');
    $available = !empty($config['credentials']['username']) && !empty($config['credentials']['password']) && !empty($config['endpoint']);

    return $available ? AvailableResult::yes() : AvailableResult::no($this->t('Please provide the plugin settings <a href=":url">here</a>.', [
      ':url' => Url::fromRoute('entity.tmgmt_translator.edit_form', [
        'tmgmt_translator' => 'etranslation',
      ])->toString(),
    ]));
  }

  /**
   * {@inheritdoc}
   */
  public function requestTranslation(JobInterface $job) {
    $config = $this->configFactory->get('tmgmt.translator.etranslation')->get('settings');
    if (!empty($config['credentials']['username']) && !empty($config['credentials']['password']) && !empty($config['endpoint'])) {
      $items = $job->getItems();
      $this->requestJobItemTranslation($items, $config);

      $job->submitted();
    }
  }

  /**
   * Requests a new translation via eTranslation service.
   *
   * @param array $items
   *   The items to translate.
   * @param array $settings
   *   The eTranslation plugin settings.
   */
  protected function requestJobItemTranslation(array $items, array $settings) {
    /** @var \Drupal\tmgmt\Entity\Job $job */
    $job = reset($items)->getJob();
    $translated = $this->eTranslation->requestTranslation($items, $settings);
    $tuid = $job->getSetting('translator');

    // Create local task for this job.
    $local_task = $this->entityTypeManager->getStorage('tmgmt_local_task')->create([
      'uid' => $job->getOwnerId(),
      'tuid' => $tuid,
      'tjid' => $job->id(),
      'title' => $job->label(),
    ]);
    if ($local_task instanceof LocalTaskInterface) {
      // If we have translator then switch to pending state.
      if ($tuid) {
        $local_task->status = LocalTaskInterface::STATUS_PENDING;
      }
      $local_task->save();

      // Create task items.
      /** @var \Drupal\tmgmt\JobItemInterface $item */
      foreach ($items as $item) {
        if (isset($translated[$item->id()]) && $translated[$item->id()] == TRUE) {
          $local_task->addTaskItem($item);
          $item->active();
        }
      }
    }
  }

}
