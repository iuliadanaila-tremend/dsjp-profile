<?php

declare(strict_types = 1);

namespace Drupal\rdf_skos\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;

/**
 * Plugin implementation of the 'skos_concept_entity_reference_label' formatter.
 *
 * @FieldFormatter(
 *   id = "skos_concept_entity_reference_label",
 *   label = @Translation("SKOS Concept Label"),
 *   description = @Translation("Display the label of the referenced concepts."),
 *   field_types = {
 *     "skos_concept_entity_reference"
 *   }
 * )
 */
class SkosConceptEntityReferenceLabelFormatter extends EntityReferenceLabelFormatter {}
