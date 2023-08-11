<?php

namespace Drupal\dsjp_private_message\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'entity reference delta view' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_delta_view",
 *   label = @Translation("Rendered entity by delta"),
 *   description = @Translation("Display the referenced entities rendered by entity_view()."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class RenderedEntityDeltaFormatter extends EntityReferenceEntityFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'number_of_entities' => 1,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $elements['number_of_entities'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of entities to display'),
      '#default_value' => $this->getSetting('number_of_entities'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntitiesToView(EntityReferenceFieldItemListInterface $items, $langcode) {
    $entities = parent::getEntitiesToView($items, $langcode);
    if (empty($entities)) {
      return $entities;
    }
    $entities = array_reverse($entities);

    return array_slice($entities, 0, $this->getSetting('number_of_entities'));
  }

}
