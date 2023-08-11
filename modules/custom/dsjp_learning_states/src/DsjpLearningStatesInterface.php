<?php

namespace Drupal\dsjp_learning_states;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a dsjp_learning_states entity type.
 */
interface DsjpLearningStatesInterface extends ContentEntityInterface, EntityOwnerInterface {

  /**
   * Gets the dsjp_learning_states creation timestamp.
   *
   * @return int
   *   Creation timestamp of the dsjp_learning_states.
   */
  public function getCreatedTime();

  /**
   * Sets the dsjp_learning_states creation timestamp.
   *
   * @param int $timestamp
   *   The dsjp_learning_states creation timestamp.
   *
   * @return \Drupal\dsjp_learning_states\DsjpLearningStatesInterface
   *   The called dsjp_learning_states entity.
   */
  public function setCreatedTime($timestamp);

}
