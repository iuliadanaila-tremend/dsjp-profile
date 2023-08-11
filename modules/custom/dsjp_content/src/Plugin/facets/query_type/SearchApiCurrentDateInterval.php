<?php

namespace Drupal\dsjp_content\Plugin\facets\query_type;

use Drupal\facets\QueryType\QueryTypePluginBase;
use Drupal\facets\Result\Result;
use Drupal\search_api\Query\QueryInterface;

/**
 * Provides support for range facets within the Search API scope.
 *
 * This is the default implementation that works with all backends.
 *
 * @FacetsQueryType(
 *   id = "search_api_current_date_interval",
 *   label = @Translation("Current Date Interval"),
 * )
 */
class SearchApiCurrentDateInterval extends QueryTypePluginBase {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $query = $this->query;

    // Only alter the query when there's an actual query object to alter.
    if ($query) {
      $operator = $this->facet->getQueryOperator();
      $field_identifier = $this->facet->getFieldIdentifier();

      if ($query->getProcessingLevel() === QueryInterface::PROCESSING_FULL) {
        // Set the options for the actual query.
        $options = &$query->getOptions();
        $options['search_api_facets'][$field_identifier] = $this->getFacetOptions();
      }

      // Add the filter to the query if there are active values.
      $active_items = $this->facet->getActiveItems();
      if (count($active_items)) {
        $filter = $query->createConditionGroup($operator, ['facet:' . $field_identifier]);

        foreach ($active_items as $value) {
          if ($value == 'ended') {
            $filter->addCondition($field_identifier, time(), '<');
          }
          if ($value == 'active') {
            $filter->addCondition($field_identifier, time(), '>=');
          }
        }
        $query->addConditionGroup($filter);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $query_operator = $this->facet->getQueryOperator();

    $active_count = $past_count = 0;
    if (!empty($this->results)) {
      foreach ($this->results as $result) {
        if ($result['count'] || $query_operator === 'or') {
          $result_filter = trim($result['filter'], '"');
          if ((int) $result_filter < time()) {
            $past_count++;
          }
          else {
            $active_count++;
          }
          if ($result_filter === 'NULL' || $result_filter === '') {
            // "Missing" facet items could not be handled in ranges.
            continue;
          }
        }
      }

      // Group in past and future results.
      $past_result = new Result($this->facet, 'ended', $this->t('Ended'), $past_count);
      $future_result = new Result($this->facet, 'active', $this->t('Active'), $active_count);
      $facet_results = [
        $past_result,
        $future_result,
      ];

      $this->facet->setResults($facet_results);
    }

    return $this->facet;
  }

}
