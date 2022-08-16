<?php

/**
 * @file
 * User online status views field.
 */

/**
 * Implements hook_views_data_alter().
 */
function user_online_status_views_data_alter(array &$data) {
  $data['views']['user_online_status'] = [
    'group' => t('User'),
    'title' => t('Online status'),
    'help' => t("Show the user's online status"),
    'field' => [
      'id' => 'user_online_status',
    ],
  ];
  return $data;
}
