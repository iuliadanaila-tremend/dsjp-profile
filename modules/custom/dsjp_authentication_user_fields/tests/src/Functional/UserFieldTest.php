<?php

namespace Drupal\Tests\user_field_test\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the User authentication fields.
 *
 * @group user_field_test
 */
class UserFieldTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'dsjp_authentication_user_fields_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $admin_user = $this->drupalCreateUser([], NULL, TRUE);

    $this->drupalLogin($admin_user);
  }

  /**
   * Test that the authentication fields are available.
   */
  public function testFieldsAvailability() {
    $this->drupalGet('/user/' . $this->loggedInUser->id() . '/edit');

    $this->assertSession()->pageTextContains('First name');
    $this->assertSession()->pageTextContains('Last name');
  }

}
