<?php

declare(strict_types = 1);

namespace Drupal\rdf_skos\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface for defining SKOS Concept entities.
 */
interface ConceptInterface extends ContentEntityInterface {

  /**
   * Gets the Preferred Label.
   *
   * @return string
   *   Preferred Label of the SKOS Concept.
   */
  public function getPreferredLabel(): ?string;

  /**
   * Sets the Preferred Label.
   *
   * @param string $label
   *   The label.
   *
   * @return \Drupal\rdf_skos\Entity\ConceptInterface
   *   The called SKOS Concept entity.
   */
  public function setPreferredLabel(string $label): ConceptInterface;

  /**
   * Gets the Alternate Label.
   *
   * @return string
   *   Alternate Label of the SKOS Concept.
   */
  public function getAlternateLabel(): ?string;

  /**
   * Sets the Alternate Label.
   *
   * @param string $label
   *   The label.
   *
   * @return \Drupal\rdf_skos\Entity\ConceptInterface
   *   The called SKOS Concept entity.
   */
  public function setAlternateLabel(string $label): ConceptInterface;

  /**
   * Gets the Hidden Label.
   *
   * @return string
   *   Hidden Label of the SKOS Concept.
   */
  public function getHiddenLabel(): ?string;

  /**
   * Sets the Hidden Label.
   *
   * @param string $label
   *   The label.
   *
   * @return \Drupal\rdf_skos\Entity\ConceptInterface
   *   The called SKOS Concept entity.
   */
  public function setHiddenLabel(string $label): ConceptInterface;

  /**
   * Gets the Scope Note.
   *
   * @return string
   *   Scope note of the SKOS Concept.
   */
  public function getScopeNote(): ?string;

  /**
   * Gets the Definition.
   *
   * @return string
   *   Definition of the SKOS Concept.
   */
  public function getDefinition(): ?string;

  /**
   * Gets the History Note.
   *
   * @return string
   *   History note of the SKOS Concept.
   */
  public function getHistoryNote(): ?string;

  /**
   * Gets the Editorial Note.
   *
   * @return string
   *   Editorial note of the SKOS Concept.
   */
  public function getEditorialNote(): ?string;

  /**
   * Gets the Change Note.
   *
   * @return string
   *   Change note of the SKOS Concept.
   */
  public function getChangeNote(): ?string;

  /**
   * Gets the Example.
   *
   * @return string
   *   Example of the SKOS Concept.
   */
  public function getExample(): ?string;

  /**
   * Gets the Concept schemes it belongs to.
   *
   * @return \Drupal\rdf_skos\Entity\ConceptSchemeInterface[]
   *   The SKOS Concept Scheme.
   */
  public function getConceptSchemes(): array;

  /**
   * Sets the Concept scheme it belongs to.
   *
   * @param ConceptSchemeInterface[] $concept_schemes
   *   The SKOS Concept Scheme.
   *
   * @return \Drupal\rdf_skos\Entity\ConceptInterface
   *   The called SKOS Concept entity.
   */
  public function setConceptSchemes(array $concept_schemes): ConceptInterface;

  /**
   * Gets the Concept schemes it is the top concept of.
   *
   * @return \Drupal\rdf_skos\Entity\ConceptSchemeInterface[]
   *   The SKOS Concept Scheme.
   */
  public function topConceptOf(): array;

  /**
   * Gets the broader Concepts from the same Concept Scheme.
   *
   * @return \Drupal\rdf_skos\Entity\ConceptInterface[]
   *   The SKOS Concepts.
   */
  public function getBroader(): array;

  /**
   * Gets the narrower Concepts from the same Concept Scheme.
   *
   * @return \Drupal\rdf_skos\Entity\ConceptInterface[]
   *   The SKOS Concepts.
   */
  public function getNarrower(): array;

  /**
   * Gets the related Concepts from the same Concept Scheme.
   *
   * @return \Drupal\rdf_skos\Entity\ConceptInterface[]
   *   The SKOS Concepts.
   */
  public function getRelated(): array;

  /**
   * Gets the exact match Concepts from other Concept Schemes.
   *
   * @return \Drupal\rdf_skos\Entity\ConceptInterface[]
   *   The SKOS Concepts.
   */
  public function getExactMatch(): array;

  /**
   * Gets the close match Concepts from other Concept Schemes.
   *
   * @return \Drupal\rdf_skos\Entity\ConceptInterface[]
   *   The SKOS Concepts.
   */
  public function getCloseMatch(): array;

  /**
   * Gets the broader Concepts from other Concept Schemes.
   *
   * @return \Drupal\rdf_skos\Entity\ConceptInterface[]
   *   The SKOS Concepts.
   */
  public function getBroadMatch(): array;

  /**
   * Gets the narrower Concepts from other Concept Schemes.
   *
   * @return \Drupal\rdf_skos\Entity\ConceptInterface[]
   *   The SKOS Concepts.
   */
  public function getNarrowMatch(): array;

  /**
   * Gets the related Concepts from other Concept Schemes.
   *
   * @return \Drupal\rdf_skos\Entity\ConceptInterface[]
   *   The SKOS Concepts.
   */
  public function getRelatedMatch(): array;

}
