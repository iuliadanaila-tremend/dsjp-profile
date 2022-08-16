<?php

declare(strict_types = 1);

namespace Drupal\Tests\rdf_skos_language_mapping\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;

/**
 * Test the RDF Skos language mapping form.
 */
class SkosLanguageMappingFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'rdf_skos_language_mapping',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    foreach (['fr', 'pt-pt'] as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }
  }

  /**
   * Tests the language mapping configuration form.
   */
  public function testLanguageMappingForm(): void {
    $this->drupalLogin($this->createUser(['administer rdf skos language mapping']));
    $this->drupalGet('admin/config/sparql/language-mapping');
    $assert_session = $this->assertSession();

    // Make sure that validation works properly.
    $edit = [
      "language_mapping[en]" => 'fr',
      "language_mapping[fr]" => 'fr',
      "language_mapping[pt-pt]" => 'pt',
    ];

    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->elementTextContains('css', '.messages--error', 'The langcode for English, fr, is not unique.');
    $this->assertSession()->elementTextContains('css', '.messages--error', 'The langcode for French, fr, is not unique.');

    // Make sure that saving of configuration works properly.
    $edit = [
      "language_mapping[en]" => 'en',
      "language_mapping[fr]" => 'fr',
      "language_mapping[pt-pt]" => 'pt',
    ];
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->elementTextContains('css', '.messages--status', 'The configuration options have been saved.');
    $form_values = [
      'English (en)' => 'en',
      'French (fr)' => 'fr',
      'Portuguese, Portugal (pt-pt)' => 'pt',
    ];
    foreach ($form_values as $label => $langcode) {
      $assert_session->fieldValueEquals($label, $langcode);
    }

    $this->assertEquals([
      'en' => 'en',
      'fr' => 'fr',
      'pt-pt' => 'pt',
    ], $this->config('rdf_skos_language_mapping.settings')->get('language_mapping'));
  }

}
