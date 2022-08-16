<?php

declare(strict_types = 1);

namespace Drupal\rdf_skos;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for concept_subset plugins.
 */
abstract class ConceptSubsetPluginBase extends PluginBase implements ConceptSubsetInterface {

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

}
