<?php

/**
 * @file
 * Theme and preprocess functions for sitemap.
 */

/**
 * Implements hook_preprocess_HOOK().
 */
function edsjp_preprocess_sitemap(&$variables) {
  // Alter the name of the homepage link on the sitemap page.
  if (isset($variables['sitemap_items'])) {
    foreach ($variables['sitemap_items'] as &$item) {
      foreach ($item['#content']['#items'] as &$link) {
        if ($link['url']->getRouteName() == '<front>') {
          $link['title'] = \Drupal::config('system.site')->get('name');
          break;
        }
      }
    }
  }
}
