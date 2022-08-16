<?php

namespace Drupal\dsjp_content\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Follow Us' block.
 *
 * @Block(
 *  id = "dsjp_filter_results_btn_block",
 *  admin_label = @Translation("Filter results button"),
 * )
 */
class FilterResultsBtnBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $text = $this->configuration['text'] ?? '';

    $form['text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text to appear on button'),
      '#default_value' => $text,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['text'] = $form_state->getValue('text');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $text = $this->configuration['text'];

    return [
      '#theme' => 'filter_results_btn',
      '#text' => $text,
      '#attached' => [
        'library' =>
          [
            'dsjp_content/drupal.facets.filter-results-btn',
          ],
      ],
      '#attributes' => [
        'style' => 'display:none',
      ],
    ];
  }

}
