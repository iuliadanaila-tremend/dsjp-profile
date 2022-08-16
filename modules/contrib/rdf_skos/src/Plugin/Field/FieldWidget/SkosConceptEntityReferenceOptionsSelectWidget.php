<?php

declare(strict_types = 1);

namespace Drupal\rdf_skos\Plugin\Field\FieldWidget;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin for the 'skos_concept_entity_reference_options_select' widget.
 *
 * @FieldWidget(
 *   id = "skos_concept_entity_reference_options_select",
 *   label = @Translation("SKOS Concept select list"),
 *   description = @Translation("A select list widget for RDF SKOS Concepts."),
 *   field_types = {
 *     "skos_concept_entity_reference"
 *   },
 *   multiple_values = TRUE
 * )
 */
class SkosConceptEntityReferenceOptionsSelectWidget extends OptionsSelectWidget {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The transliteration service.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliteration;

  /**
   * Constructs a SkosConceptEntityReferenceOptionsSelectWidget object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   *   The transliteration service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, TransliterationInterface $transliteration, LanguageManagerInterface $language_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->transliteration = $transliteration;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('transliteration'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'sort' => 'id',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['sort'] = [
      '#type' => 'radios',
      '#title' => $this->t('Sort by'),
      '#default_value' => $this->getSetting('sort'),
      '#options' => [
        'id' => $this->t('ID'),
        'label' => $this->t('Label'),
      ],
      '#description' => $this->t('Select whether the options should be sorted by ID or by label.'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $sort = $this->getSetting('sort') === 'id' ? 'ID' : 'Label';
    $summary[] = $this->t('Sort options by: @sort', ['@sort' => $sort]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  protected function getOptions(FieldableEntityInterface $entity) {
    $options = parent::getOptions($entity);
    $sort = $this->getSetting('sort');
    if ($sort === 'id') {
      return $options;
    }

    // If we have an empty label take it out before sorting.
    if ($empty_label = $this->getEmptyLabel()) {
      unset($options['_none']);
    }
    $language = $this->languageManager->getCurrentLanguage()->getId();
    uasort($options, function (string $a, string $b) use ($language): int {
      return $this->transliteration->transliterate($a, $language) <=> $this->transliteration->transliterate($b, $language);
    });
    if ($empty_label) {
      $options = ['_none' => $empty_label] + $options;
    }
    return $options;
  }

}
