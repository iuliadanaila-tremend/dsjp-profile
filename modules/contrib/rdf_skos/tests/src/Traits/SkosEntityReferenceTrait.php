<?php

declare(strict_types = 1);

namespace Drupal\Tests\rdf_skos\Traits;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Used for tests that deal with Skos entity references.
 */
trait SkosEntityReferenceTrait {

  /**
   * Creates a Skos concept reference field.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   * @param array $concept_schemes
   *   The concept scheme sto filter the concepts by.
   * @param string $field_name
   *   The field machine name.
   * @param string $field_label
   *   The field label.
   * @param string|null $widget
   *   The widget plugin ID if one is needed. NULL otherwise.
   * @param string|null $concept_subset
   *   An optional concept subset plugin.
   */
  protected function createSkosConceptReferenceField(string $entity_type, string $bundle, array $concept_schemes, string $field_name, string $field_label, string $widget = NULL, string $concept_subset = NULL): void {
    $handler_settings = [
      'target_bundles' => NULL,
      'auto_create' => FALSE,
      'concept_schemes' => $concept_schemes,
      'field' => [
        'field_name' => $field_name,
        'entity_type' => $entity_type,
        'bundle' => $bundle,
        'concept_schemes' => $concept_schemes,
      ],
    ];

    if ($concept_subset) {
      $handler_settings['concept_subset'] = $concept_subset;
    }

    if (!FieldStorageConfig::loadByName($entity_type, $field_name)) {
      FieldStorageConfig::create([
        'field_name' => $field_name,
        'type' => 'skos_concept_entity_reference',
        'entity_type' => $entity_type,
        'cardinality' => 1,
        'settings' => [
          'target_type' => 'skos_concept',
        ],
      ])->save();
    }

    if (!FieldConfig::loadByName($entity_type, $bundle, $field_name)) {
      FieldConfig::create([
        'field_name' => $field_name,
        'entity_type' => $entity_type,
        'bundle' => $bundle,
        'label' => $field_label,
        'settings' => [
          'handler' => 'default:skos_concept',
          'handler_settings' => $handler_settings,
        ],
      ])->save();
    }

    if ($widget) {
      /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display */
      $form_display = \Drupal::entityTypeManager()
        ->getStorage('entity_form_display')
        ->load("{$entity_type}.{$bundle}.default");

      $form_display->setComponent($field_name, [
        'type' => $widget,
        'region' => 'content',
      ]);

      $form_display->save();
    }

  }

}
