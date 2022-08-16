<?php

namespace Drupal\user_online_status\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Provides a form to configure User Online Status settings.
 *
 * @internal
 */
class UserOnlineStatusSettingsForm extends ConfigFormBase {

  const USER_ONLINE_STATUS_SETTINGS = 'user_online_status.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_online_status_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [static::USER_ONLINE_STATUS_SETTINGS];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config(static::USER_ONLINE_STATUS_SETTINGS);

    $form['time'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Time delays'),
    ];

    $form['time']['seconds_to_absent'] = [
      '#type' => 'number',
      '#title' => $this->t('Seconds starting from last user activity until user is displayed as absent'),
      '#description' => $this->t('Simply use the same value for absent and offline when you want skip absent.'),
      '#default_value' => $config->get('seconds_to_absent'),
      '#min' => 1,
      '#step' => 1,
      '#required' => TRUE,
    ];

    $form['time']['seconds_to_offline'] = [
      '#type' => 'number',
      '#title' => $this->t('Seconds starting from last user activity until user is displayed as offline'),
      '#description' => $this->t('This value must be greater or equal to the absent delay.'),
      '#default_value' => $config->get('seconds_to_offline'),
      '#min' => 1,
      '#step' => 1,
      '#required' => TRUE,
    ];

    $form['classes'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('CSS'),
      '#description' => $this->t('Some description'),
    ];

    $form['classes']['css_classes_online'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Online'),
      '#description' => $this->t('Additional CSS classes for the online state.'),
      '#default_value' => $config->get('css_classes_online'),
    ];

    $form['classes']['css_classes_absent'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Absent'),
      '#description' => $this->t('Additional CSS classes for the absent state.'),
      '#default_value' => $config->get('css_classes_absent'),
    ];

    $form['classes']['css_classes_offline'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Offline'),
      '#description' => $this->t('Additional CSS classes for the offline state.'),
      '#default_value' => $config->get('css_classes_offline'),
    ];

    // @todo
    // extra class for online status; default empty string/NULL
    // extra class for absent status; default empty string/NULL
    // extra class for offline status; default empty string/NULL
    // @todo JavaScript
    // Don't just add classes, also replace classes when necessary.
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Validate time delay.
    $seconds_absent = $values['seconds_to_absent'];
    $seconds_offline = $values['seconds_to_offline'];
    if ($seconds_absent > $seconds_offline) {
      $form_state->setErrorByName('absent_gt_offline', $this->t('Seconds to absent must not be greater than seconds to offline.'));
    }

    // Validate CSS classes.
    $states = [
      'online',
      'absent',
      'offline',
    ];
    foreach ($states as $state) {
      // Explode all classes into an array.
      if (!empty($values['css_classes_' . $state])) {
        $classes = trim($values['css_classes_' . $state]);
        if (preg_match('/\s/', $classes)) {
          $classes_array = explode(' ', $classes);
        }
        else {
          $classes_array[0] = $classes;
        }
        // Validate each single class.
        foreach ($classes_array as $key => $class) {
          if ($class !== Html::cleanCssIdentifier($class)) {
            $form_state->setErrorByName('css_classes_' . $state . '_' . $key, $this->t('@class is no valid CSS class.', ['@class' => $class]));
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config(static::USER_ONLINE_STATUS_SETTINGS)
      ->set('seconds_to_absent', $form_state->getValue('seconds_to_absent'))
      ->set('seconds_to_offline', $form_state->getValue('seconds_to_offline'))
      ->set('css_classes_online', $form_state->getValue('css_classes_online'))
      ->set('css_classes_absent', $form_state->getValue('css_classes_absent'))
      ->set('css_classes_offline', $form_state->getValue('css_classes_offline'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
