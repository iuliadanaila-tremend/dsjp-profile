<?php

declare(strict_types = 1);

namespace Drupal\Tests\rdf_skos\Traits;

use Drupal\sparql_entity_storage\Driver\Database\sparql\ConnectionInterface;
use Drupal\sparql_entity_storage\Entity\Query\Sparql\SparqlArg;
use EasyRdf\Graph;

/**
 * Handles the import and delete of the test SKOS vocabularies.
 */
trait SkosImportTrait {

  /**
   * Imports the test data.
   *
   * @param string $base_url
   *   The base URL of where the test is running.
   * @param \Drupal\sparql_entity_storage\Driver\Database\sparql\ConnectionInterface $sparql
   *   The Sparql connection.
   * @param string $test
   *   The test these are used for. Separate graphs for behat and phpunit.
   */
  protected function import(string $base_url, ConnectionInterface $sparql, string $test): void {
    $graphs = $this->getTestGraphInfo($base_url, $test);
    foreach ($graphs as $info) {
      $graph = new Graph($info['data']);
      $graph->load();
      $graph_uri = SparqlArg::uri($info['uri']);
      $query = "INSERT DATA INTO $graph_uri {\n";
      $query .= $graph->serialise('ntriples') . "\n";
      $query .= '}';
      $sparql->update($query);
    }
  }

  /**
   * Clears the test data.
   *
   * @param string $base_url
   *   The base URL of where the test is running.
   * @param \Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface $sparql
   *   The Sparql connection.
   * @param string $test
   *   The test these are used for. Separate graphs for behat and phpunit.
   */
  protected function clear(string $base_url, ConnectionInterface $sparql, string $test): void {
    $graphs = $this->getTestGraphInfo($base_url, $test);
    foreach ($graphs as $info) {
      $query = <<<EndOfQuery
DELETE {
  GRAPH <{$info['uri']}> {
    ?entity ?field ?value
  }
}
WHERE {
  GRAPH <{$info['uri']}> {
    ?entity ?field ?value
  }
}
EndOfQuery;

      $sparql->query($query);
    }
  }

  /**
   * Returns the info about where the test RDF data is.
   *
   * @param string $base_url
   *   The base URL of where the test is running.
   * @param string $test
   *   The test these are used for. Separate graphs for behat and phpunit.
   *
   * @return array
   *   The graph info.
   */
  protected function getTestGraphInfo(string $base_url, string $test): array {
    return [
      'fruit' => [
        'uri' => "http://example.com/fruit/$test",
        'data' => "$base_url/modules/custom/rdf_skos/tests/test_rdf/fruit.rdf",
      ],
      'vegetables' => [
        'uri' => "http://example.com/vegetables/$test",
        'data' => "$base_url/modules/custom/rdf_skos/tests/test_rdf/vegetables.rdf",
      ],
    ];
  }

  /**
   * Configures the SKOS entities to read from a certain graph.
   *
   * @param string $name
   *   The name of the graph.
   */
  protected function enableGraph(string $name) {
    $base_url = $_ENV['SIMPLETEST_BASE_URL'];
    $graphs = [];
    $info = $this->getTestGraphInfo($base_url, 'phpunit');
    $graphs[$name] = $info[$name]['uri'];

    \Drupal::service('rdf_skos.skos_graph_configurator')->addGraphs($graphs);
  }

}
