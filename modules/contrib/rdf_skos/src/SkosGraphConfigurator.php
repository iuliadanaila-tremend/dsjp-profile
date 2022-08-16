<?php

declare(strict_types = 1);

namespace Drupal\rdf_skos;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Service used to set up the SKOS graphs.
 */
class SkosGraphConfigurator implements SkosGraphConfiguratorInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * SkosGraphConfigurator constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function addGraphs(array $graphs = []): void {
    if (empty($graphs)) {
      return;
    }

    $config = $this->configFactory->getEditable('rdf_skos.graphs');
    $entity_types = $config->get('entity_types');
    $changed = FALSE;

    foreach ($graphs as $name => $graph) {
      $graph = ['name' => $name, 'uri' => $graph];

      foreach (['skos_concept_scheme', 'skos_concept'] as $type) {
        // Make sure the key exists.
        if (!isset($entity_types[$type])) {
          $entity_types[$type] = [];
        }

        // Find if the graph is already configured. If not, add it.
        $key = array_search($graph, $entity_types[$type]);
        if ($key === FALSE) {
          $entity_types[$type][] = $graph;
          $changed = TRUE;
        }
      }
    }

    // Save the configuration only if graphs were added.
    if ($changed) {
      $config->set('entity_types', $entity_types)->save();
    }
  }

}
