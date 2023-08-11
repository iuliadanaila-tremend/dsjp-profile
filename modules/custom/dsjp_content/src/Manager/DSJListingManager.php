<?php

namespace Drupal\dsjp_content\Manager;

use Drupal\Core\Entity\EntityDisplayRepository;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\PluginSettingsInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides a custom breadcrumb builder for vocabulary Article type.
 */
class DSJListingManager {

  use StringTranslationTrait;

  /**
   * The entity repository manager.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepository
   */
  protected $entityFieldManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityDisplayRepository;

  /**
   * Constructs the TermBreadcrumbBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface|null $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity repository.
   * @param \Drupal\Core\Entity\EntityDisplayRepository $entity_display_repository
   *   The entity repository.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository, EntityFieldManagerInterface $entity_field_manager, EntityDisplayRepository $entity_display_repository) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * Adds all the categorizations from all the content types in the form.
   *
   * @param array $form
   *   Current form.
   * @param string $filters_field_name
   *   Field that will be altered to store, load and display the filters.
   * @param string $reference_field
   *   Field that will have the content types.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function createCategorizations(array &$form, string $filters_field_name, string $reference_field) {
    // If form doesnt have content types, dont add any categorization.
    if (!isset($form[$reference_field]['widget']['#options'])) {
      return FALSE;
    }

    $content_types = array_keys($form[$reference_field]['widget']['#options']);
    $variation_field = $form[$reference_field];
    // Construct variation input name to use it in #states.
    $variation_input_name_parents = array_shift($variation_field['widget']['#parents']);
    if (!empty($variation_field['widget']['#parents'])) {
      $variation_input_name_parents .= '[' . implode('][', $variation_field['widget']['#parents']) . ']';
    }

    // Load default values field.
    $value = $form[$filters_field_name]['widget'][0]['value']['#default_value'];
    $default_values = $value ? unserialize($value, ['allowed_classes' => FALSE]) : [];

    $excluded_fields = $this->getExcludedFields($form);

    // Transform the field into a field group.
    $filters = [
      '#parents' => $form[$filters_field_name]['#parents'],
      '#type' => 'details',
      '#title' => $this->t('Advanced filters'),
      '#description' => $this->t('Filters for the selected content types.'),
      '#open' => TRUE,
      // Controls the HTML5 'open' attribute. Defaults to FALSE.
    ];
    foreach ($content_types as $content_type) {
      $this->addCategorizationWidgetsForContentType($filters, $content_type, $default_values, $excluded_fields);
    }

    $this->addStatesOnFilters($filters, $content_types, $variation_input_name_parents);

    // Add the filters in the place of the field.
    $form[$filters_field_name]['filters'] = $filters;

    // Make sure the field is hidden. We dont want to display serialized values
    // in the back-end.
    $form[$filters_field_name]['widget'][0]['value']['#type'] = 'hidden';
  }

  /**
   * Adds the states on the filters.
   *
   * @param array $filters
   *   Filters array.
   * @param array $content_types
   *   Content types.
   * @param string $variation_input_name_parents
   *   Field that was referenced.
   */
  public function addStatesOnFilters(array &$filters, array $content_types, string $variation_input_name_parents) {
    foreach ($filters as $key => $field) {
      if (!str_starts_with($key, 'field')) {
        continue;
      }
      $states = [];
      $and_states = [];
      foreach ($content_types as $content_type) {
        if (!in_array($content_type, $field['#content_types'])) {
          $states['invisible'][] = [
            ':input[name="' . $variation_input_name_parents . '[' . $content_type . ']' . '"][value="' . $content_type . '"]' => ['checked' => TRUE],
          ];
        }
      }
      foreach ($field['#content_types'] as $content_type) {
        $and_states[':input[name="' . $variation_input_name_parents . '[' . $content_type . ']' . '"][value="' . $content_type . '"]'] = ['checked' => FALSE];
      }
      $states['invisible'][] = $and_states;

      $filters[$key]['#states'] = $states;
    }
  }

  /**
   * Add the field widget for all categorizations of a content type.
   *
   * @param array $form
   *   Form that will receive these widgets.
   * @param string $content_type
   *   Bundle that will give the form widgets.
   * @param array $default_values
   *   The values populated in the widgets.
   * @param array $excluded_fields
   *   Exclude some fields. Usually those that are already in form.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function addCategorizationWidgetsForContentType(array &$form, string $content_type, array $default_values, array $excluded_fields) {
    $form_state = new FormState();
    // Create a generic entity of that type.
    $entity = $this->entityTypeManager->getStorage('node')->create([
      'type' => $content_type,
    ]);
    $form_state->set('entity', $entity);
    $form_display = $this->entityTypeManager->getStorage('entity_form_display')
      ->load('node.' . $content_type . '.default');

    // If there is a "none", "all" or some missing bundle in the list.
    if (!$form_display) {
      return FALSE;
    }
    $form_state->set('form_display', $form_display);
    $form_object = $this->entityTypeManager->getFormObject($entity->getEntityTypeId(), 'default');
    $form_object->setEntity($entity);
    $form_state->setFormObject($form_object)
      ->disableRedirect();

    $input = $form_state->getUserInput();
    // @phpstan-ignore-next-line
    if (!isset($input)) {
      $form_state->setUserInput($form_state->getValues());
    }

    foreach ($form_display->getComponents() as $name => $component) {
      if (!str_starts_with($name, 'field_dsj') || isset($excluded_fields[$name])) {
        continue;
      }
      // Get the widget from the default form display of the bundle.
      $widget = $form_display->getRenderer($name);
      if (!$widget) {
        continue;
      }
      $items = $entity->get($name);
      $items->filterEmptyItems();

      $this->addFormWidget($form, $form_state, $content_type, $items, $widget, $name, $default_values);
    }
  }

  /**
   * Add the widget on the current form.
   *
   * @param array $form
   *   Current form.
   * @param \Drupal\Core\Form\FormState $form_state
   *   Current form state.
   * @param string $content_type
   *   Content type on which this widget appears.
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   Items from the content type.
   * @param \Drupal\Core\Field\PluginSettingsInterface $widget
   *   Widget for the category.
   * @param string $name
   *   Name of the current field.
   * @param array $default_values
   *   Default fields that need to be populated in the widget.
   */
  public function addFormWidget(array &$form, FormState $form_state, string $content_type, FieldItemListInterface $items, PluginSettingsInterface $widget, string $name, array $default_values) {
    // Check if the fields describe a categorization. Usually list and
    // taxonomy terms. Maybe there is a better way to find the
    // categorizations.
    if (in_array($widget->getPluginId(), ['options_select', 'options_shs'])) {
      if (!isset($form[$name])) {
        $form[$name] = $widget->form($items, $form, $form_state);
        $form[$name]['#access'] = $items->access('edit');
        $form[$name]['widget']['#required'] = FALSE;
        $delta = $form[$name]['widget']['#delta'];
        $key_column = $form[$name]['widget']['#key_column'];
        if (!empty($default_values[$name][$delta][$key_column])) {
          foreach ($default_values[$name] as $delta => $value) {
            $form[$name]['widget']['#default_value'][$delta] = $value[$key_column];
          }
        }
      }
      // Used later on for the states.
      $form[$name]['#content_types'][] = $content_type;
    }
  }

  /**
   * Creates the array of excluded fields.
   *
   * @param array $form
   *   Existing form.
   *
   * @return string[]
   *   Excluded fields with fieldname as key.
   */
  public function getExcludedFields(array $form) {
    // Explicit define excluded fields.
    $excluded_fields = ['field_dsj_type_of_article' => 1];
    foreach ($form as $name => $component) {
      if (!str_starts_with($name, 'field_dsj')) {
        continue;
      }
      $excluded_fields[$name] = $name;
    }
    return $excluded_fields;
  }

  /**
   * Adds additional filters to the view filters.
   *
   * @param array $filters
   *   Existing view filters.
   * @param array $query
   *   Cache query.
   * @param array $additional_categories
   *   Additional categories that needs to be added as view filters.
   * @param bool $search_api
   *   True if the view is created with search api.
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   */
  public function addViewFilters(array &$filters, array &$query, array $additional_categories, bool $search_api) {
    foreach ($additional_categories as $field_name => $values) {
      $filter_values = [];
      $field_key = 'value';
      foreach ($values as $value) {
        // We treat for now only 'value' or 'target_id' values.
        $field_key = isset($value['target_id']) ? 'target_id' : 'value';
        $filter_values[$value[$field_key]] = $field_key == 'target_id' ? (int) $value[$field_key] : $value[$field_key];
        $query['f[' . count($query) . ']'] = $field_name . "_" . $field_key . ":" . $value[$field_key];
      }
      $view_plugin = $field_key == 'target_id' ? "taxonomy_index_tid" : "list_field";
      $filters[$field_name . "_" . $field_key] = [
        "id" => $search_api ? $field_name : $field_name . "_" . $field_key,
        "table" => $search_api ? "search_api_index_main_content" : "node__" . $field_name,
        "field" => $search_api ? $field_name : $field_name . "_" . $field_key,
        "relationship" => "none",
        "group_type" => "group",
        "admin_label" => "",
        "plugin_id" => $search_api ? "search_api_options" : $view_plugin,
        "operator" => "or",
        "value" => $filter_values,
        "group" => 1,
        "exposed" => FALSE,
        "is_grouped" => FALSE,
        "reduce_duplicates" => FALSE,
        "type" => "select",
        "hierarchy" => FALSE,
        "limit" => TRUE,
        "error_message" => TRUE,
      ];
    }
  }

}
