<?php

/**
 * @file
 * Theme and preprocess functions for private messages.
 */

use Drupal\block\BlockInterface;
use Drupal\block\Entity\Block;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Implements hook_preprocess_HOOK().
 */
function edsjp_preprocess_private_message_thread(&$variables) {
  if ($variables['view_mode'] === 'block') {
    _edsjp_preprocess_private_message_thread_block($variables);
    _edsjp_add_unread_messages_classes($variables);
  }
  elseif ($variables['view_mode'] == 'default') {
    $block = Block::load('privatemessagepagetextblock');
    if ($block instanceof BlockInterface) {
      $variables['message_block'] = \Drupal::entityTypeManager()->getViewBuilder('block')->view($block);
    }
    $user = User::load(\Drupal::currentUser()->id());
    if ($user instanceof UserInterface) {
      $variables['current_user'] = \Drupal::entityTypeManager()->getViewBuilder('user')->view($user, 'private_message');
    }
    if (isset($variables['content']['delete_link'])) {
      $variables['content']['delete_link']['#prefix'] = '<div class="private_message_thread_delete_link_wrapper hidden">';
    }
    $variables['back_url'] = Url::fromRoute('view.dsj_private_messages.private_messages_page', [
      'user' => \Drupal::currentUser()->id(),
      'arg_1' => 'last',
    ], [
      'attributes' => [
        'class' => ['hidden-desktop'],
      ],
    ])->toString();
    $variables['#attached']['drupalSettings']['private_message']['total_messages'] = count($variables['private_message_thread']->getMessages());

  }
}

/**
 * Helper function for private message threads rendered in block view mode.
 */
function _edsjp_preprocess_private_message_thread_block(&$variables) {
  // Make sure this gets cached based on the query arguments too.
  $variables['#cache']['contexts'][] = 'url';
  $variables['#cache']['contexts'][] = 'url.query_args';
  $thread = $variables['private_message_thread'];
  $members = $thread->getMembers();
  foreach ($members as $index => $member) {
    if ($member->id() == \Drupal::routeMatch()->getRawParameter('user')) {
      unset($variables['content']['members'][$index]);
    }
    // Make sure that if the first member is deleted, the second one will get
    // printed on the page.
    elseif ($index == 1) {
      $variables['content']['members'][0] = $variables['content']['members'][$index];
      unset($variables['content']['members'][$index]);
    }
  }
}

/**
 * Implements hook_preprocess_HOOK().
 */
function edsjp_preprocess_views_view_unformatted(&$variables) {
  if ($variables['view']->id() == 'dsj_private_messages' && $variables['view']->current_display == 'block_private_messages') {
    $tid = \Drupal::routeMatch()->getRawParameter('arg_1');
    // If the current thread is parameter is empty or last and the current item
    // displayed is the first one, we make that one the selected one, otherwise
    // we add the class based on the thread id from url.
    if (empty($tid) || $tid == 'last') {
      $latest_thread = \Drupal::entityTypeManager()->getStorage('private_message_thread')->getQuery()
        ->condition('members', [\Drupal::routeMatch()->getRawParameter('user')], 'IN')
        ->sort('updated', 'DESC')
        ->range(0, 1)
        ->accessCheck(TRUE)
        ->execute();
      $tid = reset($latest_thread);
    }
    foreach ($variables['rows'] as &$row) {
      $thread = $row['content']['#row']->_entity;
      if ($thread->id() == $tid) {
        $row['attributes']->addClass('thread-selected');
        break;
      }
    }
  }
}

/**
 * Helper function for unread private messages on a visible thread.
 */
function _edsjp_add_unread_messages_classes(&$variables) {
  $thread = $variables['private_message_thread'];
  $messages = $thread->getMessages();
  $variables['unread_count'] = 0;
  if (!empty($messages)) {
    // When the first message who's owner is not the user from the url and the
    // created timestamp is above the last checked timestamp, we add a new
    // class for the thread.
    foreach ($messages as $message) {
      $owner = $message->getOwnerId();
      if ($owner != \Drupal::routeMatch()->getRawParameter('user')) {
        $created = $message->getCreatedTime();
        $last_check_timestamp = \Drupal::service('user.data')->get('dsjp_private_message', \Drupal::routeMatch()->getRawParameter('user'), 'last_thread_' . $thread->id() . '_view');
        if (empty($last_check_timestamp) || $last_check_timestamp < $created) {
          if (!in_array('unread-pm', $variables['attributes']['class'])) {
            $variables['attributes']['class'][] = 'unread-pm';
          }
          $variables['unread_count']++;
        }
      }
    }
  }
}

/**
 * Implements hook_preprocess_HOOK().
 */
function edsjp_preprocess_private_message_notification_block(&$variables) {
  $url = Url::fromRoute('view.dsj_private_messages.private_messages_page', [
    'user' => \Drupal::currentUser()->id(),
    'arg_1' => 'last',
  ], [
    'attributes' => [
      'class' => ['private-message-page-link'],
    ],
  ]);
  $variables['private_message_page_link'] = Link::fromTextAndUrl($variables['new_message_count'], $url)->toString();
}

/**
 * Implements hook_preprocess_HOOK().
 */
function edsjp_preprocess_views_view(&$variables) {
  // Add total pages to the views templates.
  $pager_manager = \Drupal::service('pager.manager');
  $variables['total_pages'] = $pager_manager->getPager() ? \Drupal::service('pager.manager')->getPager()->getTotalPages() : 0;
  if ($variables['view']->id() == 'dsj_private_messages' && $variables['view']->current_display == 'private_messages_page') {
    $argument = \Drupal::routeMatch()->getRawParameter('arg_1');
    if (empty($argument) || $argument == 'last') {
      $variables['hide_row_mobile'] = TRUE;
    }
    else {
      $variables['hide_block_mobile'] = TRUE;
    }
    $block = Block::load('views_block__dsj_private_messages_block_private_messages');
    if ($block instanceof BlockInterface) {
      $variables['my_messages_block'] = \Drupal::entityTypeManager()->getViewBuilder('block')->view($block);
    }
  }
}
