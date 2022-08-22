<?php

namespace Drupal\dsjp_community\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;
use Drupal\ckeditor\CKEditorPluginContextualInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactory;

/**
 * Defines the "Mentions Autocomplete" plugin.
 *
 * @CKEditorPlugin(
 *   id = "mentionsautocomplete",
 *   label = @Translation("Mentions Autocomplete")
 * )
 */
class MentionsAutocomplete extends CKEditorPluginBase implements CKEditorPluginContextualInterface, ContainerFactoryPluginInterface {

  /**
   * The config service.
   *
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * MentionsAutocomplete constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   The config factory service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactory $config) {
    $this->config = $config->get('mentions.settings');
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $config = $container->get('config.factory');

    return new static($configuration,
      $plugin_id,
      $plugin_definition,
      $config
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'mentionsautocomplete' => [
        'label' => $this->t('Mentions'),
        'image' => drupal_get_path('module', 'mentions') . '/mentions.png',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [
      'core/drupal.ajax',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'dsjp_community') . '/js/plugins/mentionsautocomplete/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(Editor $editor) {
    $enabled = $this->config->get('ckeditor.autocomplete');
    return $enabled != 0;
  }

}
