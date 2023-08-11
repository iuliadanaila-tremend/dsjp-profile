<?php

namespace Drupal\Tests\legat_user_test\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\legal\Entity\Conditions;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the legal page for logged in user.
 *
 * @group legat_user_test
 */
class LegalUserTest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'legal',

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

    // Create a new legal condition.
    Conditions::create([
      'version'    => 1,
      'revision'   => 1,
      'language'   => 'en',
      'conditions' => 'Terms & conditions',
      'format'     => 'full_html',
      'date'       => time(),
      'extras'     => serialize([]),
      'changes'    => NULL,
    ])->save();

    drupal_flush_all_caches();
    $admin_user = $this->drupalCreateUser([
      'access content',
    ]);

    $this->drupalLogin($admin_user);
  }

  /**
   * {@inheritdoc}
   */
  protected function drupalLogin(AccountInterface $account) {
    if ($this->loggedInUser) {
      $this->drupalLogout();
    }

    $this->drupalGet(Url::fromRoute('user.login'));
    $this->submitForm([
      'name' => $account->getAccountName(),
      'pass' => $account->passRaw,
    ], $this->t('Log in'));
    // Logged in user has to submit the legal form before going further.
    $this->submitForm([
      'legal_accept' => 1,
    ], $this->t('Confirm'));

    // @see ::drupalUserIsLoggedIn()
    $account->sessionId = $this->getSession()->getCookie(\Drupal::service('session_configuration')->getOptions(\Drupal::request())['name']);
    $this->assertTrue($this->drupalUserIsLoggedIn($account), new FormattableMarkup('User %name successfully logged in.', ['%name' => $account->getAccountName()]));

    $this->loggedInUser = $account;
    $this->container->get('current_user')->setAccount($account);
  }

  /**
   * Test that the user logged in successfully.
   */
  public function testUserLogin() {
    $this->assertSession()->pageTextContains('Member for');
  }

}
