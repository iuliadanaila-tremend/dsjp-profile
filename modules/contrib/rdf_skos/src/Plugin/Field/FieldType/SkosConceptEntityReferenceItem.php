<?php

declare(strict_types = 1);

namespace Drupal\rdf_skos\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;

/**
 * A field type used to reference SKOS Concept entities.
 *
 * It only supports referencing SKOS Concept entities but can be configured
 * to limit these by Concept Scheme.
 *
 * @FieldType(
 *   id = "skos_concept_entity_reference",
 *   label = @Translation("SKOS Concept Reference"),
 *   description = @Translation("References SKOS Concepts."),
 *   category = @Translation("SKOS"),
 *   default_widget = "skos_concept_entity_reference_autocomplete",
 *   default_formatter = "skos_concept_entity_reference_label",
 *   list_class = "\Drupal\rdf_skos\Plugin\Field\SkosConceptReferenceFieldItemList",
 * )
 */
class SkosConceptEntityReferenceItem extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings(): array {
    return [
      'target_type' => 'skos_concept',
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function getPreconfiguredOptions(): array {
    // We don't want to use this field with any other entity types so we don't
    // preconfigure anything here.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state): array {
    $field = $form_state->getFormObject()->getEntity();

    /** @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface $selection_manager */
    $selection_manager = \Drupal::service('plugin.manager.entity_reference_selection');

    $self = get_class($this);
    $form = [
      '#type' => 'container',
      '#process' => [[$self, 'fieldSettingsAjaxProcess']],
      '#element_validate' => [[$self, 'fieldSettingsFormValidate']],
    ];

    $form['handler'] = [
      '#type' => 'details',
      '#title' => t('Reference type'),
      '#open' => TRUE,
      '#tree' => TRUE,
      '#process' => [[$self, 'formProcessMergeParent']],
    ];

    $form['handler']['handler'] = [
      '#type' => 'value',
      '#value' => 'default:skos_concept',
    ];

    $form['handler']['handler_settings'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['entity_reference-settings']],
    ];

    $handler = $selection_manager->getSelectionHandler($field);
    // Set the current field config in the form state to make it available in
    // selection plugin.
    $form_state->set('field', $field);
    $form['handler']['handler_settings'] += $handler->buildConfigurationForm([], $form_state);

    return $form;
  }

}
