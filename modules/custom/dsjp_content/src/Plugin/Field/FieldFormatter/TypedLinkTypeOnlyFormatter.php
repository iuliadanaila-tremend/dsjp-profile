<?php

namespace Drupal\dsjp_content\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\OptGroup;

/**
 * Plugin implementation of the 'TypedLinkTypeOnlyFormatter' formatter.
 *
 * @FieldFormatter(
 *   id = "typed_link_type_only",
 *   label = @Translation("Typed Link Type Only Formatter"),
 *   description = @Translation("Show the type field of the current typed_link field."),
 *   field_types = {
 *     "typed_link"
 *   }
 * )
 */
class TypedLinkTypeOnlyFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    // Only collect allowed options if there are actually items to display.
    if ($items->count()) {
      $provider = $items->getFieldDefinition()
        ->getFieldStorageDefinition()
        ->getOptionsProvider('link_type', $items->getEntity());
      // Flatten the possible options, to support opt groups.
      $options = OptGroup::flattenOptions($provider->getPossibleOptions());

      foreach ($items as $delta => $item) {
        $value = $item->link_type;
        // If the stored value is in the current set of allowed values, display
        // the associated label, otherwise just display the raw value.
        $output = $options[$value] ?? $value;
        $elements[$delta]['type'] = [
          '#markup' => $output,
          '#allowed_tags' => FieldFilteredMarkup::allowedTags(),
        ];
      }
    }

    return $elements;
  }

}
