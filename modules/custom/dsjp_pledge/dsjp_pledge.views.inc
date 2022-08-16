<?php

/**
 * @file
 * Provides views data alter functionality.
 */

/**
 * Implements hook_views_data_alter().
 */
function dsjp_pledge_views_data_alter(array &$data) {
  $data['node__field_dsj_initiative_beneficiary']['beneficiary'] = [
    'title' => t('Pledge Initiative Beneficiary select'),
    'filter' => [
      'group' => t('Custom'),
      'title' => t('Pledge Initiative Beneficiary select'),
      'field' => 'field_dsj_initiative_beneficiary_value',
      'id' => 'dsj_pledge_initiative_beneficiary',
    ],
  ];
}
