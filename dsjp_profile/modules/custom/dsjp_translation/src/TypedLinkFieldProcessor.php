<?php

namespace Drupal\dsjp_translation;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\tmgmt_content\DefaultFieldProcessor;

/**
 * Provides a tmgmt field processor for typed_link fields.
 */
class TypedLinkFieldProcessor extends DefaultFieldProcessor {

  /**
   * {@inheritdoc}
   */
  public function extractTranslatableData(FieldItemListInterface $field) {
    $data = parent::extractTranslatableData($field);
    if (isset($data[0])) {
      // Exclude the uri and link_type subfields of the typed_link field.
      foreach ($data[0] as $key => &$property) {
        if (in_array($key, ['uri', 'link_type'])) {
          $property['#translate'] = FALSE;
        }
      }
    }

    return $data;
  }

}
