<?php

namespace Drupal\typed_link\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\OptGroup;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;

/**
 * Defines the 'typed_link' field widget.
 *
 * @FieldWidget(
 *   id = "typed_link",
 *   label = @Translation("Typed Link Widget"),
 *   description = @Translation("Expands on the link widget adding a drop down for the asset type."),
 *   field_types = {"typed_link"},
 * )
 */
class TypedLinkWidget extends LinkWidget {

  private $column;
  private $required;
  private $options;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->column = $field_definition->getFieldStorageDefinition()->getMainPropertyName();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $this->required = $element['#required'];

    $element['link_type'] = [
      '#title' => $this->t('Link type'),
      '#type' => 'select',
      '#options' => $this->getOptions($items->getEntity()),
      '#default_value' => $this->getSelectedOption($items[$delta]),
      // Do not display a 'multiple' select box if there is only one option.
      '#multiple' => FALSE,
    ];

    return $element;
  }

  /**
   * Returns the array of options for the widget.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity for which to return options.
   *
   * @return array
   *   The array of options for the widget.
   */
  protected function getOptions(FieldableEntityInterface $entity) {
    if (!isset($this->options)) {
      // Limit the settable options for the current user account.
      $options = $this->fieldDefinition
        ->getFieldStorageDefinition()
        ->getOptionsProvider($this->column, $entity)
        ->getSettableOptions(\Drupal::currentUser());

      $module_handler = \Drupal::moduleHandler();
      $context = [
        'fieldDefinition' => $this->fieldDefinition,
        'entity' => $entity,
      ];
      $module_handler->alter('options_list', $options, $context);

      array_walk_recursive($options, [$this, 'sanitizeLabel']);

      $options = OptGroup::flattenOptions($options);

      $this->options = $options;
    }
    return $this->options;
  }

  /**
   * Determines selected options from the incoming field values.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field values.
   *
   * @return string
   *   The selected option.
   */
  protected function getSelectedOption(FieldItemInterface $item) {
    // We need to check against a flat list of options.
    $flat_options = OptGroup::flattenOptions($this->getOptions($item->getEntity()));

    $value = $item->{$this->column};
    $selected_option = NULL;
    // Keep the value if it actually is in the list of options (needs to be
    // checked against the flat list).
    if (isset($flat_options[$value])) {
      $selected_option = $value;
    }

    return $selected_option;
  }

  /**
   * {@inheritdoc}
   */
  protected function sanitizeLabel(&$label) {
    // Select form inputs allow unencoded HTML entities, but no HTML tags.
    $label = Html::decodeEntities(strip_tags($label));
  }

}
