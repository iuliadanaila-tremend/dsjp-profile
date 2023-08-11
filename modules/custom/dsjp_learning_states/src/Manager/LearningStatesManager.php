<?php

namespace Drupal\dsjp_learning_states\Manager;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides helper methods for the map and countries.
 */
class LearningStatesManager {

  use StringTranslationTrait;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The entity type manager.
   *
   * @var array
   */
  protected $states;

  /**
   * The entity type manager.
   *
   * @var array
   */
  protected $transitions;

  /**
   * LearningStatesManager constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   The logger channel.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current account.
   */
  public function __construct(LoggerChannelFactoryInterface $loggerChannelFactory, EntityTypeManagerInterface $entity_type_manager, AccountInterface $account) {
    $this->logger = $loggerChannelFactory;
    $this->entityTypeManager = $entity_type_manager;
    $this->account = $account;

    $this->states = [
      'interested' => $this->t('Intereseted in'),
      'in_progress' => $this->t('In progress'),
      'done' => $this->t('Done'),
      'remove' => $this->t('Remove'),
    ];

    $this->transitions = [
      'remove' => [
        'label' => $this->t('Remove') ,
        'from_states' => ['interested', 'in_progress', 'done'],
      ],
      'interested' => [
        'label' => $this->t('Interested in') ,
        'from_states' => ['interested'],
      ],
      'in_progress' => [
        'label' => $this->t('In progress') ,
        'from_states' => ['interested', 'in_progress'],
      ],
      'done' => [
        'label' => $this->t('Done') ,
        'from_states' => ['interested', 'in_progress', 'done'],
      ],
    ];

  }

  /**
   * Adds the learning state to the database.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Current entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account for whom the state is set.
   * @param string $state
   *   The value of the state.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addLearningState(EntityInterface $entity, AccountInterface $account, string $state) {

    $old_state = $this->getEntityLearningState($entity, $account);
    if (!$old_state) {
      $learning_state = $this->entityTypeManager->getStorage('dsjp_learning_states')->create([
        'uid' => $account->id(),
        'entity_id' => $entity->id(),
        'entity_type' => $entity->getEntityTypeId(),
        'value' => $state,
      ]);

      $learning_state->save();
    }
    else {
      if ($old_state->getValue() != $state) {
        $old_state->setValue($state);
        $old_state->save();
      }
    }
  }

  /**
   * Remove the learning state from database.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Current entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account for whom the state is set.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function removeLearningState(EntityInterface $entity, AccountInterface $account) {
    $learning_state = $this->getEntityLearningState($entity, $account);
    $learning_state->delete();
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityLearningState(EntityInterface $entity, AccountInterface $account = NULL) {
    $query = $this->entityTypeManager->getStorage('dsjp_learning_states')->getQuery();

    if (!is_null($account)) {
      $query->condition('uid', $account->id());
    }
    else {
      $query->condition('uid', $this->account->id());
    }

    $query->condition('entity_id', $entity->id());
    $query->accessCheck(TRUE);
    $ids = $query->execute();

    if (empty($ids)) {
      return FALSE;
    }

    $state_id = reset($ids);

    $state_entity = $this->entityTypeManager->getStorage('dsjp_learning_states')->load($state_id);

    return $state_entity;
  }

  /**
   * Get the array of states.
   *
   * @return array
   *   The existing states.
   */
  public function getStates() {
    return $this->states;
  }

  /**
   * Get all the possible transitions from a given state.
   *
   * @param string $state
   *   The current entity state.
   *
   * @return array
   *   Possible states with the transition label.
   */
  public function getPosibleTransitions(string $state) {
    $result = [];
    foreach ($this->transitions as $next_state => $transition) {
      if (in_array($state, $transition['from_states'])) {
        $result[$next_state] = $transition['label'];
      }
    }
    return $result;
  }

}
