<?php

declare(strict_types = 1);

namespace Drupal\rdf_skos\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface for defining SKOS Concept Scheme entities.
 */
interface ConceptSchemeInterface extends ContentEntityInterface {

  /**
   * Gets the Title.
   *
   * @return string
   *   Preferred Label of the SKOS Concept Scheme.
   */
  public function getTitle(): string;

  /**
   * Sets the Title.
   *
   * @param string $title
   *   The title.
   *
   * @return \Drupal\rdf_skos\Entity\ConceptSchemeInterface
   *   The called SKOS Concept Scheme entity.
   */
  public function setTitle(string $title): ConceptSchemeInterface;

  /**
   * Gets the top Concepts in this scheme.
   *
   * @return \Drupal\rdf_skos\Entity\ConceptInterface[]
   *   The top concepts.
   */
  public function getTopConcepts(): array;

}
