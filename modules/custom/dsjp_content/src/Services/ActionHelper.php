<?php

namespace Drupal\dsjp_content\Services;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\content_moderation\StateTransitionValidationInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class ActionHelper for helping other Action Plugin classes.
 *
 * @package Drupal\dsjp_content\Services.
 */
class ActionHelper {

  use StringTranslationTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * The string identifier for the workflow.
   *
   * @var string
   */
  protected $workflowId;

  /**
   * The permission handler.
   *
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected $permissionHandler;

  /**
   * Entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The moderation info service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInfo;

  /**
   * Moderation state transition validation service.
   *
   * @var \Drupal\content_moderation\StateTransitionValidationInterface
   */
  protected $validator;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * User storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $userStorage;

  /**
   * Constructs ActionHelper object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_info
   *   The moderation info.
   * @param \Drupal\content_moderation\StateTransitionValidationInterface $validator
   *   The transition validator.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager, ModerationInformationInterface $moderation_info, StateTransitionValidationInterface $validator, MessengerInterface $messenger) {
    $this->account = $current_user;
    $this->userStorage = $entity_type_manager->getStorage('user');
    $this->entityTypeManager = $entity_type_manager;
    $this->moderationInfo = $moderation_info;
    $this->validator = $validator;
    $this->messenger = $messenger;
    // We check if this is the right workflow.
    $this->workflowId = 'dsj_organisation_flow';
  }

  /**
   * Load latest Revision of a given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Current entity.
   *
   * @return Drupal\Core\Entity\ContentEntityInterface
   *   Return last revision of the entity.
   */
  public function loadLatestRevision(ContentEntityInterface $entity) {
    $entity_storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
    $original_entity = ($revision_id = $entity_storage->getLatestTranslationAffectedRevisionId($entity->id(), $entity->language()->getId())) ?
      $entity_storage->loadRevision($revision_id)->getTranslation($entity->language()->getId()) :
      NULL;
    return $original_entity;
  }

  /**
   * Changes the moderation state and status of an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Current entity.
   * @param string $new_state
   *   The destination state.
   * @param string $new_status
   *   The status that will need to change to.
   * @param string $message
   *   The log message.
   */
  public function actionExecute(ContentEntityInterface $entity, $new_state, $new_status, $message) {
    $revision = $this->loadLatestRevision($entity);
    $storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
    $revision = $storage->createRevision($revision);
    $revision->moderation_state->value = $new_state;
    $revision->status = $new_status;
    if ($revision instanceof RevisionLogInterface) {
      $revision->setRevisionLogMessage($message);
      $revision->setRevisionUserId($this->account->id());
    }
    $revision->save();
  }

  /**
   * Check if the object can change for the new state from the current user.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $object
   *   Current object.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Current user.
   * @param string $return_as_object
   *   How the response to be returned.
   * @param string $to_state_id
   *   Id of the destination state.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Access for this acction.
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   */
  public function actionAccess(ContentEntityInterface $object, AccountInterface $account = NULL, $return_as_object = FALSE, $to_state_id = 'published') {
    if (!$object) {
      $result = AccessResult::forbidden('Not a valid entity.');
      return $return_as_object ? $result : $result->isAllowed();
    }

    // If it has moderation, it has to be editorial for now.
    if ($workflow = $this->moderationInfo->getWorkflowForEntity($object)) {
      if ($workflow->id() !== $this->workflowId) {
        $result = AccessResult::forbidden('Not a valid workflow for this entity.');
        $result->addCacheableDependency($workflow);
        return $return_as_object ? $result : $result->isAllowed();
      }
    }
    else {
      $result = $object->access('update', $account, TRUE);
      return $return_as_object ? $result : $result->isAllowed();
    }

    $object = $this->loadLatestRevision($object);
    // Let content moderation do its job. See content_moderation_entity_access()
    // for more details.
    $access = $object->access('update', $account, TRUE);

    $from_state = $workflow->getTypePlugin()->getState($object->moderation_state->value);
    // Make sure we can make the transition.
    if ($from_state->canTransitionTo($to_state_id)) {
      $to_state = $workflow->getTypePlugin()->getState($to_state_id);
      // Let the validator do the access check.
      // This not only checks if the transition is valid but
      // also checks if the user have permission to do
      // the transition. While it does repeat some of the access checks
      // this validator can be overridden by groups.
      $valid = $this->validator->isTransitionValid($workflow, $from_state, $to_state, $account, $object);
      if ($valid) {
        // The user has permission to
        // perform the transition. Set to allow if they also have update
        // access.
        $result = AccessResult::allowed()->andIf($access);
      }
      else {
        // The user does not have permission to perform the
        // transition. In keeping consistent with the previous
        // code return neutral.
        $result = AccessResult::neutral()->andIf($access);
      }
    }
    else {
      $this->messenger->addWarning($this->t('Change to @title is in @current_state and needs to be moderated before it can be @transition.',
          [
            '@title' => $object->label(),
            '@current_state' => $from_state->label(),
            '@transition' => $to_state_id,
          ]),
        MessengerInterface::TYPE_WARNING);
      $result = AccessResult::forbidden('No valid transition found.');
    }
    $result->addCacheableDependency($workflow);
    return $return_as_object ? $result : $result->isAllowed();
  }

}
