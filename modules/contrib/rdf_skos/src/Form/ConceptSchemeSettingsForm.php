<?php

declare(strict_types = 1);

namespace Drupal\rdf_skos\Form;

/**
 * Settings form for the SKOS Concept Scheme entity.
 */
class ConceptSchemeSettingsForm extends SkosEntitySettingsForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'skos_concept_scheme.settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityTypeId(): string {
    return 'skos_concept_scheme';
  }

}
