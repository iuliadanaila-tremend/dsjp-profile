<?php

namespace Drupal\Tests\community_group_test\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the community functionalities.
 *
 * @group community_group_test
 */
class CommunityGroupTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'dsjp_community_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The entity type manager property.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $admin_user = $this->drupalCreateUser([], NULL, TRUE);
    $this->entityTypeManager = $this->container->get('entity_type.manager');

    $this->drupalLogin($admin_user);
  }

  /**
   * Test that the general group is available.
   */
  public function testPageAvailability() {
    $group = $this->entityTypeManager->getStorage('group')->create([
      'type' => 'test_group_type',
      'label' => 'Test Group',
    ]);
    $group->save();
    $this->drupalGet('/admin/group');
    $this->assertSession()->pageTextContains('Test Group');
    $this->assertSession()->pageTextNotContains('There are no groups yet.');

    $this->drupalGet('/admin/group/types');
    $this->assertSession()->pageTextContains('Test group type');
  }

}
