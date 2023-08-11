<?php

namespace Drupal\dsjp_content\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginContextualInterface;
use Drupal\Core\Asset\LibraryDiscovery;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\editor\Entity\Editor;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the CKEditor Editor Placeholder plugin.
 *
 * @CKEditorPlugin(
 *   id = "editorplaceholder",
 *   label = @Translation("CKEditor Editor Placeholder plugin"),
 * )
 */
class EditorPlaceholder extends PluginBase implements CKEditorPluginContextualInterface, ContainerFactoryPluginInterface {
  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private $moduleHandler;

  /**
   * Drupal library discovery.
   *
   * @var \Drupal\Core\Asset\LibraryDiscovery
   */
  private $libraryDiscovery;

  /**
   * Editor placeholder constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandler $moduleHandler
   *   Module handler.
   * @param \Drupal\Core\Asset\LibraryDiscovery $libraryDiscovery
   *   Library discovery.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandler $moduleHandler, LibraryDiscovery $libraryDiscovery) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $moduleHandler;
    $this->libraryDiscovery = $libraryDiscovery;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('library.discovery')
    );
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
  public function getDependencies(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    $path = 'libraries/editorplaceholder';
    if ($this->moduleHandler->moduleExists('libraries')) {
      $library = $this->libraryDiscovery->getLibraryByName('editorplaceholder');
      if ($library) {
        $path = $library['path'];
      }
    }
    return $path . '/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    // Add a default value, otherwise the placeholder won't render.
    return [
      'editorplaceholder' => $this->t('Enter your text'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(Editor $editor) {
    return TRUE;
  }

}
