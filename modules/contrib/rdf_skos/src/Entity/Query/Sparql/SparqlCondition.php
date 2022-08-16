<?php

declare(strict_types = 1);

namespace Drupal\rdf_skos\Entity\Query\Sparql;

use Drupal\sparql_entity_storage\Entity\Query\Sparql\SparqlCondition as OriginalSparqlCondition;

/**
 * SPARQL condition for SKOS entities.
 */
class SparqlCondition extends OriginalSparqlCondition {

  /**
   * {@inheritdoc}
   */
  protected function compileBundleCondition($condition): void {
    $this->addConditionFragment(self::ID_KEY . ' ' . $this->escapePredicate($this->fieldMappings[$condition['field']]) . ' ' . $this->getBundleConditionValue());
  }

  /**
   * Returns the value for filtering the SKOS entity types.
   *
   * @return string
   *   The condition value.
   */
  protected function getBundleConditionValue(): string {
    $entity_type = $this->query->getEntityType();
    $map = [
      'skos_concept_scheme' => '<http://www.w3.org/2004/02/skos/core#ConceptScheme>',
      'skos_concept' => '<http://www.w3.org/2004/02/skos/core#Concept>',
    ];
    return $map[$entity_type->id()];
  }

}
