<?php

namespace Drupal\dsjp_content\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginContextualInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the CKEditor Editor Placeholder plugin.
 *
 * @CKEditorPlugin(
 *   id = "editorplaceholder",
 *   label = @Translation("CKEditor Editor Placeholder plugin"),
 * )
 */
class EditorPlaceholder extends PluginBase implements CKEditorPluginContextualInterface {

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
    if (\Drupal::moduleHandler()->moduleExists('libraries')) {
      $path = libraries_get_path('editorplaceholder');
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
