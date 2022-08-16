<?php

declare(strict_types = 1);

namespace Drupal\rdf_skos;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Represents a concept subset plugin manager.
 */
interface ConceptSubsetPluginManagerInterface extends PluginManagerInterface {

  /**
   * Returns all the plugin definitions that map predicates to Drupal fields.
   *
   * @return array
   *   The definitions.
   */
  public function getPredicateMappingDefinitions(): array;

  /**
   * Returns all the plugin definitions that apply to a set of concept schemes.
   *
   * @param array $concept_schemes
   *   The  selected concept scheme IDs.
   *
   * @return array
   *   The definitions.
   */
  public function getApplicableDefinitions(array $concept_schemes): array;

}
