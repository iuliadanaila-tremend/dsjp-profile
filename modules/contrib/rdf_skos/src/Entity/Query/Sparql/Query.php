<?php

declare(strict_types = 1);

namespace Drupal\rdf_skos\Entity\Query\Sparql;

use Drupal\Core\Entity\Query\ConditionInterface;
use Drupal\sparql_entity_storage\Entity\Query\Sparql\Query as OriginalQuery;

/**
 * Specific Query class for the SKOS entities.
 */
class Query extends OriginalQuery {

  /**
   * {@inheritdoc}
   */
  protected function conditionGroupFactory($conjunction = 'AND'): ConditionInterface {
    $class = static::getClass($this->namespaces, 'SparqlCondition');

    return new $class($conjunction, $this, $this->namespaces, $this->fieldHandler, $this->languageManager);
  }

}
