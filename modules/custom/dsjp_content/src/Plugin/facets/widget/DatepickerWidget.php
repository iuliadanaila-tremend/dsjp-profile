<?php

namespace Drupal\dsjp_content\Plugin\facets\widget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Widget\WidgetPluginBase;

/**
 * The links widget.
 *
 * @FacetsWidget(
 *   id = "datepicker",
 *   label = @Translation("Datepicker"),
 *   description = @Translation("A simple widget that shows a datepicker"),
 * )
 */
class DatepickerWidget extends WidgetPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'show_reset_link' => FALSE,
      'hide_reset_when_no_selection' => FALSE,
      'reset_text' => $this->t('Show all'),
    ] + parent::defaultConfiguration();
  }

  /**
   * Prepare dates for js initialization of datepicker widget.
   *
   * @param array $results
   *   Facet results.
   *
   * @return array
   *   Date values and selected date.
   */
  public function prepareDatepickerValues(array $results) {
    $values = [];
    $activeDate = '';
    foreach ($results as $id => $result) {
      $values[$id] = [
        'displayValue' => $result->getDisplayValue(),
        'url' => $result->getUrl()->toString(),
      ];
      if ($result->isActive()) {
        $activeDate = $result->getDisplayValue();
      }
    }

    return ['values' => $values, 'activeDate' => $activeDate];
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet) {
    $build = parent::build($facet);
    $this->appendWidgetLibrary($build);

    $results = $facet->getResults();
    $build['#attached']['drupalSettings']['facets']['datepickerValues'][$facet->id()] = $this->prepareDatepickerValues($results);
    return $build;
  }

  /**
   * Appends widget library and relevant information for it to build array.
   *
   * @param array $build
   *   Reference to build array.
   */
  protected function appendWidgetLibrary(array &$build) {
    $build['#attached']['library'][] = 'dsjp_content/drupal.facets.datepicker-widget';
    $build['#attributes']['class'][] = 'js-facets-datepicker';
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    $form = parent::buildConfigurationForm($form, $form_state, $facet);

    $form['show_reset_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show reset link'),
      '#default_value' => $this->getConfiguration()['show_reset_link'],
    ];
    $form['reset_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Reset text'),
      '#default_value' => $this->getConfiguration()['reset_text'],
      '#states' => [
        'visible' => [
          ':input[name="widget_config[show_reset_link]"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="widget_config[show_reset_link]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['hide_reset_when_no_selection'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide reset link when no facet item is selected'),
      '#default_value' => $this->getConfiguration()['hide_reset_when_no_selection'],
    ];
    return $form;
  }

}
