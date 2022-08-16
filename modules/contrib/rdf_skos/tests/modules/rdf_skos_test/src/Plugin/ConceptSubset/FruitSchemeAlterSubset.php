<?php

declare(strict_types = 1);

namespace Drupal\rdf_skos_test\Plugin\ConceptSubset;

use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\rdf_skos\ConceptSubsetPluginBase;

/**
 * Test plugin that only works with Fruit concepts.
 *
 * @ConceptSubset(
 *   id = "fruit_alter",
 *   label = @Translation("Fruit alter"),
 *   description = @Translation("Alters the fruit concept queries."),
 *   concept_schemes = {
 *     "http://example.com/fruit"
 *   },
 * )
 */
class FruitSchemeAlterSubset extends ConceptSubsetPluginBase {

  /**
   * {@inheritdoc}
   */
  public function alterQuery(QueryInterface $query, $match_operator, array $concept_schemes = [], string $match = NULL): void {
    // Allow only one fruit in this subset.
    $query->condition('id', 'http://example.com/fruit/citrus-fruit');
  }

}
