<?php

namespace Drupal\dsjp_content\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the cta button block.
 *
 * @Block(
 *   id = "cta_button",
 *   admin_label = @Translation("Cta button"),
 * )
 */
class CtaNavigationButton extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The config facatory service property.
   *
   * @var \\Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * CtaNavigationButton constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory interface.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $configFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $configFactory;
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
  public function build() {
    $build = [];
    $config = $this->configFactory->get('dsjp_content.cta_config');
    if (!empty($config->get('cta_text')) && !empty($config->get('cta_url')) && $config->get('show_cta_button') == 1) {
      $build = [
        '#type' => 'link',
        '#title' => $config->get('cta_text'),
        '#url' => Url::fromUri($config->get('cta_url')),
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), ['cta_nav_button']);
  }

}
