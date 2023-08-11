<?php

namespace Drupal\dsjp_sat\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a configuration form for the SAT integration.
 */
class SatConfigForm extends ConfigFormBase {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'dsjp_sat.configuration',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dsjp_sat_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory()->get('dsjp_sat.configuration');
    $form['sat_pages'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Redirect pages'),
      'success' => [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'node',
        '#selection_settings' => [
          'target_bundles' => ['dsj_landing_page'],
        ],
        '#title' => $this->t('Success page'),
      ],
      'welcome' => [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'node',
        '#selection_settings' => [
          'target_bundles' => ['dsj_landing_page'],
        ],
        '#title' => $this->t('Welcome back page'),
      ],
      'timer' => [
        '#type' => 'number',
        '#title' => $this->t('Timer'),
        '#min' => 1,
        '#default_value' => !empty($config->get('timer')) ? $config->get('timer') : 60,
      ],
    ];
    foreach (['success', 'welcome'] as $key) {
      if (!empty($config->get($key))) {
        $node = $this->entityTypeManager->getStorage('node')->load($config->get($key));
        $form['sat_pages'][$key]['#default_value'] = $node instanceof NodeInterface ? $node : '';
      }
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory()->getEditable('dsjp_sat.configuration')
      ->set('success', $form_state->getValue('success'))
      ->set('welcome', $form_state->getValue('welcome'))
      ->set('timer', $form_state->getValue('timer'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
