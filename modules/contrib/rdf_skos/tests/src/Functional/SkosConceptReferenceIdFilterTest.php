<?php

declare(strict_types = 1);

namespace Drupal\Tests\rdf_skos\Functional;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\sparql_entity_storage\Traits\SparqlConnectionTrait;
use Drupal\Tests\rdf_skos\Traits\SkosImportTrait;
use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\Tests\ViewTestData;

/**
 * Test the Skos concept reference ID Views filter.
 *
 * @see \Drupal\rdf_skos\Plugin\views\filter\SkosConceptReferenceId
 */
class SkosConceptReferenceIdFilterTest extends ViewTestBase {

  use SparqlConnectionTrait;
  use SkosImportTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'views',
    'node',
    'sparql_entity_storage',
    'rdf_skos',
    'rdf_skos_test',
  ];

  /**
   * {@inheritdoc}
   */
  public static $testViews = [
    'skos_concept_reference_filter_test',
    'skos_concept_reference_filter_test_filters',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp(FALSE);
    $this->setUpSparql();
    $base_url = $_ENV['SIMPLETEST_BASE_URL'];
    $this->import($base_url, $this->sparql, 'phpunit');
    $this->enableGraph('fruit');

    $this->drupalCreateContentType([
      'type' => 'article',
    ]);

    $this->createSkosConceptReferenceField();

    ViewTestData::createTestViews(get_class($this), ['rdf_skos_test']);

    foreach ($this->getNodeData() as $values) {
      $values['type'] = 'article';
      $this->createNode($values);
    }
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
   * Tests the Skos Concept Reference ID filter.
   */
  public function testViewFilter(): void {
    $this->drupalGet('/skos-concept-reference-test');
    foreach ($this->getNodeData() as $values) {
      $this->assertSession()->linkExists($values['title'], 0, sprintf('The link with the label "%s" was not found and it should be.', $values['title']));
    }

    // The filter view shows nodes that reference lemons.
    $this->drupalGet('/skos-concept-reference-test-filters');
    foreach ($this->getNodeData() as $values) {
      if ($values['title'] === 'Node with lemon') {
        $this->assertSession()->linkExists($values['title'], 0, sprintf('The link with the label "%s" was not found and it should be.', $values['title']));
        continue;
      }
      $this->assertSession()->linkNotExists($values['title'], sprintf('The link with the label "%s" was found and it should not be.', $values['title']));
    }

    // Edit the View to show nodes that reference lemons or apples.
    /** @var \Drupal\views\ViewEntityInterface $view */
    $view = $this->container->get('entity_type.manager')->getStorage('view')->load('skos_concept_reference_filter_test_filters');
    $display = $view->get('display');
    $display['default']['display_options']['filters']['field_fruit_reference_target_id']['value'][] = 'http://example.com/fruit/apple';
    $view->set('display', $display);
    $view->save();

    $this->drupalGet('/skos-concept-reference-test-filters');
    foreach ($this->getNodeData() as $values) {
      if (in_array($values['title'], ['Node with lemon', 'Node with apple'])) {
        $this->assertSession()->linkExists($values['title'], 0, sprintf('The link with the label "%s" was not found and it should be.', $values['title']));
        continue;
      }
      $this->assertSession()->linkNotExists($values['title'], sprintf('The link with the label "%s" was found and it should not be.', $values['title']));
    }

    // Edit the View to show nodes that reference lemons AND apples.
    $display['default']['display_options']['filters']['field_fruit_reference_target_id']['operator'] = 'and';
    $view->set('display', $display);
    $view->save();

    $this->createNode([
      'type' => 'article',
      'title' => 'Node with both apple and lemon',
      'field_fruit_reference' => [
        'http://example.com/fruit/lemon',
        'http://example.com/fruit/apple',
      ],
    ]);

    $this->drupalGet('/skos-concept-reference-test-filters');
    $this->assertSession()->linkExists('Node with both apple and lemon', 0, sprintf('The link with the label "Node with both apple and lemon" was not found and it should be.'));
    foreach ($this->getNodeData() as $values) {
      $this->assertSession()->linkNotExists($values['title'], sprintf('The link with the label "%s" was found and it should not be.', $values['title']));
    }
  }

  /**
   * Returns some data to create nodes from.
   *
   * @return array
   *   The node data.
   */
  protected function getNodeData(): array {
    return [
      [
        'title' => 'Node with citrus fruit',
        'field_fruit_reference' => 'http://example.com/fruit/citrus-fruit',
      ],
      [
        'title' => 'Node with exotic fruit',
        'field_fruit_reference' => 'http://example.com/fruit/exotic-fruit',
      ],
      [
        'title' => 'Node with lemon',
        'field_fruit_reference' => 'http://example.com/fruit/lemon',
      ],
      [
        'title' => 'Node with apple',
        'field_fruit_reference' => 'http://example.com/fruit/apple',
      ],
    ];
  }

  /**
   * Creates the Skos Concept reference field on the Article node type.
   */
  protected function createSkosConceptReferenceField(): void {
    $handler_settings = [
      'target_bundles' => [],
      'auto_create' => FALSE,
      'concept_schemes' => [
        'http://example.com/fruit',
      ],
      'field' => [
        'field_name' => 'field_fruit_reference',
        'entity_type' => 'node',
        'bundle' => 'article',
        'concept_schemes' => [
          'http://example.com/fruit',
        ],
      ],
    ];

    FieldStorageConfig::create([
      'field_name' => 'field_fruit_reference',
      'type' => 'skos_concept_entity_reference',
      'entity_type' => 'node',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings' => [
        'target_type' => 'skos_concept',
      ],
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_fruit_reference',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Fruit',
      'settings' => [
        'handler' => 'default:skos_concept',
        'handler_settings' => $handler_settings,
      ],
    ])->save();
  }

}
