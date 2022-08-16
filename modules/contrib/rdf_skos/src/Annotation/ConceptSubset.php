<?php

declare(strict_types = 1);

namespace Drupal\rdf_skos\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the concept_subset annotation object.
 *
 * @Annotation
 */
class ConceptSubset extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * Whether this plugin maps predicates to Drupal base fields.
   *
   * Optional.
   *
   * @var bool
   */
  public $predicate_mapping;

  /**
   * The concept schemes this plugin should work with.
   *
   * Optional. Leaving empty means it works with all concept schemes.
   *
   * @var array
   */
  public $concept_schemes;

}
