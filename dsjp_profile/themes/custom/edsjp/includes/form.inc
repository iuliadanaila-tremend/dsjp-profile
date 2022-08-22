<?php

/**
 * @file
 * Theme and preprocess functions for forms.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_FORM_ID_alter() for user_form.
 */
function edsjp_form_user_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  if ($form['#form_id'] !== 'user_form') {
    return;
  }

  $form['#fieldgroups']['group_dsj_preferences']->children[] = 'legal';

  unset($form['field_dsj_firstname']['widget'][0]['value']['#description']);
  unset($form['field_dsj_lastname']['widget'][0]['value']['#description']);

  // Account details section.
  $form['account']['mail']['#title'] = t('Registered email');
  $form['account']['mail']['#weight'] = -5;

  $form['account']['#attributes']['class'] = 'form--item-account-info';

  // Account preferences section.
  unset($form['timezone']['#title']);
  $form['timezone']['#type'] = 'container';
  unset($form['timezone']['timezone']['#description']);
  unset($form['language']['#title']);
  $form['language']['#type'] = 'container';
  unset($form['language']['preferred_langcode']['#description']);
}

/**
 * Implements hook_preprocess_HOOK().
 */
function edsjp_preprocess_input(&$variables) {
  // Change the autocomplete field with a editable div in order to show the
  // selected user with it's profile picture as select list and chosen doesn't
  // allow html option values.
  if (isset($variables['element']) && isset($variables['element']['#selection_handler']) && $variables['element']['#selection_handler'] == 'default:user_fullname') {
    $variables['children'] = [
      '#markup' => '<div id="visible-overlap-input" contenteditable class="ecl-select"></div>',
    ];
    $variables['attributes']['class'][] = 'autocomplete-visually-hidden';
  }
}

/**
 * Implements hook_preprocess_HOOK().
 */
function edsjp_preprocess_input__checkbox(&$variables) {
  if (isset($variables['element']['#attributes']['data-keep-disabled'])) {
    $variables['input_attributes'][] = [
      'name' => 'data-keep-disabled',
      'value' => $variables['element']['#attributes']['data-keep-disabled'],
    ];
  }
  if (isset($variables['element']['#attributes']['data-delta'])) {
    $variables['input_attributes'][] = [
      'name' => 'data-delta',
      'value' => $variables['element']['#attributes']['data-delta'],
    ];
  }
}

/**
 * Implements hook_preprocess_HOOK().
 */
function edsjp_preprocess_form_element(&$variables) {
  if (isset($variables['element']) && $variables['element']['#type'] == 'managed_file' && $variables['element']['#field_name'] == 'field_dsj_attachments') {
    $variables['description_display'] = 'before';
  }
}

/**
 * Implements hook_preprocess_HOOK().
 */
function edsjp_preprocess_fieldset(&$variables) {
  if (isset($variables['element']['#type']) && $variables['element']['#type'] == 'fieldset' &&
    (isset($variables['element']['created']) || isset($variables['element']['changed']) ||
    strpos($variables['element']['#id'], 'edit-initiative-beneficiary-') === 0)) {
    $variables['legend_display'] = TRUE;
  }
}
