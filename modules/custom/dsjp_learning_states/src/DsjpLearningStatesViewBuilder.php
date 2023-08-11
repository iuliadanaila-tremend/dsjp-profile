<?php

namespace Drupal\dsjp_learning_states;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Provides a view controller for a dsjp_learning_states entity type.
 */
class DsjpLearningStatesViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode) {
    $build = parent::getBuildDefaults($entity, $view_mode);
    // The dsjp_learning_states has no entity template itself.
    unset($build['#theme']);
    return $build;
  }

}
