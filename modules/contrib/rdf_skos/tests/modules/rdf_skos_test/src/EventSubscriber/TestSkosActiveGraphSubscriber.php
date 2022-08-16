<?php

declare(strict_types = 1);

namespace Drupal\rdf_skos_test\EventSubscriber;

use Drupal\sparql_entity_storage\Event\ActiveGraphEvent;
use Drupal\sparql_entity_storage\Event\SparqlEntityStorageEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Test subscriber that indiscriminately sets some random graphs.
 */
class TestSkosActiveGraphSubscriber implements EventSubscriberInterface {

  /**
   * Sets some dummy active graphs.
   *
   * @param \Drupal\sparql_entity_storage\Event\ActiveGraphEvent $event
   *   The event object to process.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown when the access is denied and redirects to user login page.
   */
  public function graphForEntityConvert(ActiveGraphEvent $event): void {
    $event->setGraphs(['test_graph']);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      SparqlEntityStorageEvents::GRAPH_ENTITY_CONVERT => ['graphForEntityConvert'],
    ];
  }

}
