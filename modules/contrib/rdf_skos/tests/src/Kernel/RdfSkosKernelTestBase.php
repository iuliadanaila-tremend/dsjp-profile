<?php

declare(strict_types = 1);

namespace Drupal\Tests\rdf_skos\Kernel;

use Drupal\Tests\sparql_entity_storage\Kernel\SparqlKernelTestBase;
use Drupal\Tests\rdf_skos\Traits\SkosImportTrait;

/**
 * Base class for the SKOS Kernel Tests.
 */
class RdfSkosKernelTestBase extends SparqlKernelTestBase {

  use SkosImportTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'rdf_skos',
    'rdf_skos_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $base_url = $_ENV['SIMPLETEST_BASE_URL'];
    $this->import($base_url, $this->sparql, 'phpunit');
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown(): void {
    $base_url = $_ENV['SIMPLETEST_BASE_URL'];
    $this->clear($base_url, $this->sparql, 'phpunit');

    parent::tearDown();
  }

}
