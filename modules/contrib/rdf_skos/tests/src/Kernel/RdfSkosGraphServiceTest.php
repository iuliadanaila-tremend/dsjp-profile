<?php

declare(strict_types = 1);

namespace Drupal\Tests\rdf_skos\Kernel;

use Drupal\Tests\sparql_entity_storage\Kernel\SparqlKernelTestBase;

/**
 * Tests the RDF SKOS service.
 */
class RdfSkosGraphServiceTest extends SparqlKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'rdf_skos',
  ];

  /**
   * Test that op_skos_setup service sets the correct config.
   */
  public function testGraphSetupService(): void {
    // Prepare the graph data.
    $graphs = [
      'fruit' => 'http://example.com/fruit/phpunit',
    ];
    $expected = [
      'skos_concept_scheme' => [
        [
          'name' => 'fruit',
          'uri' => 'http://example.com/fruit/phpunit',
        ],
      ],
      'skos_concept' => [
        [
          'name' => 'fruit',
          'uri' => 'http://example.com/fruit/phpunit',
        ],
      ],
    ];

    // Call the service to import our data.
    \Drupal::service('rdf_skos.skos_graph_configurator')->addGraphs($graphs);

    $config = \Drupal::service('config.factory')->get('rdf_skos.graphs');
    $entity_types = $config->get('entity_types');
    $this->assertEquals($expected, $entity_types);

    // Add more values.
    $graphs['vegetables'] = 'http://example.com/vegetables/phpunit';
    $expected['skos_concept_scheme'][] = [
      'name' => 'vegetables',
      'uri' => 'http://example.com/vegetables/phpunit',
    ];
    $expected['skos_concept'][] = [
      'name' => 'vegetables',
      'uri' => 'http://example.com/vegetables/phpunit',
    ];

    // Import and assert no duplicates.
    \Drupal::service('rdf_skos.skos_graph_configurator')->addGraphs($graphs);

    $config = \Drupal::service('config.factory')->get('rdf_skos.graphs');
    $entity_types = $config->get('entity_types');
    $this->assertEquals($expected, $entity_types);
  }

}
