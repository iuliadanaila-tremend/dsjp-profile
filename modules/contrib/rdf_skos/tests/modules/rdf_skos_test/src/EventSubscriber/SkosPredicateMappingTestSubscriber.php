<?php

declare(strict_types = 1);

namespace Drupal\rdf_skos_test\EventSubscriber;

use Drupal\rdf_skos\Event\SkosPredicateMappingEvent;
use Drupal\sparql_entity_storage\SparqlEntityStorageFieldHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to the SKOS predicate mapping event.
 *
 * The purpose is to map a single predicate to two separately defined base
 * fields and ensure that it works.
 */
class SkosPredicateMappingTestSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[SkosPredicateMappingEvent::EVENT][] = ['onPredicateMapping', 20];
    return $events;
  }

  /**
   * Maps a predicate to a custom base field on the Skos Concept.
   *
   * @param \Drupal\rdf_skos\Event\SkosPredicateMappingEvent $event
   *   The event.
   *
   * @see \oe_content_organisation_entity_base_field_info()
   */
  public function onPredicateMapping(SkosPredicateMappingEvent $event): void {
    $mapping = $event->getMapping();
    $entity_type_id = $event->getEntityTypeId();

    if ($entity_type_id === 'skos_concept') {
      $mapping['fields']['dummy_field_one'] = [
        'column' => 'value',
        'predicate' => ['http://www.w3.org/2004/02/skos/core#dummy'],
        'format' => SparqlEntityStorageFieldHandler::TRANSLATABLE_LITERAL,
      ];

      $mapping['fields']['dummy_field_two'] = [
        'column' => 'value',
        'predicate' => ['http://www.w3.org/2004/02/skos/core#dummy'],
        'format' => SparqlEntityStorageFieldHandler::TRANSLATABLE_LITERAL,
      ];
    }

    $event->setMapping($mapping);
  }

}
