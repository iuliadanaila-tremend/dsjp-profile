<?php

namespace Drupal\dsjp_responsive_background_image;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Render\Markup;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\media\Entity\Media as MediaAlias;
use Drupal\media_entity\Entity\Media;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;

/**
 * ResponsiveBackgroundImage class.
 *
 * Provides a method to generate a responsive background image
 * using a Responsive Image Style.
 */
class ResponsiveBackgroundImage {

  /**
   * Generates a Drupal style tag array.
   *
   * The array contains CSS media queries to apply
   * a responsive background image to a specific DOM element/node.
   * The return value must be assigned correctly.
   * See the return description below.
   *
   * @param string $css_selector
   *   A CSS selector that points to the HTML tag to which a background image
   *   will be applied. Do not include curly braces/brackets.
   *   This selector should be unique to avoid the same background image being
   *   accidentally applied to multiple elements. For example,
   *   if you have a Paragraph type of Hero, you should add a class containing
   *   the entity ID to the Paragraph template, and then
   *   pass in that class as part of the selector here. This way multiple
   *   instances of the Hero Paragraph can appear on the same page with
   *   different background images.
   *   For example: '.paragraph--id--3 .hero__image', where '3' is the entity ID
   *   retrieved from the entity using $paragraph_entity->id().
   * @param Drupal\Core\Entity\ContentEntityBase $entity
   *   An instance of Drupal\Core\Entity\ContentEntityBase which contains
   *   the image field.
   *   Preferably this method has been called from
   *   within a preprocess hook such as THEME_preprocess_paragraph(&$vars),
   *   in which case the entity can be retrieved using $vars['paragraph'].
   * @param string $field_machine_name
   *   The machine name of the image field.
   *   For example: 'field_hero_background_image'.
   * @param string $responsive_image_style_machine_name
   *   The machine name of the responsive image style.
   * @param string $media_entity_field_machine_name
   *   Optional. If the image field is a Media field, and the Image field on the
   *   Image Media Type is custom and not the default 'field_media_image',
   *   pass in the custom field machine name.
   *
   * @return array
   *   A Drupal style tag array containing CSS media queries to apply responsive
   *   background images to a specific HTML tag.
   *   Assuming that this method has been called from inside a preprocess
   *   function such as THEME_preprocess_paragraph(&$vars),
   *   the return value should be assigned to $vars['#attached']['html_head'][],
   *   or else calling this method will have no effect.
   *   Returns false if media queries cannot be generated.
   */
  public static function generateMediaQueries(string $css_selector, ContentEntityBase $entity, string $field_machine_name, string $responsive_image_style_machine_name, string $media_entity_field_machine_name = 'field_media_image') {

    // Get field type.
    $field_type = $entity->get($field_machine_name)->getFieldDefinition()->getType();

    // @todo Take into account if image field is hidden in display settings.
    // If field type is an entity_reference
    // we'll assume this is a Media Image field.
    if ($field_type == 'entity_reference') {
      $moduleHandler = \Drupal::service('module_handler');
      if ($moduleHandler->moduleExists('media')) {
        $media_entity = MediaAlias::load(
          $entity->get($field_machine_name)
            ->getValue()[0]['target_id']
        );

        $file_entity = File::load(
          $media_entity->get($media_entity_field_machine_name)
            ->getValue()[0]['target_id']
        );
      }
      elseif ($moduleHandler->moduleExists('media_entity')) {
        // @phpstan-ignore-next-line
        $media_entity = Media::load(
          $entity->get($field_machine_name)
            ->getValue()[0]['target_id']
        );

        $file_entity = File::load(
          $media_entity->get($media_entity_field_machine_name)
            ->getValue()[0]['target_id']
        );
      }
    }
    elseif ($field_type == 'image') {
      $file_entity = File::load($entity->get($field_machine_name)->getValue()[0]['target_id']);
    }
    else {
      \Drupal::logger('responsive_background_image')->error('Responsive Background Image field must be of type Image or Media Image.');
      return FALSE;
    }

    // @phpstan-ignore-next-line
    $uri = $file_entity->getFileUri();

    // Load Responsive Image Style and mappings.
    $responsiveImageStyle = ResponsiveImageStyle::load($responsive_image_style_machine_name);
    $image_style_mappings = array_reverse($responsiveImageStyle->getImageStyleMappings());

    // Load theme breakpoints.
    $breakpoint_group = $responsiveImageStyle->getBreakpointGroup();
    $breakpoints = \Drupal::service('breakpoint.manager')
      ->getBreakpointsByGroup($breakpoint_group);

    $media_queries_1x = '';
    $media_queries_2x = '';

    // Loop through image style mappings starting from
    // smallest to largest and build media queries.
    foreach ($image_style_mappings as $key => $image_style_mapping) {
      // If this is the first mapping and if a fallback image style is set,
      // add a default background image to media query.
      if ($key == 0) {
        $fallback_image_style = $responsiveImageStyle->getFallbackImageStyle();
        $disallowed_options = [
          '_empty image_',
          '_original image_',
        ];
        if (!in_array($fallback_image_style, $disallowed_options)) {
          $media_queries_1x .= self::createFallbackMediaQuery(
            $css_selector,
            \Drupal::service('file_url_generator')->transformRelative(ImageStyle::load($responsiveImageStyle->getFallbackImageStyle())
              ->buildUrl($uri))
          );
        }
      }

      // Load media query for breakpoint.
      $media_query = $breakpoints[$image_style_mapping['breakpoint_id']]->getMediaQuery();

      // Get path to image using image style.
      $image_path = \Drupal::service('file_url_generator')->transformRelative(ImageStyle::load($image_style_mapping['image_mapping'])
        ->buildUrl($uri));

      // If multiplier is 1x.
      if ($image_style_mapping['multiplier'] == '1x') {
        $media_queries_1x .= self::createSingleMediaQuery($media_query, $css_selector, $image_path, '1x');
      }
      // If multiplier is greater than 1x.
      // @todo Should we actually pass the exact multiplier to the media query?
      else {
        $media_queries_2x .= self::createSingleMediaQuery($media_query, $css_selector, $image_path, '2x');
      }
    }

    $all_media_queries = $media_queries_1x . $media_queries_2x;

    // Create unique id for use in style tag machine name.
    $entity_id = $entity->id();
    $uuid = \Drupal::service('uuid')->generate();

    // Return style tag array.
    return [
    [
      '#tag' => 'style',
      // We have to add this as insecure markup, otherwise auto-escaping
      // escapes the & to &amp; and breaks image urls.
      // See https://www.drupal.org/project/responsive_background_image/issues/3067838#comment-13208830
      '#value' => Markup::create($all_media_queries),
      // Should be last element of <head>, currently impossible
      // due to core issue #2391025, so we'll at least set this as
      // high as possible behind the meta tags,
      // but it won't get behind the <title>.
      '#weight' => 99999,
    ],
      'responsive-background-image-' . $entity_id . '-' . $uuid,
    ];
  }

  /**
   * Function for creating a fallback query.
   *
   * @param string $css_selector
   *   CSS selector for element that will have background image.
   * @param string $fallback_image_path
   *   Path to the fallback image.
   *
   * @return string
   *   A CSS property for a fallback background image.
   */
  private static function createFallbackMediaQuery(string $css_selector, string $fallback_image_path) {
    return '
  ' . $css_selector . ' {
    background-image: url(' . $fallback_image_path . ');
  }';
  }

  /**
   * Function for creating single query.
   *
   * @param string $media_query
   *   CSS media query from Breakpoint group config.
   * @param string $css_selector
   *   CSS selector for element that will have background image.
   * @param string $image_path
   *   Path to image.
   * @param string $multiplier
   *   Responsive image multiplier/pixel ratio.
   *
   * @return string
   *   A single CSS media query for one one window width
   *   and one multiplier/pixel ratio.
   */
  private static function createSingleMediaQuery(string $media_query, string $css_selector, string $image_path, string $multiplier) {
    switch ($multiplier) {
      case '1x':
        $min_pixel_ratio = '';
        break;

      default:
        $min_pixel_ratio = 'and (min-device-pixel-ratio: 1.5)';
        break;
    }

    return '
  @media ' . $media_query . $min_pixel_ratio . ' {
    ' . $css_selector . ' {
    background-image: url(' . $image_path . ');
    }
  }';
  }

}
