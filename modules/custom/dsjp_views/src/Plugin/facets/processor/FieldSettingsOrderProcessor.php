<?php

namespace Drupal\dsjp_views\Plugin\facets\processor;

use Drupal\facets\Processor\SortProcessorPluginBase;
use Drupal\facets\Processor\SortProcessorInterface;
use Drupal\facets\Result\Result;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * A processor that orders the results by the field storage allowed values.
 *
 * @FacetsProcessor(
 *   id = "field_setting_processor_order",
 *   label = @Translation("Sort by field settings allowed values list"),
 *   description = @Translation("Sorts the widget results by the order of the field allowed values."),
 *   stages = {
 *     "sort" = 50
 *   }
 * )
 */
class FieldSettingsOrderProcessor extends SortProcessorPluginBase implements SortProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function sortResults(Result $a, Result $b) {
    $fieldId = $a->getFacet()->getFieldIdentifier();
    $settings = FieldStorageConfig::loadByName('node', $fieldId)->getSetting('allowed_values');
    $order = array_flip(array_keys($settings));

    return $order[$a->getRawValue()] > $order[$b->getRawValue()];
  }

}
