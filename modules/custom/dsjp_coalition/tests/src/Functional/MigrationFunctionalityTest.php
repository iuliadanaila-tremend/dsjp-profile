<?php

namespace Drupal\Tests\migration_functionality\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the migration pages.
 *
 * @group migration_functionality
 */
class MigrationFunctionalityTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'migrate',
    'migrate_tools',
    'dsjp_coalition_test',
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
   * Test that the general migration page is available.
   */
  public function testPageAvailability() {
    $this->drupalGet('/admin/structure/migrate');
    $this->assertSession()->pageTextContains('Default');
    $this->assertSession()->linkByHrefExists('admin/structure/migrate/manage/default/migrations');
  }

}
