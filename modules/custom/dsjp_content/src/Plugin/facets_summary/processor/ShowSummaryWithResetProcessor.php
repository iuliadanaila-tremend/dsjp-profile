<?php

namespace Drupal\dsjp_content\Plugin\facets_summary\processor;

use Drupal\facets\Entity\Facet;
use Drupal\facets_summary\FacetsSummaryInterface;
use Drupal\facets_summary\Processor\BuildProcessorInterface;
use Drupal\facets_summary\Processor\ProcessorPluginBase;

/**
 * Provides a processor that shows a summary of all selected facets with links.
 *
 * @SummaryProcessor(
 *   id = "show_summary_with_reset",
 *   label = @Translation("Show a summary of all selected facets with reset links"),
 *   description = @Translation("When checked, this facet will show an imploded list of all selected facets."),
 *   stages = {
 *     "build" = 20
 *   }
 * )
 */
class ShowSummaryWithResetProcessor extends ProcessorPluginBase implements BuildProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function build(FacetsSummaryInterface $facets_summary, array $build, array $facets) {
    $facets_config = $facets_summary->getFacets();

    if (!isset($build['#items'])) {
      return $build;
    }

    /** @var \Drupal\facets\Entity\Facet $facet */
    foreach ($facets as $facet) {
      if (empty($facet->getActiveItems())) {
        continue;
      }
      $items = $this->getActiveDisplayValues($facet, $build['#items']);
      if (!empty($items)) {
        $facet_summary = [
          '#theme' => 'facets_summary_facet',
          '#label' => $facets_config[$facet->id()]['label'],
          '#separator' => $facets_config[$facet->id()]['separator'],
          '#items' => $items,
          '#facet_id' => $facet->id(),
          '#facet_admin_label' => $facet->getName(),
        ];
        array_unshift($build['#items'], $facet_summary);
      }
    }
    return $build;
  }

  /**
   * Build list of facet result links based on facet id.
   *
   * @param \Drupal\facets\Entity\Facet $facet
   *   Facet object.
   * @param array $results
   *   Facet summary default results.
   *
   * @return array
   *   Array of facets summary links.
   */
  protected function getActiveDisplayValues(Facet $facet, array &$results) {
    $items = [];
    $facetId = $facet->id();
    foreach ($results as $id => $result) {
      if (isset($result['#title']['#facet'])
        && $result['#title']['#facet']->id() == $facetId) {
        $items[] = $result;
        unset($results[$id]);
      }
    }
    return $items;
  }

}
