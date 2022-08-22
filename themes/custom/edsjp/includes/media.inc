<?php

/**
 * @file
 * Theme and preprocess functions for media.
 */

/**
 * Implements hook_preprocess_HOOK().
 */
function edsjp_preprocess_media(&$variables) {
  if ('image' === $variables['media']->bundle()) {
    // Add wrapper class for image media field.
    $variables['content']['oe_media_image']['#attributes']['class'][] = 'image-container';
  }
}
