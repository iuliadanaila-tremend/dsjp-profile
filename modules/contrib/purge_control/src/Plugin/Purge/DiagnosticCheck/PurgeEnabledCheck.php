<?php

namespace Drupal\purge_control\Plugin\Purge\DiagnosticCheck;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\purge_control\Form\PurgeControlSettings;

/**
 * Checks if Purging is enabled or not.
 *
 * @PurgeDiagnosticCheck(
 *   id = "purge_enabled",
 *   title = @Translation("Purge enabled."),
 *   description = @Translation("Checks to see if the puring is enabled."),
 *   dependent_queue_plugins = {},
 *   dependent_purger_plugins = {}
 * )
 */
class PurgeEnabledCheck extends DiagnosticCheckBase {
  /**
   * The settings configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Constructs a PurgeEnabledCheck object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The factory for configuration objects.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->config = $config->get(PurgeControlSettings::SETTINGS);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    if ($this->config->get('disable_purge') == TRUE) {
      $this->recommendation = $this->t('Purging is disabled.');
      return self::SEVERITY_ERROR;
    }

    $this->recommendation = $this->t('Purging is enabled.');
    return self::SEVERITY_OK;
  }

}
