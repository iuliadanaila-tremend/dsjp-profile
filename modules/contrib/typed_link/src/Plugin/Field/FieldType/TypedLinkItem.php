<?php

namespace Drupal\typed_link\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\OptionsProviderInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\link\Plugin\Field\FieldType\LinkItem;
use Drupal\options\Plugin\Field\FieldType\ListStringItem;

/**
 * Defines the 'typed_link' field type.
 *
 * @FieldType(
 *   id = "typed_link",
 *   label = @Translation("Typed Link"),
 *   category = @Translation("General"),
 *   description = @Translation("Contains a link and a category so the category can be used for theming purposes."),
 *   default_widget = "typed_link",
 *   default_formatter = "typed_link"
 * )
 */
class TypedLinkItem extends LinkItem implements OptionsProviderInterface {

  /**
   * An string option field.
   *
   * @var \Drupal\options\Plugin\Field\FieldType\ListStringItem
   */
  protected $optionField;

  /**
   * {@inheritdoc}
   */
  public function __construct(DataDefinitionInterface $definition, $name = NULL, TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);
    $this->optionField = new ListStringItem($definition, $name, $parent);
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);
    $properties['link_type'] = DataDefinition::create('string')
      ->setLabel(t('Link Type'))
      ->addConstraint('Length', ['max' => 255])
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    $schema['columns']['link_type'] = [
      'type' => 'varchar',
      'length' => 255,
    ];
    $schema['indexes']['link_type'] = ['link_type'];
    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'allowed_values' => [],
      'allowed_values_function' => '',
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    return $this->optionField->storageSettingsForm($form, $form_state, $has_data);
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleValues(AccountInterface $account = NULL) {
    return $this->optionField->getPossibleValues($account);
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleOptions(AccountInterface $account = NULL) {
    return $this->optionField->getPossibleOptions($account);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableValues(AccountInterface $account = NULL) {
    return $this->optionField->getSettableValues($account);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableOptions(AccountInterface $account = NULL) {
    return $this->optionField->getSettableOptions($account);
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'link_type';
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values = parent::generateSampleValue($field_definition);
    $options = options_allowed_values($field_definition->getFieldStorageDefinition());
    $values['link_type'] = array_rand($options, 1);

    return $values;
  }

}
