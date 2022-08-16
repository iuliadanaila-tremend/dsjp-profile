<?php

declare(strict_types = 1);

namespace Drupal\rdf_skos\Plugin\views\filter;

use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rdf_skos\Entity\ConceptSchemeInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\ManyToOne;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter by Skos concept ID.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("skos_concept_reference_id")
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class SkosConceptReferenceId extends ManyToOne {

  /**
   * Stores the exposed input for this filter.
   *
   * @var array
   *
   * phpcs:disable Drupal.NamingConventions.ValidVariableName.LowerCamelName
   */
  public $validated_exposed_input = NULL;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   *
   * phpcs:enable Drupal.NamingConventions.ValidVariableName.LowerCamelName
   */
  protected $entityTypeManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Constructs a SkosConceptReferenceId object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   The entity repository.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, EntityRepositoryInterface $entityRepository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->entityRepository = $entityRepository;
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
      $container->get('entity.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    if (!empty($this->definition['concept_scheme'])) {
      $this->options['concept_scheme'] = $this->definition['concept_scheme'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function hasExtraOptions() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    return $this->valueOptions;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['type'] = ['default' => 'textfield'];
    $options['limit'] = ['default' => TRUE];
    $options['concept_scheme'] = ['default' => ''];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExtraOptionsForm(&$form, FormStateInterface $form_state) {
    // We need to get all the IDs before trying to load. Peculiarity of the
    // RDF storage.
    $ids = $this->entityTypeManager->getStorage('skos_concept_scheme')->getQuery()->execute();
    $schemes = $this->entityTypeManager->getStorage('skos_concept_scheme')->loadMultiple($ids);
    $options = [];
    foreach ($schemes as $scheme) {
      $options[$scheme->id()] = $scheme->label();
    }

    if ($this->options['limit']) {
      if (empty($this->options['concept_scheme'])) {
        $first_scheme = reset($schemes);
        $this->options['concept_scheme'] = $first_scheme->id();
      }

      if (empty($this->definition['concept_scheme'])) {
        $form['concept_scheme'] = [
          '#type' => 'radios',
          '#title' => $this->t('Concept scheme'),
          '#options' => $options,
          '#description' => $this->t('Select which concept scheme to filter by.'),
          '#default_value' => $this->options['concept_scheme'],
        ];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $scheme = $this->entityTypeManager->getStorage('skos_concept_scheme')->load($this->options['concept_scheme']);
    if (empty($scheme) && $this->options['limit']) {
      $form['markup'] = [
        '#markup' => '<div class="js-form-item form-item">' . $this->t('An invalid concept scheme is selected. Please change it in the options.') . '</div>',
      ];
      return;
    }

    $concepts = $this->value ? $this->entityTypeManager->getStorage('skos_concept')->loadMultiple($this->value) : [];
    $form['value'] = [
      '#title' => $this->options['limit'] ? $this->t('Select concepts from concept scheme @scheme', ['@scheme' => $scheme->label()]) : $this->t('Select concepts'),
      '#type' => 'textfield',
      '#default_value' => EntityAutocomplete::getEntityLabels($concepts),
      // Account for the large size of concept references.
      '#maxlength' => 10000,
    ];

    if ($this->options['limit']) {
      $form['value']['#type'] = 'entity_autocomplete';
      $form['value']['#target_type'] = 'skos_concept';
      $form['value']['#selection_settings']['concept_schemes'] = [$scheme->id()];
      $form['value']['#process_default_value'] = FALSE;
      $form['value']['#tags'] = TRUE;
    }

    if (!$form_state->get('exposed')) {
      // Retain the helper option.
      $this->helper->buildOptionsForm($form, $form_state);

      // Show help text if not exposed to end users.
      $form['value']['#description'] = t('Leave blank for all. Otherwise, the first selected concept will be the default instead of "Any".');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function valueValidate($form, FormStateInterface $form_state) {
    $concepts = [];
    if ($values = $form_state->getValue(['options', 'value'])) {
      foreach ($values as $value) {
        $concepts[] = $value['target_id'];
      }
    }
    $form_state->setValue(['options', 'value'], $concepts);
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    $this->valueOptions = [];

    if ($this->value) {
      $this->value = array_filter($this->value);
      $concepts = $this->entityTypeManager->getStorage('skos_concept')->loadMultiple($this->value);
      foreach ($concepts as $concept) {
        $this->valueOptions[$concept->id()] = $this->entityRepository->getTranslationFromContext($concept)->label();
      }
    }

    return parent::adminSummary();
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   * @SuppressWarnings(PHPMD.NPathComplexity)
   *
   * This is entirely copied from
   * Drupal\taxonomy\Plugin\views\filter\TaxonomyIndexTid.
   */
  public function acceptExposedInput($input) {
    if (empty($this->options['exposed'])) {
      return TRUE;
    }
    // We need to know the operator, which is normally set in
    // \Drupal\views\Plugin\views\filter\FilterPluginBase::acceptExposedInput(),
    // before we actually call the parent version of ourselves.
    if (!empty($this->options['expose']['use_operator']) && !empty($this->options['expose']['operator_id']) && isset($input[$this->options['expose']['operator_id']])) {
      $this->operator = $input[$this->options['expose']['operator_id']];
    }

    // If view is an attachment and is inheriting exposed filters, then assume
    // exposed input has already been validated.
    if (!empty($this->view->is_attachment) && $this->view->display_handler->usesExposed()) {
      $this->validated_exposed_input = (array) $this->view->exposed_raw_input[$this->options['expose']['identifier']];
    }

    // If we're checking for EMPTY or NOT, we don't need any input, and we can
    // say that our input conditions are met by just having the right operator.
    if ($this->operator == 'empty' || $this->operator == 'not empty') {
      return TRUE;
    }

    // If it's non-required and there's no value don't bother filtering.
    if (!$this->options['expose']['required'] && empty($this->validated_exposed_input)) {
      return FALSE;
    }

    $rc = parent::acceptExposedInput($input);
    if ($rc) {
      // If we have previously validated input, override.
      if (isset($this->validated_exposed_input)) {
        $this->value = $this->validated_exposed_input;
      }
    }

    return $rc;
  }

  /**
   * {@inheritdoc}
   */
  public function validateExposed(&$form, FormStateInterface $form_state) {
    if (empty($this->options['exposed'])) {
      return;
    }

    $identifier = $this->options['expose']['identifier'];

    if (empty($this->options['expose']['identifier'])) {
      return;
    }

    if ($values = $form_state->getValue($identifier)) {
      foreach ($values as $value) {
        $this->validated_exposed_input[] = $value['target_id'];
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * This is entirely copied from
   * Drupal\taxonomy\Plugin\views\filter\TaxonomyIndexTid.
   */
  protected function valueSubmit($form, FormStateInterface $form_state) {
    // Prevent array_filter from messing up our arrays in parent submit.
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposeForm(&$form, FormStateInterface $form_state) {
    parent::buildExposeForm($form, $form_state);
    unset($form['expose']['reduce']);
    $form['error_message'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display error message'),
      '#default_value' => !empty($this->options['error_message']),
    ];
  }

  /**
   * {@inheritdoc}
   *
   * This is entirely copied from
   * Drupal\taxonomy\Plugin\views\filter\TaxonomyIndexTid.
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();
    // The result potentially depends on entity access and so is just cacheable
    // per user.
    // @todo See https://www.drupal.org/node/2352175.
    $contexts[] = 'user';

    return $contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();

    $scheme = $this->entityTypeManager->getStorage('skos_concept_scheme')->load($this->options['concept_scheme']);
    if ($scheme instanceof ConceptSchemeInterface) {
      $dependencies[$scheme->getConfigDependencyKey()][] = $scheme->getConfigDependencyName();
    }

    if (!$this->options['value']) {
      return $dependencies;
    }

    foreach ($this->entityTypeManager->getStorage('skos_concept')->loadMultiple($this->options['value']) as $concept) {
      $dependencies[$concept->getConfigDependencyKey()][] = $concept->getConfigDependencyName();
    }

    return $dependencies;
  }

}
