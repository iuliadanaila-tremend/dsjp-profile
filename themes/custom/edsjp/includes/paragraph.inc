<?php

/**
 * @file
 * Theme and preprocess functions for paragraph.
 */

use Drupal\Core\Url;
use Drupal\dsjp_responsive_background_image\ResponsiveBackgroundImage;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * Implements hook_preprocess_HOOK().
 *
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 */
function edsjp_preprocess_paragraph(&$variables) {
  switch ($variables['paragraph']->bundle()) {
    case 'dsj_listing':
      _edsjp_preprocess_listing_component($variables);
      break;

    case 'dsj_top_banner':
    case 'dsj_cta_banner':
    case 'dsj_highlighted_listing':
      _edsjp_preprocess_top_banner($variables);
      break;

    case 'dsj_widget':
      _edsjp_preprocess_widget_component($variables);
      break;

    case 'dsj_events_community_quotes':
      _edsjp_preprocess_dsj_events_community_quotes_component($variables);
      break;

    case 'dsj_learning_material':
      _edsjp_preprocess_learning_material($variables);
      break;

    case 'dsj_assessment':
      _edsjp_preprocess_assessment($variables);

    case 'dsj_cards':
      _edsjp_preprocess_cards($variables);
  }
  $variables['attributes']['id'] = 'paragraph-id-' . $variables['paragraph']->id();
}

/**
 * Helper function for preprocessing assessments.
 */
function _edsjp_preprocess_assessment(&$variables) {
  $paragraph = $variables['paragraph'];
  $startDate = $paragraph->get('field_dsj_start_date')->getString();
  $variables['content']['field_dsj_data'][0]['#start_date'] = \Drupal::service('date.formatter')->format(strtotime($startDate), 'custom', 'd.m.Y', 'GMT');
}

/**
 * Helper function for preprocessing listing component.
 */
function _edsjp_preprocess_listing_component(&$variables) {
  $paragraph = $variables['paragraph'];
  $query = [];
  $i = 0;
  $view = Views::getView('dsj_paragraph_component_listing');
  $display = $variables['view_mode'] == 'default' ? 'dsj_component_listing' : 'dsj_component_listing_slider';
  $view->setDisplay($display);

  $content_type_values = $paragraph->get('field_dsj_content_types')
    ->getValue();
  $content_types_arg = '';
  if (!empty($content_type_values)) {
    $content_types_arg = implode('+', array_map(function ($entry) {
      return $entry['value'];
    }, $content_type_values));

    if (count($content_type_values) > 1) {
      foreach ($content_type_values as $ct) {
        $query['f[' . $i++ . ']'] = 'content_type:' . $ct['value'];
      }
    }
  }

  $digital_technologies_values = $paragraph->get('field_dsj_digital_technology')
    ->getValue();
  _edsjp_preprocess_listing_component_view_filter($view, $query, $digital_technologies_values, $content_types_arg, $i);

  $article_type_values = $paragraph->get('field_dsj_article_type')
    ->getValue();
  _edsjp_preprocess_listing_component_view_filter_article($view, $query, $article_type_values, $content_types_arg, $i);

  $paragraph_addition_categories = $paragraph->get('field_dsj_filters')
    ->getValue();
  if ($paragraph_addition_categories) {
    _edsjp_preprocess_listing_component_view_additional_filters($view, $query, $paragraph_addition_categories);
  }

  $view->setArguments([$content_types_arg]);
  $view->preExecute();

  $view->execute();
  if (!empty($view->result)) {
    $variables['content']['view'] = $view->buildRenderable($display, [$content_types_arg]);
  }

  $variables['content']['view']['#cache']['keys'] = [
    $display,
    $paragraph->id(),
    '[configuration]=' . hash('sha256', serialize($query)),
  ];
}

/**
 * Helper function for preprocessing listing component.
 */
function _edsjp_preprocess_listing_component_view_filter_article(ViewExecutable &$view, array &$query, array $article_type_values, string $content_types_arg, int &$query_index) {
  $filters = $view->display_handler->getOption('filters');
  // If this filter is not set , dont give error.
  // This should be the type of article.
  if (!isset($filters['tid_raw'])) {
    return FALSE;
  }

  if (in_array('dsj_article', explode('+', $content_types_arg))) {
    $article_type_filters = [];
    foreach ($article_type_values as $article_type) {
      $article_type_filters[$article_type['target_id']] = (int) $article_type['target_id'];
      $query['f[' . $query_index++ . ']'] = 'article_type:' . $article_type['target_id'];
    }
    // If no type was checked let the default behaviour.
    if (!empty($article_type_filters)) {
      // Use regular expression to simulate an in list operator.
      $filters['tid_raw']['operator'] = 'regular_expression';
      $filters['tid_raw']['value'] = [
        'min' => '',
        'max' => '',
        'value' => '(' . implode('|', $article_type_filters) . ')',
      ];
      $filters['tid_raw']['plugin_id'] = 'list_field';
      $view->display_handler->overrideOption('filters', $filters);
    }
  }
}

/**
 * Helper function for preprocessing listing component.
 */
function _edsjp_preprocess_listing_component_view_filter(ViewExecutable &$view, array &$query, array $data, string $content_type, int &$query_index): void {
  $filters = $view->display_handler->getOption('filters');
  if ($content_type == 'dsj_pledge') {
    unset($filters['field_dsj_digital_technology_value']);
    $view->display_handler->overrideOption('filters', $filters);
    return;
  }

  $digital_technologies = [];

  if (!empty($data)) {
    foreach ($data as $dt) {
      $query['f[' . $query_index++ . ']'] = 'digital_technology:' . $dt['value'];
      $digital_technologies[$dt['value']] = $dt['value'];
    }

    // Add the digital technologies filter.
    if (isset($view->filter['field_dsj_digital_technology_value'])) {
      $view->filter['field_dsj_digital_technology_value']->operator = 'or';
      $view->filter['field_dsj_digital_technology_value']->value = $digital_technologies;
      $view->display_handler->overrideOption('filters', $filters);
    }
  }
}

/**
 * Helper function to add additional filters from the listing paragraph.
 */
function _edsjp_preprocess_listing_component_view_additional_filters(&$view, &$query, $paragraph_addition_categories) {
  $additional_categories = unserialize($paragraph_addition_categories[0]['value'], ['allowed_classes' => FALSE]);
  if (!empty($additional_categories)) {
    $filters = $view->display_handler->getOption('filters');
    $listing_manager = \Drupal::service('dsj_content.listing_manager');
    $listing_manager->addViewFilters($filters, $query, $additional_categories, FALSE);
    $view->display_handler->overrideOption('filters', $filters);
  }
}

/**
 * Function for providing image as background.
 */
function _edsjp_preprocess_top_banner(&$variables) {
  $paragraph = $variables['paragraph'];
  $paragraph_id = $paragraph->id();
  $css_class = 'paragraph--id--' . $paragraph_id;
  $variables['attributes']['class'][] = $css_class;

  if (!empty($paragraph->get('field_dsj_image')
    ->getValue()[0]['target_id'])) {

    $css_selector = '.' . $css_class;
    $style_tag = ResponsiveBackgroundImage::generateMediaQueries($css_selector, $paragraph, 'field_dsj_image', 'dsj_banner', 'oe_media_image');
    if ($style_tag) {
      $variables['#attached']['html_head'][] = $style_tag;
    }
  }
}

/**
 * Function for preprocessing Widget Component.
 */
function _edsjp_preprocess_widget_component(&$variables) {
  $paragraph = $variables['paragraph'];
  $widget_type = $paragraph->get('field_dsj_type');
  if (!$widget_type->isEmpty()) {
    $variables['widget_type_class'] = 'widget_wrapper_' . $widget_type->value;
    switch ($widget_type->value) {
      case 'most_popular':
        $variables['widget'] = views_embed_view('dsj_most_popular', 'dsj_most_popular');
        break;

      case 'events_community':
        $variables['widget'] = views_embed_view('dsj_events', 'dsj_two_more');
        $variables['widget_community'] = views_embed_view('dsj_discussions', 'dsj_latest_two');
        break;
    }
  }

}

/**
 * Function for preprocessing Events community and testimonials Component.
 */
function _edsjp_preprocess_dsj_events_community_quotes_component(&$variables) {
  $variables['widget'] = views_embed_view('dsj_events', 'dsj_two_more');
  $variables['widget_community'] = views_embed_view('dsj_discussions', 'dsj_latest_two');
}

/**
 * Function for preprocessing Cards Component.
 */
function _edsjp_preprocess_cards(&$variables) {
  $paragraph = $variables['paragraph'];
  if ($paragraph->hasField('field_dsj_title')) {
    $paragraph_title = $paragraph->get('field_dsj_title');
    if (!$paragraph_title->isEmpty()) {
      $variables['attributes']['class'][] = 'has-title';
    }
  }
}

/**
 * Adds the learning material details from the referenced node.
 *
 * @param array $variables
 *   Preprocess variables.
 */
function _edsjp_preprocess_learning_material(array &$variables) {
  // Get the node referenced values. So we can intersect with paragraph fields
  // inside the template.
  if (isset($variables['content']['field_dsj_learning_material'][0]['#node'])) {
    $entity_type_manager = \Drupal::entityTypeManager();
    $node_view_builder = $entity_type_manager->getViewBuilder('node');
    $view_mode = $variables['content']['field_dsj_learning_material'][0]['#view_mode'];
    $view = $node_view_builder->view($variables['content']['field_dsj_learning_material'][0]['#node'], $view_mode);
    $build = $node_view_builder->build($view);
    $build['url'] = Url::fromRoute('entity.node.canonical', ['node' => $build['#node']->id()]);
    $variables['node_reference'] = $build;
    // Hide learning state form.
  }
}
