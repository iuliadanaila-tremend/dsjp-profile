<?php

declare(strict_types = 1);

namespace Drupal\rdf_skos_test\Plugin\ConceptSubset;

use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\rdf_skos\ConceptSubsetPluginBase;

/**
 * Test plugin that only works with Fruit and Vegetable concepts.
 *
 * @ConceptSubset(
 *   id = "multi_alter",
 *   label = @Translation("multi alter"),
 *   description = @Translation("Alters the fruit and vegetables concept queries."),
 *   concept_schemes = {
 *     "http://example.com/fruit",
 *     "http://example.com/vegetables",
 *   },
 * )
 */
class MultiSchemeAlterSubset extends ConceptSubsetPluginBase {

  /**
   * {@inheritdoc}
   */
  public function alterQuery(QueryInterface $query, $match_operator, array $concept_schemes = [], string $match = NULL): void {
    // Perform no alteration.
  }

}
