<?php

declare(strict_types = 1);

namespace Drupal\rdf_skos;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\sparql_entity_storage\SparqlEntityStorageGraphHandler;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Contains helper methods for managing the RDF entity graphs for SKOS.
 */
class RdfSkosGraphHandler extends SparqlEntityStorageGraphHandler {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a RDF graph handler object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $eventDispatcher, ConfigFactoryInterface $configFactory) {
    parent::__construct($entity_type_manager, $eventDispatcher);
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function getGraphDefinitions(string $entity_type_id): array {
    if (isset($this->cache['definition'][$entity_type_id])) {
      return $this->cache['definition'][$entity_type_id];
    }

    $config = $this->configFactory->get('rdf_skos.graphs');
    if (!$config || !$config->get('entity_types.' . $entity_type_id)) {
      $this->cache['definition'][$entity_type_id] = [
        'nonexistent' => [
          'title' => 'Inexistent Graph',
          'description' => 'This graph does not exist but we need to include something to prevent Sparql from going over all graphs.',
        ],
      ];
      return $this->cache['definition'][$entity_type_id];
    }

    foreach ($config->get('entity_types.' . $entity_type_id) as $graph) {
      $this->cache['definition'][$entity_type_id][$graph['name']] = [
        // Empty because we don't have any meta info about those graphs.
        'title' => '',
        'description' => '',
      ];
    }

    return $this->cache['definition'][$entity_type_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeGraphUris(string $entity_type_id, array $limit_to_graph_ids = NULL): array {
    if (isset($this->cache['structure'][$entity_type_id])) {
      return $this->cache['structure'][$entity_type_id];
    }

    $config = $this->configFactory->get('rdf_skos.graphs');
    if (!$config || !$config->get('entity_types.' . $entity_type_id)) {
      $this->cache['structure'][$entity_type_id][$entity_type_id]['nonexistent'] = 'http://this-graph-does-not-exist';
      return $this->cache['structure'][$entity_type_id];
    }

    foreach ($config->get('entity_types.' . $entity_type_id) as $graph) {
      // Double up to mimic the bundle.
      $this->cache['structure'][$entity_type_id][$entity_type_id][$graph['name']] = $graph['uri'];
    }

    // Limit the results.
    if ($limit_to_graph_ids) {
      return array_map(function (array $graphs) use ($limit_to_graph_ids): array {
        return array_intersect_key($graphs, array_flip($limit_to_graph_ids));
      }, $this->cache['structure'][$entity_type_id]);
    }

    return $this->cache['structure'][$entity_type_id];
  }

}
