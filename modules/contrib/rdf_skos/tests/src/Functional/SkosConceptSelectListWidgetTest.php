<?php

declare(strict_types = 1);

namespace Drupal\Tests\rdf_skos\Functional;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\sparql_entity_storage\Traits\SparqlConnectionTrait;
use Drupal\Tests\rdf_skos\Traits\SkosEntityReferenceTrait;
use Drupal\Tests\rdf_skos\Traits\SkosImportTrait;

/**
 * Test the Skos concept select list widget.
 */
class SkosConceptSelectListWidgetTest extends BrowserTestBase {

  use SparqlConnectionTrait;
  use SkosImportTrait;
  use SkosEntityReferenceTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'sparql_entity_storage',
    'rdf_skos',
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

    $this->drupalCreateContentType([
      'type' => 'article',
    ]);

    $this->createSkosConceptReferenceField(
      'node',
      'article',
      ['http://example.com/fruit'],
      'field_fruit_reference',
      'Fruit',
      'skos_concept_entity_reference_options_select'
    );
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
   * Tests the Skos Concept Reference Select list widget.
   */
  public function testSkosConceptSelectList(): void {
    $this->drupalLogin($this->drupalCreateUser([
      'view published skos concept scheme entities',
      'view published skos concept entities',
      'bypass node access',
    ]));
    $this->drupalGet('/node/add');
    $page = $this->getSession()->getPage();
    // Assert the options are ordered by id by default.
    $expected_options = [
      '_none' => '- None -',
      'http://example.com/fruit/citrus-fruit' => 'Citrus fruit',
      'http://example.com/fruit/exotic-fruit' => 'Exotic fruit',
      'http://example.com/fruit/banana' => 'Banana',
      'http://example.com/fruit/lemon' => 'Lemon',
      'http://example.com/fruit/pear' => 'Pear',
      'http://example.com/fruit/apple' => 'Apple',
      'http://example.com/fruit/alien' => 'Ålien fruit',
    ];
    $actual_options = $this->getOptions($page->findField('field_fruit_reference'));
    $this->assertEquals($expected_options, $actual_options);

    // Configure the field widget to be ordered by label.
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display */
    $form_display = EntityFormDisplay::load('node.article.default');
    $field_config = $form_display->getComponent('field_fruit_reference');
    $field_config['settings']['order'] = 'label';
    $form_display->setComponent('field_fruit_reference', $field_config);
    $form_display->save();

    // Assert the options are now ordered by label.
    $this->drupalGet('/node/add');
    $page = $this->getSession()->getPage();
    $expected_options = [
      '_none' => '- None -',
      'http://example.com/fruit/alien' => 'Ålien fruit',
      'http://example.com/fruit/apple' => 'Apple',
      'http://example.com/fruit/banana' => 'Banana',
      'http://example.com/fruit/citrus-fruit' => 'Citrus fruit',
      'http://example.com/fruit/exotic-fruit' => 'Exotic fruit',
      'http://example.com/fruit/lemon' => 'Lemon',
      'http://example.com/fruit/pear' => 'Pear',
    ];
    $actual_options = $this->getOptions($page->findField('field_fruit_reference'));
    $this->assertEquals($expected_options, $actual_options);

    // Select and option and save the node.
    $page->selectFieldOption('Fruit', 'Apple');
    $page->fillField('Title', 'Set Fruit in select box');
    $page->pressButton('Save');
    $this->assertSession()->elementTextContains('css', '.messages--status', 'article Set Fruit in select box has been created.');
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->drupalGetNodeByTitle('Set Fruit in select box');
    // Make sure that field value is saved properly.
    $this->assertEquals('http://example.com/fruit/apple', $node->get('field_fruit_reference')->target_id);
    $this->drupalGet($node->toUrl('edit-form'));
    $this->assertSession()->fieldValueEquals('Fruit', 'http://example.com/fruit/apple');
  }

}
