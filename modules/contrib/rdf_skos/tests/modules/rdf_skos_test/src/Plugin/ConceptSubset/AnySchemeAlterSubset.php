<?php

declare(strict_types = 1);

namespace Drupal\rdf_skos_test\Plugin\ConceptSubset;

use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\rdf_skos\ConceptSubsetPluginBase;

/**
 * Test plugin that can alter the query of any scheme.
 *
 * @ConceptSubset(
 *   id = "any_alter",
 *   label = @Translation("Any alter"),
 *   description = @Translation("Alters any concept scheme queries.")
 * )
 */
class AnySchemeAlterSubset extends ConceptSubsetPluginBase {

  /**
   * {@inheritdoc}
   */
  public function alterQuery(QueryInterface $query, $match_operator, array $concept_schemes = [], string $match = NULL): void {
    // We don't actually alter the query but use this plugin to test that it
    // is always available.
  }

}
