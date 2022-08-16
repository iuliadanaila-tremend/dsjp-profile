<?php

namespace Drupal\purge_control\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure purge settings.
 */
class PurgeControlSettings extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'purge_control.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'purge_control_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['purge_auto_control'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automate control of enabling and disabling of purge.'),
      '#default_value' => $config->get('purge_auto_control'),
      '#description' => $this->t('If checked, the enabling / disabling of purge will be automated.'),
    ];

    $form['disable_purge'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable purging.'),
      '#default_value' => $config->get('disable_purge'),
      '#description' => $this->t('Disables purging for all purgers.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Save the configuration.
    $this->configFactory->getEditable(static::SETTINGS)
      ->set('purge_auto_control', $values['purge_auto_control'])
      ->set('disable_purge', $values['disable_purge'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
