<?php

namespace Drupal\Tests\tzfield\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests for time zone field module.
 *
 * @group tzfield
 */
class TimeZoneFieldTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['tzfield'];

  /**
   * Functional tests for tzfield.
   */
  public function testTimeZoneField() {
  }

}
