<?php

/**
 * @file
 * Provides views data for dsjp_views.module.
 */

/**
 * Implements hook_views_data().
 */
function dsjp_views_views_data() {
  $data['views']['extended_result'] = [
    'title' => t('Result summary with exposed filters'),
    'help' => t('Shows result summary, for example the items per page.'),
    'area' => [
      'id' => 'extended_result',
    ],
  ];

  return $data;
}

/**
 * Implements hook_views_data_alter().
 */
function dsjp_views_views_data_alter(array &$data) {
  $data['tmgmt_job_item']['item'] = [
    'title' => t('Item'),
    'relationship' => [
      'label' => t('Item'),
      'id' => 'standard',
      'base' => 'node_field_data',
      'base field' => 'nid',
      'field' => 'item_id',
    ],
  ];
}
