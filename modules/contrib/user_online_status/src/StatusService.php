<?php

namespace Drupal\user_online_status;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\user\Entity\User;
use Drupal\user_online_status\Form\UserOnlineStatusSettingsForm;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The user's status service.
 */
class StatusService {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The user storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $userStorage;

  /**
   * The config.
   *
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * StatusService constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   */
  public function __construct(RequestStack $request_stack, EntityTypeManager $entity_type_manager, ConfigFactory $config_factory) {
    $this->request = $request_stack->getCurrentRequest();
    $this->userStorage = $entity_type_manager->getStorage('user');
    $this->config = $config_factory->getEditable(UserOnlineStatusSettingsForm::USER_ONLINE_STATUS_SETTINGS);
  }

  /**
   * Returns online status for each uid given.
   *
   * @param int[] $uids
   *   The uid's.
   *
   * @return array
   *   Associative array with uid => status.
   */
  public function getMultipleUserStatuses(array $uids) {
    $users = $this->userStorage->loadMultiple($uids);
    $statuses = [];

    foreach ($users as $user) {
      $statuses[$user->id()] = $this->getUserStatus($user);
    }

    return $statuses;
  }

  /**
   * Gets the status of a single user.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user.
   *
   * @return string
   *   The user's status.
   */
  public function getUserStatus(User $user) {
    $last = $user->getLastAccessedTime();
    $now = $this->request->server->get('REQUEST_TIME');
    $seconds_to_absent = $this->config->get('seconds_to_absent');
    $seconds_to_offline = $this->config->get('seconds_to_offline');

    // Credits to Kevin Quillen.
    // @see https://drupal.stackexchange.com/a/273974/15055
    switch (TRUE) {

      case ($last >= ($now - $seconds_to_absent)):
        return 'online';

      case (($last < ($now - $seconds_to_absent)) && $last > ($now - $seconds_to_offline)):
        return 'absent';

      default:
        return 'offline';
    }
  }

}
