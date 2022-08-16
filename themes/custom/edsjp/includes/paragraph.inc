<?php

/**
 * @file
 * Theme and preprocess functions for paragraph.
 */

use Drupal\views\Views;
use Drupal\views\ViewExecutable;
use Drupal\dsjp_responsive_background_image\ResponsiveBackgroundImage;

/**
 * Implements hook_preprocess_HOOK().
 */
function edsjp_preprocess_paragraph(&$variables) {
  switch ($variables['paragraph']->bundle()) {
    case 'dsj_listing':
      _edsjp_preprocess_listing_component($variables);
      break;

    case 'dsj_top_banner':
    case 'dsj_cta_banner':
      _edsjp_preprocess_top_banner($variables);
      break;

    case 'dsj_widget':
      _edsjp_preprocess_widget_component($variables);
      break;

    case 'dsj_assessment':
      _edsjp_preprocess_assessment($variables);
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
    $view->setArguments([$content_types_arg]);
    $view->preExecute();
    if (count($content_type_values) > 1) {
      foreach ($content_type_values as $ct) {
        $query['f[' . $i++ . ']'] = 'content_type:' . $ct['value'];
      }
    }
  }

  $digital_technologies_values = $paragraph->get('field_dsj_digital_technology')
    ->getValue();
  _edsjp_preprocess_listing_component_view_filter($view, $query, $digital_technologies_values, $content_types_arg);

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
function _edsjp_preprocess_listing_component_view_filter(ViewExecutable &$view, array &$query, array $data, string $content_type): void {
  if ($content_type == 'dsj_pledge') {
    unset($view->filter['field_dsj_digital_technology_value']);
    return;
  }

  $digital_technologies = [];

  if (!empty($data)) {
    foreach ($data as $dt) {
      $query['f[' . $i++ . ']'] = 'digital_technology:' . $dt['value'];
      $digital_technologies[$dt['value']] = $dt['value'];
    }

    // Add the digital technologies filter.
    if (isset($view->filter['field_dsj_digital_technology_value'])) {
      $view->filter['field_dsj_digital_technology_value']->operator = 'or';
      $view->filter['field_dsj_digital_technology_value']->value = $digital_technologies;
    }
    else {
      unset($view->filter['field_dsj_digital_technology_value']);
    }
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
