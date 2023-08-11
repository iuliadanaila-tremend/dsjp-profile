<?php

namespace Drupal\dsjp_pledge\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for describe of initiative types.
 */
class InitiativeTypeDescriptionConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'dsjp_pledge.initiative_type_description_config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'initiative_type_description_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('dsjp_pledge.initiative_type_description_config');

    $form['type_descriptions'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Initiative type descriptions'),
      '#description' => $this->t('A description of the Initiative type that will be displayed in the tooltip and other places for each type.'),
    ];
    $form['type_descriptions']['education'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Education'),
      '#default_value' => $config->get('education') ?? '',
      '#required' => TRUE,
    ];
    $form['type_descriptions']['labour_force'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Labour Force'),
      '#default_value' => $config->get('labour_force') ?? '',
      '#required' => TRUE,
    ];
    $form['type_descriptions']['ict_professionals'] = [
      '#type' => 'textarea',
      '#title' => $this->t('ICT Professionals'),
      '#default_value' => $config->get('ict_professionals') ?? '',
      '#required' => TRUE,
    ];
    $form['type_descriptions']['other'] = [
      '#type' => 'textarea',
      '#title' => $this->t('All Citizens'),
      '#default_value' => $config->get('other') ?? '',
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('dsjp_pledge.initiative_type_description_config')
      ->set('education', $form_state->getValue('education'))
      ->set('labour_force', $form_state->getValue('labour_force'))
      ->set('ict_professionals', $form_state->getValue('ict_professionals'))
      ->set('other', $form_state->getValue('other'))
      ->save();
  }

}
