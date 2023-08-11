<?php

namespace Drupal\dsjp_learning_states\Entity\Storage;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;

/**
 * Default SQL flagging storage.
 */
class LearningStatesStorage extends SqlContentEntityStorage implements ContentEntityStorageInterface {

  /**
   * Stores loaded states per user, entity type and IDs.
   *
   * @var array
   */
  protected $stateSavedIdsByEntity = [];

  /**
   * {@inheritdoc}
   */
  public function resetCache(array $ids = NULL) {
    parent::resetCache($ids);
    $this->stateSavedIdsByEntity = [];
  }

  /**
   * {@inheritdoc}
   */
  public function loadIsSaved(EntityInterface $entity, AccountInterface $account) {
    $flag_ids = $this->loadIsSavedMultiple([$entity], $account);
    return $flag_ids[$entity->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function loadIsSavedMultiple(array $entities, AccountInterface $account) {

    // We cannot have anonymous states.
    if ($account->isAnonymous()) {
      return [];
    }

    $states_ids_by_entity = [];

    if (!$entities) {
      return $states_ids_by_entity;
    }

    // All entities must be of the same type, get the entity type from the
    // first.
    $entity_type_id = reset($entities)->getEntityTypeId();
    $ids_to_load = [];

    // Loop over all requested entities, if they are already in the loaded list,
    // get then from there, merge the global and per-user flags together.
    foreach ($entities as $entity) {
      if (isset($this->stateSavedIdsByEntity[$account->id()][$entity_type_id][$entity->id()])) {
        $states_ids_by_entity[$entity->id()] = $this->stateSavedIdsByEntity[$account->id()][$entity_type_id][$entity->id()];
      }
      else {
        $ids_to_load[$entity->id()] = [];
      }
    }

    // If there are no entities that need to be loaded, return the list.
    if (!$ids_to_load) {
      return $states_ids_by_entity;
    }

    // Initialize the loaded lists with the missing ID's as an empty array.
    if (!isset($this->stateSavedIdsByEntity[$account->id()][$entity_type_id])) {
      $this->stateSavedIdsByEntity[$account->id()][$entity_type_id] = [];
    }

    $this->stateSavedIdsByEntity[$account->id()][$entity_type_id] += $ids_to_load;

    $states_ids_by_entity += $ids_to_load;

    // Directly query the table to avoid the overhead of loading the content
    // entities.
    $query = $this->database->select('dsjp_learning_states', 'ls')
      ->fields('ls', ['entity_id', 'value'])
      ->condition('entity_type', $entity_type_id)
      ->condition('entity_id', array_keys($ids_to_load), 'IN')
      ->condition('uid', $account->id());

    $result = $query
      ->execute();

    // Loop over all results, put them in the cached list and the list that will
    // be returned.
    foreach ($result as $row) {
      $this->stateSavedIdsByEntity[$account->id()][$entity_type_id][$row->entity_id] = $row->value;
      $states_ids_by_entity[$row->entity_id][$row->flag_id] = $row->flag_id;
    }

    return $states_ids_by_entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function doDelete($entities) {

    parent::doDelete($entities);

    /** @var \Drupal\Core\Entity\ContentEntityInterface[] $entities */
    foreach ($entities as $entity) {
      // After deleting a flagging, remove it from the cached flagging by entity
      // if already in static cache.
      if (isset($this->stateSavedIdsByEntity[$entity->get('uid')->target_id][$entity->get('entity_type')->value][$entity->get('entity_id')->value])) {
        unset($this->stateSavedIdsByEntity[$entity->get('uid')->target_id][$entity->get('entity_type')->value][$entity->get('entity_id')->value]);
      }
    }
  }

}
