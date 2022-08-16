<?php

declare(strict_types = 1);

namespace Drupal\rdf_skos\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\rdf_skos\ConceptSubsetPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Selection plugin for SKOS Concepts.
 *
 * @EntityReferenceSelection(
 *   id = "default:skos_concept",
 *   label = @Translation("SKOS Concept selection"),
 *   entity_types = {"skos_concept"},
 *   group = "default",
 *   weight = 1
 * )
 */
class SkosConceptSelection extends DefaultSelection {

  /**
   * The concept subset plugin manager.
   *
   * @var \Drupal\rdf_skos\ConceptSubsetPluginManagerInterface
   */
  protected $subsetManager;

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.ExcessiveParameterList)
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, AccountInterface $current_user, EntityFieldManagerInterface $entity_field_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityRepositoryInterface $entity_repository, ConceptSubsetPluginManagerInterface $subset_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $module_handler, $current_user, $entity_field_manager, $entity_type_bundle_info, $entity_repository);
    $this->subsetManager = $subset_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('current_user'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity.repository'),
      $container->get('plugin.manager.concept_subset')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      // Empty array means allow all.
      'concept_schemes' => [],
      // Concept subset to filter the available concepts by.
      'concept_subset' => NULL,
        // In case the plugin is used in a reference field, we can store some
        // info about it.
      'field' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);

    // We do not support sorting or auto-creation of values.
    $form['auto_create']['#access'] = FALSE;
    $form['sort']['#access'] = FALSE;

    $configuration = $this->getConfiguration();

    $form['concept_schemes'] = [
      '#type' => 'select',
      '#title' => $this->t('Concept Schemes'),
      '#description' => $this->t('Concept Schemes to filter by. Leave empty to allow all.'),
      '#options' => [],
      '#default_value' => array_values($configuration['concept_schemes']),
      '#multiple' => TRUE,
      '#size' => 10,
      // Ajax is applied in
      // EntityReferenceItem::fieldSettingsAjaxProcessElement()
      '#ajax' => TRUE,
    ];

    $options = $this->prepareConceptSchemeOptions();

    if (empty($options)) {
      return $form;
    }

    $concept_schemes = $configuration['concept_schemes'];
    if ($concept_schemes) {
      $subset_element = $this->buildConceptSubsetElement($form, $form_state, $concept_schemes);
      if ($subset_element) {
        $form['concept_subset'] = $subset_element;
      }
    }

    $form['concept_schemes']['#options'] = $options;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::validateConfigurationForm($form, $form_state);

    $settings = $form_state->getValue('settings');
    $concept_schemes = $settings['handler_settings']['concept_schemes'];
    if ($concept_schemes) {
      $concept_schemes = array_values($concept_schemes);
      $settings['handler_settings']['concept_schemes'] = $concept_schemes;
    }

    // Store NULL when no concept subset is chosen.
    if (isset($settings['handler_settings']['concept_subset']) && $settings['handler_settings']['concept_subset'] === '') {
      $settings['handler_settings']['concept_subset'] = NULL;
    }

    // Add field information that can be used in the selection handler. This
    // comes from the actual SkosConceptEntityReferenceItem and we need it so
    // that the selection plugin query builder can receive this information.
    $field = $form_state->get('field');
    if ($field instanceof FieldConfigInterface) {
      $settings['handler_settings']['field'] = [
        'field_name' => $field->getName(),
        'entity_type' => $field->getTargetEntityTypeId(),
        'bundle' => $field->getTargetBundle(),
        'concept_schemes' => $concept_schemes,
      ];
    }

    $form_state->setValue('settings', $settings);
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS'): QueryInterface {
    $query = parent::buildEntityQuery($match, $match_operator);
    $configuration = $this->getConfiguration();
    if (!empty($configuration['field'])) {
      // Allow query alterations when used for a reference field.
      $query->addTag('skos_concept_field_selection_plugin');
      $query->addMetaData('field', $configuration['field']);
    }

    $concept_schemes = $configuration['concept_schemes'];
    if (!empty($concept_schemes)) {
      $group = $query->orConditionGroup()
        ->condition('in_scheme', $concept_schemes, 'IN')
        ->condition('top_concept_of', $concept_schemes, 'IN');
      $query->condition($group);
    }

    $this->applyConceptSubset($query, $match_operator, $concept_schemes, $match);

    return $query;
  }

  /**
   * Prepares the options for the concept scheme select element.
   *
   * @return array
   *   The options.
   */
  protected function prepareConceptSchemeOptions(): array {
    $ids = $this->entityTypeManager->getStorage('skos_concept_scheme')
      ->getQuery()
      ->execute();

    if (!$ids) {
      return [];
    }

    /** @var \Drupal\rdf_skos\Entity\ConceptSchemeInterface[] $concept_schemes */
    $concept_schemes = $this->entityTypeManager->getStorage('skos_concept_scheme')->loadMultiple($ids);

    $options = [];
    foreach ($concept_schemes as $concept_scheme) {
      $options[$concept_scheme->id()] = $concept_scheme->getTitle();
    }

    return $options;
  }

  /**
   * Builds the element for the concept subsets.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $concept_schemes
   *   The chosen concept schemes.
   *
   * @return array
   *   The form element.
   */
  protected function buildConceptSubsetElement(array $form, FormStateInterface $form_state, array $concept_schemes): array {
    $definitions = $this->subsetManager->getApplicableDefinitions($concept_schemes);

    if (!$definitions) {
      return [];
    }

    $options = [];
    foreach ($definitions as $id => $definition) {
      $options[$id] = $definition['label'];
    }

    return [
      '#type' => 'select',
      '#title' => $this->t('Concept subset'),
      '#description' => $this->t('The concept subset you would like this selection to filter by.'),
      '#options' => $options,
      '#default_value' => $this->getConfiguration()['concept_subset'],
      '#empty_value' => '',
    ];
  }

  /**
   * Applies the concept subset alterations to the query.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The query.
   * @param string $match_operator
   *   The matching operator.
   * @param array $concept_schemes
   *   The selected concept schemes.
   * @param string|null $match
   *   The value to match.
   */
  protected function applyConceptSubset(QueryInterface $query, $match_operator, array $concept_schemes = [], string $match = NULL): void {
    $configuration = $this->getConfiguration();
    if (!$configuration['concept_subset']) {
      return;
    }

    /** @var \Drupal\rdf_skos\ConceptSubsetInterface $plugin */
    $plugin = $this->subsetManager->createInstance($configuration['concept_subset']);
    $plugin->alterQuery($query, $match_operator, $concept_schemes, $match);
  }

}
