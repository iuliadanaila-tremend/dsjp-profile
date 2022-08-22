<?php

namespace Drupal\dsjp_pledge\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Provides a field type of baz.
 *
 * @FieldType(
 *   id = "dsj_initiative_beneficiary",
 *   label = @Translation("Initiative Beneficiary"),
 *   default_formatter = "dsj_initiative_beneficiary_formatter",
 *   default_widget = "dsj_initiative_beneficiary_widget",
 * )
 */
class InitiativeBeneficiaryType extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string');
    $properties['number'] = DataDefinition::create('string');
    $properties['status'] = DataDefinition::create('string');

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'number' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'status' => [
          'type' => 'varchar',
          'length' => 255,
        ],
      ],
    ];
  }

}
