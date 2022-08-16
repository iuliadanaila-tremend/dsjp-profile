<?php

declare(strict_types = 1);

namespace Drupal\rdf_skos\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\sparql_entity_storage\Event\ActiveGraphEvent;
use Drupal\sparql_entity_storage\Event\SparqlEntityStorageEvents;
use Drupal\sparql_entity_storage\SparqlEntityStorageGraphHandlerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Ensure the correct graph is used for reading the SKOS entities.
 *
 * We use this when the SKOS entities are param converted to ensure that for
 * the SKOS entity types, only the correct graphs are used.
 *
 * @see \Drupal\sparql_entity_storage\ParamConverter\SparqlEntityStorageConverter
 */
class SkosActiveGraphSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The RDF graph handler service.
   *
   * @var \Drupal\sparql_entity_storage\SparqlEntityStorageGraphHandlerInterface
   */
  protected $rdfGraphHandler;

  /**
   * Constructs a new event subscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\sparql_entity_storage\SparqlEntityStorageGraphHandlerInterface $rdf_graph_handler
   *   The RDF graph handler service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, SparqlEntityStorageGraphHandlerInterface $rdf_graph_handler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->rdfGraphHandler = $rdf_graph_handler;
  }

  /**
   * Set the appropriate graph as an active graph for the SKOS entities.
   *
   * @param \Drupal\sparql_entity_storage\Event\ActiveGraphEvent $event
   *   The event object to process.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown when the access is denied and redirects to user login page.
   */
  public function graphForEntityConvert(ActiveGraphEvent $event): void {
    $entity_type_id = $event->getEntityTypeId();
    if (!in_array($entity_type_id, ['skos_concept_scheme', 'skos_concept'])) {
      return;
    }

    // By default, we look in all graphs.
    $graphs = $this->rdfGraphHandler->getEntityTypeGraphIds($entity_type_id);
    $event->setGraphs($graphs);
    $event->stopPropagation();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      SparqlEntityStorageEvents::GRAPH_ENTITY_CONVERT => [
        'graphForEntityConvert',
        100,
      ],
    ];
  }

}
