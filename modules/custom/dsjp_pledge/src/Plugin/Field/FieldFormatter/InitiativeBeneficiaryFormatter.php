<?php

namespace Drupal\dsjp_pledge\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'dsj_initiative_beneficiary' formatter.
 *
 * @FieldFormatter(
 *   id = "dsj_initiative_beneficiary_formatter",
 *   label = @Translation("Initiative Beneficiary Formatter"),
 *   field_types = {
 *     "dsj_initiative_beneficiary"
 *   }
 * )
 */
class InitiativeBeneficiaryFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      if ($item->status == 1) {
        $element[$delta] = ['#markup' => $item->value];
      }
    }

    return $element;
  }

}
