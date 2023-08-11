<?php

namespace Drupal\dsjp_learning_states\Entity\Storage;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;

/**
 * Defines the flag schema handler.
 */
class LearningStatesStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    $schema['dsjp_learning_states']['indexes'] += [
      'entity_id__uid' => ['entity_id', 'uid'],
    ];

    return $schema;
  }

}
