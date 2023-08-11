<?php

namespace Drupal\Tests\request_translation\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Translation module and the custom provider.
 *
 * @group request_translation
 */
class RequestTranslationTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'content_translation',
    'tmgmt',
    'dsjp_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $admin_user = $this->drupalCreateUser([
      'translate any entity',
      'create translation jobs',
    ], NULL, TRUE);

    $this->drupalLogin($admin_user);
  }

  /**
   * Test that the eTranslation provider works.
   */
  public function testRequestTranslation() {
    $this->drupalGet('/admin/tmgmt/translators');
    $this->assertSession()->linkByHrefExists('admin/tmgmt/translators/manage/etranslation');

    $this->drupalGet('/admin/tmgmt/translators/manage/etranslation');
    $this->assertSession()->pageTextContains('Credentials');
  }

}
