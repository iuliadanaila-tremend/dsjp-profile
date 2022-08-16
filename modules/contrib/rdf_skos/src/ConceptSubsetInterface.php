<?php

declare(strict_types = 1);

namespace Drupal\rdf_skos;

use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Interface for concept_subset plugins.
 */
interface ConceptSubsetInterface {

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated title.
   */
  public function label(): string;

  /**
   * Alters the query of the selection plugin.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The query to alter.
   * @param string $match_operator
   *   The matching operator.
   * @param array $concept_schemes
   *   The selected concept schemes.
   * @param string|null $match
   *   The value to match.
   */
  public function alterQuery(QueryInterface $query, $match_operator, array $concept_schemes = [], string $match = NULL): void;

}
