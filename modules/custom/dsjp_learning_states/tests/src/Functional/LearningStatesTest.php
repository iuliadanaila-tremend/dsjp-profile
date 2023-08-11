<?php

namespace Drupal\Tests\learning_states\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests for the Lorem Ipsum module.
 *
 * @group loremipsum
 */
class LearningStatesTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['node', 'dsjp_learning_states'];

  /**
   * A simple user.
   *
   * @var \Drupal\user\Entity\User
   */
  private $user;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Perform initial setup tasks that run before every test method.
   */
  public function setUp():void {
    parent::setUp();
    $this->user = $this->drupalCreateUser([
      'administer site configuration',
      'access dsjp_learning_states overview',
    ]);
  }

  /**
   * Test that the general group is available.
   */
  public function testPageAvailability() {
    // Login.
    $this->drupalLogin($this->user);

    // Learning states list exists.
    $this->drupalGet('admin/content/dsjp-learning-states');
    $this->assertSession()->statusCodeEquals(200);

    // Add learning states exists.
    $this->drupalGet('admin/content/dsjp-learning-states/add');
    $this->assertSession()->statusCodeEquals(200);
  }

}
