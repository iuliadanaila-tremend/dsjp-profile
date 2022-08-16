<?php

declare(strict_types = 1);

namespace Drupal\rdf_skos\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;

/**
 * Plugin for the 'skos_concept_entity_reference_autocomplete' widget.
 *
 * @FieldWidget(
 *   id = "skos_concept_entity_reference_autocomplete",
 *   label = @Translation("SKOS Concept Autocomplete"),
 *   description = @Translation("An autocomplete text field for SKOS Concepts."),
 *   field_types = {
 *     "skos_concept_entity_reference"
 *   }
 * )
 */
class SkosConceptEntityReferenceAutocompleteWidget extends EntityReferenceAutocompleteWidget {}
