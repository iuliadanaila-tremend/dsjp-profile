<?php

declare(strict_types = 1);

namespace Drupal\rdf_skos\Entity\Query\Sparql;

use Drupal\sparql_entity_storage\Entity\Query\Sparql\QueryFactory as OriginalQueryFactory;

/**
 * Provides a factory for creating entity query objects for SKOS entities.
 *
 * This is the service responsible for instantiating the Query object for the
 * storage handler and that resides in this same namespace.
 */
class QueryFactory extends OriginalQueryFactory {}
