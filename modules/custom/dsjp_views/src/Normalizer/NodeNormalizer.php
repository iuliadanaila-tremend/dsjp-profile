<?php

namespace Drupal\dsjp_views\Normalizer;

use Drupal\comment\CommentInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\flag\FlaggingInterface;
use Drupal\serialization\Normalizer\ContentEntityNormalizer;

/**
 * Provides a custom normalizer for node entities.
 */
class NodeNormalizer extends ContentEntityNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = ContentEntityInterface::class;

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = []) {
    $attributes = parent::normalize($entity, $format, $context);
    if ((!$entity instanceof CommentInterface) && (!$entity instanceof FlaggingInterface)) {
      // Remove unneeded fields from display.
      $attributesToRemove = [
        'uuid',
        'vid',
        'uid',
        'revision_timestamp',
        'revision_log',
        'revision_uid',
        'default_langcode',
        'moderation_state',
        'content_translation_outdated',
        'content_translation_source',
        'promote',
        'sticky',
        'revision_translation_affected',
        'metatag',
        'field_dsj_comments',
        'field_dsj_meta_tags',
      ];

      if (isset($context['views_style_plugin']) && $context['views_style_plugin']->view->current_display == 'rest_content') {
        // Leave uid for smart rest content API.
        unset($attributesToRemove[2]);
      }
      foreach ($attributesToRemove as $attributeName) {
        if (isset($attributes[$attributeName])) {
          unset($attributes[$attributeName]);
        }
      }
    }

    return $attributes;
  }

}
