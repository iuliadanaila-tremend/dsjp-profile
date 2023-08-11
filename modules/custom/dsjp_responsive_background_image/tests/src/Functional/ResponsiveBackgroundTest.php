<?php

namespace Drupal\Tests\responsive_background_test\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Responsive Background module.
 *
 * @group responsive_background_test
 */
class ResponsiveBackgroundTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'views',
    'dsjp_responsive_background_image',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // Make sure to complete the normal setup steps first.
    parent::setUp();

    // Set the front page to "/node".
    \Drupal::configFactory()
      ->getEditable('system.site')
      ->set('page.front', '/node')
      ->save(TRUE);
  }

  /**
   * Test that the website is functioning correctly.
   */
  public function testResponsiveBackground() {
    // Load the front page.
    $this->drupalGet('<front>');

    // Confirm that the site didn't throw a server error or something else.
    $this->assertSession()->statusCodeEquals(200);

    // Confirm that the front page contains the standard text.
    // Verify the assertion: pageTextContains() for HTML responses,
    // responseContains() for non-HTML responses.
    // The passed text should be HTML decoded,
    // exactly as a human sees it in the browser.
    $this->assertSession()->pageTextContains('Welcome to Drupal');
  }

}
