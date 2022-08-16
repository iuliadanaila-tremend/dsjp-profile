<?php

namespace Drupal\Tests\user_online_status\Functional;

use Drupal\Tests\BrowserTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests for online status service.
 *
 * @group user_online_status
 */
class ServiceTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user_online_status'];

  /**
   * The online user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $userOnline;

  /**
   * The absent user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $userAbsent;

  /**
   * The offline user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $userOffline;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $now = Request::createFromGlobals()->server->get('REQUEST_TIME');
    // Use default config.
    $time_to_absent = 900;
    $time_to_offline = 1800;

    $this->userOnline = $this->drupalCreateUser([], NULL, FALSE, [
      'access' => $now - floor($time_to_absent / 2),
    ]);
    $this->userAbsent = $this->drupalCreateUser([], NULL, FALSE, [
      'access' => $now - $time_to_absent - floor(($time_to_offline - $time_to_absent) / 2),
    ]);
    $this->userOffline = $this->drupalCreateUser([], NULL, FALSE, [
      'access' => $now - ($time_to_offline * 2),
    ]);
  }

  /**
   * Test user online status service.
   */
  public function testUserOnlineStatusService() {
    /** @var \Drupal\user_online_status\StatusService $status_service */
    $status_service = \Drupal::service('user_online_status.status_service');

    $this->assertEqual($status_service->getUserStatus($this->userOnline), 'online');
    $this->assertEqual($status_service->getUserStatus($this->userAbsent), 'absent');
    $this->assertEqual($status_service->getUserStatus($this->userOffline), 'offline');
  }

}
