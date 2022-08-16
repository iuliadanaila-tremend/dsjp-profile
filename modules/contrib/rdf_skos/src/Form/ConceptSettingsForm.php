<?php

declare(strict_types = 1);

namespace Drupal\rdf_skos\Form;

/**
 * Settings form for the SKOS Concept entity.
 */
class ConceptSettingsForm extends SkosEntitySettingsForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'skos_concept.settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityTypeId(): string {
    return 'skos_concept';
  }

}
