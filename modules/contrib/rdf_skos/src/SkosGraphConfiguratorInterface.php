<?php

declare(strict_types = 1);

namespace Drupal\rdf_skos;

/**
 * Defines the interface for the Skos graph service.
 */
interface SkosGraphConfiguratorInterface {

  /**
   * Add skos graphs.
   *
   * @param array $graphs
   *   A list of graph URIs, keyed by graph names.
   */
  public function addGraphs(array $graphs = []): void;

}
