<?php

declare(strict_types = 1);

namespace Drupal\rdf_skos\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event dispatched to alter the processed graph results.
 */
class SkosProcessGraphResultsEvent extends Event {

  /**
   * Event name to alter the processed graph results for SKOS entities.
   */
  const ALTER = 'rdf_skos.process_graph_results_alter';

  /**
   * The processed results.
   *
   * @var array
   */
  protected $results = [];

  /**
   * The entity type ID.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * Returns the processed results.
   *
   * @return array
   *   The results.
   */
  public function getResults(): array {
    return $this->results;
  }

  /**
   * Sets the processed results.
   *
   * @param array $results
   *   The results.
   */
  public function setResults(array $results): void {
    $this->results = $results;
  }

  /**
   * Returns the entity type ID the mapping applies to.
   *
   * @return string
   *   The entity type ID.
   */
  public function getEntityTypeId(): string {
    return $this->entityTypeId;
  }

  /**
   * Sets the entity type ID the mapping applies to.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   */
  public function setEntityTypeId(string $entity_type_id): void {
    $this->entityTypeId = $entity_type_id;
  }

}
