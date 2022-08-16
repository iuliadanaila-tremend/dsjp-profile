<?php

namespace Drupal\dsjp_views\Normalizer;

use Drupal\Core\Entity\ContentEntityInterface;
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
    foreach ($attributesToRemove as $attributeName) {
      if (isset($attributes[$attributeName])) {
        unset($attributes[$attributeName]);
      }
    }

    return $attributes;
  }

}
