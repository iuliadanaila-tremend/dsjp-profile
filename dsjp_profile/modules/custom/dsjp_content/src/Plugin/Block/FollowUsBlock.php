<?php

namespace Drupal\dsjp_content\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a 'Follow Us' block.
 *
 * @Block(
 *  id = "follow_us_block",
 *  admin_label = @Translation("Follow Us Block"),
 * )
 */
class FollowUsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $options = [
      'email' => $this->t('E-mail'),
      'twitter' => $this->t('Twitter'),
      'facebook' => $this->t('Facebook'),
      'youtube' => $this->t('Youtube'),
      'instagram' => $this->t('Instagram'),
    ];
    $optionsDefaultValues = [];
    $links = !empty($this->configuration['social_links']) ? $this->configuration['social_links'] : [];
    foreach ($links as $key => $link) {
      if ($link['enabled'] == 1) {
        $optionsDefaultValues[] = $key;
      }
    }
    $form['social_types'] = [
      '#type' => 'checkboxes',
      '#multiple' => TRUE,
      '#title' => $this->t('Select visible social links'),
      '#options' => $options,
      '#default_value' => $optionsDefaultValues,
    ];
    foreach ($options as $key => $option) {
      $form['social_link_' . $key] = [
        '#type' => 'textfield',
        '#title' => $this->t('@option link.', ['@option' => $option]),
        '#states' => [
          'visible' => [
            'input[name="settings[social_types][' . $key . ']"]' => ['checked' => TRUE],
          ],
        ],
        '#default_value' => !empty($links[$key]) ? $links[$key]['link'] : '',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $availableLinks = $form_state->getValue('social_types');
    foreach ($availableLinks as $key => $enabled) {
      $this->configuration['social_links'][$key] = [
        'enabled' => $enabled === $key ? 1 : 0,
        'link' => $form_state->getValue('social_link_' . $key),
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $links = [];
    $availableLinks = $this->configuration['social_links'];
    foreach ($availableLinks as $key => $linkData) {
      if ($linkData['enabled'] == 1) {
        $links[$key] = Url::fromUri($linkData['link'])->toString();
      }
    }
    return [
      '#theme' => 'follow_us_block',
      '#links' => $links,
    ];
  }

}
