<?php

declare(strict_types = 1);

namespace Drupal\Tests\rdf_skos\FunctionalJavascript;

use Drupal\field\Entity\FieldConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\rdf_skos\Traits\SkosImportTrait;
use Drupal\Tests\sparql_entity_storage\Traits\SparqlConnectionTrait;

/**
 * Tests the SKOS Concept entity reference selection plugin form.
 */
class SkosConceptSelectionFormTest extends WebDriverTestBase {

  use SparqlConnectionTrait;
  use SkosImportTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'field_ui',
    'field',
    'rdf_skos',
    'rdf_skos_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp();
    $this->setUpSparql();
    $base_url = $_ENV['SIMPLETEST_BASE_URL'];
    $this->import($base_url, $this->sparql, 'phpunit');
    $this->enableGraph('fruit');
    $this->enableGraph('vegetables');

    $this->drupalCreateContentType([
      'type' => 'article',
    ]);

    $this->drupalLogin($this->drupalCreateUser([], $this->randomString(), TRUE));
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown(): void {
    $base_url = $_ENV['SIMPLETEST_BASE_URL'];
    $this->clear($base_url, $this->sparql, 'phpunit');

    parent::tearDown();
  }

  /**
   * Tests the skos concept selection form.
   */
  public function testSelectionConfigForm(): void {
    $this->drupalGet('admin/structure/types/manage/article/fields/add-field');
    $this->getSession()->getPage()->selectFieldOption('Add a new field', 'skos_concept_entity_reference');
    $this->getSession()->getPage()->fillField('Label', 'Reference field');
    $this->assertSession()->waitForText('Machine name: field_reference_field');
    $this->getSession()->getPage()->pressButton('Save and continue');
    $this->getSession()->getPage()->pressButton('Save field settings');

    // Assert we have the concept schemes selection element.
    $this->assertSession()->selectExists('Concept Schemes');
    $this->assertSession()->optionExists('Concept Schemes', 'http://example.com/fruit');
    $this->assertSession()->optionExists('Concept Schemes', 'http://example.com/vegetables');

    // With no concept schemes selected, we don't have any concept subset
    // element.
    $this->assertSession()->fieldNotExists('Concept subset');

    // Select the Vegetable scheme and assert we show the right subset for
    // selection.
    $this->getSession()->getPage()->selectFieldOption('Concept Schemes', 'http://example.com/vegetables');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->assertSession()->selectExists('Concept subset');
    $this->assertSession()->optionExists('Concept subset', 'any_alter');
    $this->assertSession()->optionNotExists('Concept subset', 'fruit_alter');
    // Verify that an option is present to allow to not select any subset.
    $this->assertSession()->optionExists('Concept subset', '- None -');

    // Select the Fruit scheme and assert that we show both subsets.
    $this->getSession()->getPage()->selectFieldOption('Concept Schemes', 'http://example.com/fruit');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->assertSession()->selectExists('Concept subset');
    $this->assertSession()->optionExists('Concept subset', 'any_alter');
    $this->assertSession()->optionExists('Concept subset', 'fruit_alter');
    $this->assertSession()->optionExists('Concept subset', '- None -');

    $this->getSession()->getPage()->selectFieldOption('Concept subset', 'fruit_alter');

    $this->getSession()->getPage()->pressButton('Save settings');
    $this->assertSession()->pageTextContains('Saved Reference field configuration.');

    // Edit back the form and assert the correct default values are set.
    $link = $this->getSession()->getPage()->find('xpath', '//tr/td[normalize-space(text())="field_reference_field"]/..//a[normalize-space(text())="Edit"]');
    $link->click();

    $this->assertSession()->selectExists('Concept Schemes');
    $option = $this->assertSession()->optionExists('Concept Schemes', 'http://example.com/fruit');
    $this->assertTrue($option->hasAttribute('selected'));
    $option = $this->assertSession()->optionExists('Concept Schemes', 'http://example.com/vegetables');
    $this->assertFalse($option->hasAttribute('selected'));

    $this->assertSession()->selectExists('Concept subset');
    $option = $this->assertSession()->optionExists('Concept subset', 'any_alter');
    $this->assertFalse($option->hasAttribute('selected'));
    $option = $this->assertSession()->optionExists('Concept subset', 'fruit_alter');
    $this->assertTrue($option->hasAttribute('selected'));

    // Select the no subset option.
    $this->assertSession()->selectExists('Concept subset')->selectOption('- None -');
    $this->getSession()->getPage()->pressButton('Save settings');
    $this->assertSession()->pageTextContains('Saved Reference field configuration.');

    // Verify that a NULL value is stored when no concept is selected.
    $field_config = FieldConfig::load('node.article.field_reference_field');
    $this->assertNull($field_config->getSetting('handler_settings')['concept_subset']);
  }

}
