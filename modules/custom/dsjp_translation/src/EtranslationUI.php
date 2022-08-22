<?php

namespace Drupal\dsjp_translation;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tmgmt\TranslatorPluginUiBase;

/**
 * Provides a UI plugin for eTranslation translator.
 */
class EtranslationUI extends TranslatorPluginUiBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\tmgmt\TranslatorInterface $translator */
    $translator = $form_state->getFormObject()->getEntity();
    $form['credentials'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Credentials'),
      'username' => [
        '#type' => 'textfield',
        '#title' => $this->t('Username'),
        '#default_value' => $translator->getSetting(['credentials', 'username']),
      ],
      'password' => [
        '#type' => 'password',
        '#title' => $this->t('Password'),
        '#default_value' => $translator->getSetting(['credentials', 'password']),
      ],
    ];
    $form['endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Endpoint'),
      '#default_value' => $translator->getSetting('endpoint'),
    ];
    $form['basic_auth'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Callback basic auth'),
      'username' => [
        '#type' => 'textfield',
        '#title' => $this->t('Username'),
        '#default_value' => $translator->getSetting(['basic_auth', 'username']),
      ],
      'password' => [
        '#type' => 'password',
        '#title' => $this->t('Password'),
        '#default_value' => $translator->getSetting(['basic_auth', 'password']),
      ],
    ];

    return $form;
  }

}
