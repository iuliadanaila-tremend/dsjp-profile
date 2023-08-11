<?php

/**
 * @file
 * Contains views API hooks for dsjp_learning_states module.
 */

/**
 * Implements hook_views_data_alter().
 */
function dsjp_learning_states_views_data_alter(array &$data) {
  $data['node']['dsjp_learning_state'] = [
    'title' => t('Node learning states'),
    'help' => t('Limit results to only those entity selected for learning; Or display information about the learning states on a entity.'),
    'relationship' => [
      'group' => t('Learning states'),
      'label' => t('Learning states'),
      'base' => 'dsjp_learning_states',
      'base field' => 'entity_id',
      'relationship field' => 'nid',
      'handler' => 'views_handler_relationship',
      'id' => 'standard',
    ],
  ];
}
