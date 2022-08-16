<?php

declare(strict_types = 1);

namespace Drupal\rdf_skos\Plugin;

/**
 * Interface for ConceptSubset plugins that need to map predicates.
 *
 * Sometimes, in order to determine a concept subset, new custom predicates
 * need to be mapped to the triple data that does not technically adhere to
 * the SKOS notation.
 *
 * This mapping needs to be done between a predicate and a base field on the
 * Drupal entity that will be hydrated with the data value.
 */
interface PredicateMapperInterface {

  /**
   * Returns an array of predicate mappings.
   *
   * @return array
   *   The predicate mappings.
   */
  public function getPredicateMapping(): array;

  /**
   * Returns the base field definitions that map to the predicates.
   *
   * @return array
   *   The base field definitions.
   */
  public function getBaseFieldDefinitions(): array;

}
