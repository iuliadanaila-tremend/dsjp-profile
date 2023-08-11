<?php

namespace Drupal\dsjp_content\Plugin\facets\processor;

use Drupal\Core\Cache\UnchangingCacheableDependencyTrait;
use Drupal\facets\Processor\ProcessorPluginBase;

/**
 * Provides a processor for granularity.
 *
 * @FacetsProcessor(
 *   id = "interval_date_processor",
 *   label = @Translation("Current date interval processor"),
 *   description = @Translation("Group dates before the current date and after."),
 * )
 */
class CurrentDateIntervalProcessor extends ProcessorPluginBase {

  use UnchangingCacheableDependencyTrait;

  /**
   * {@inheritdoc}
   */
  public function getQueryType() {
    return 'current_date_interval';
  }

}
