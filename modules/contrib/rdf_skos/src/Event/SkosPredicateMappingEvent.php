<?php

declare(strict_types = 1);

namespace Drupal\rdf_skos\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event dispatched to gather custom predicate mappings for SKOS entities.
 */
class SkosPredicateMappingEvent extends Event {

  /**
   * Event name dispatched to gather predicate mappings for SKOS entities.
   */
  const EVENT = 'rdf_skos_field_handler.predicate_mapping';

  /**
   * The predicate mapping.
   *
   * @var array
   */
  protected $mapping = [];

  /**
   * The entity type ID.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * SkosPredicateMappingEvent constructor.
   *
   * @param string $entityTypeId
   *   The entity type ID.
   */
  public function __construct(string $entityTypeId) {
    $this->entityTypeId = $entityTypeId;
  }

  /**
   * Returns the predicate mapping.
   *
   * @return array
   *   The mapping.
   */
  public function getMapping(): array {
    return $this->mapping;
  }

  /**
   * Sets the predicate mapping.
   *
   * @param array $mapping
   *   The mapping.
   */
  public function setMapping(array $mapping): void {
    $this->mapping = $mapping;
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

}
