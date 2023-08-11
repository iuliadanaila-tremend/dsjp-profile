<?php

/**
 * @file
 * Theme and preprocess functions for users.
 */

use Drupal\block_content\BlockContentInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function edsjp_theme_suggestions_user_alter(array &$suggestions, array $variables) {
  if (!isset($variables['elements']['#view_mode'])) {
    return;
  }

  $viewMode = $variables['elements']['#view_mode'];

  array_splice($suggestions, 1, 0, 'user');
  array_splice($suggestions, 2, 0, 'user__' . str_replace('-', '_', $viewMode));
}

/**
 * Implements hook_preprocess_user().
 */
function edsjp_preprocess_user(&$variables) {
  $account = $variables['elements']['#user'];
  $variables = _edsp_get_author_variables($variables, $account);
  // Add the flag library for user listing view mode.
  if ('listing' === $variables['elements']['#view_mode']) {
    $variables['#attached']['library'][] = 'edsjp/flags';
    if (isset($variables['content']['field_dsj_job_title'][0]) &&
      isset($variables['content']['field_dsj_organisation'][0])) {
      $variables['content']['field_dsj_job_title'][0]['#context']['value'] .= ' ' . t('at') . ' ';
    }
  }
  elseif ($variables['elements']['#view_mode'] === 'full') {
    if (!empty($variables['content']['field_dsj_i_am_interested_in']['#items'])) {
      $technologies = $variables['content']['field_dsj_i_am_interested_in']['#items']->getValue();
      foreach ($technologies as $key => $technology) {
        $url = Link::fromTextAndUrl($variables['content']['field_dsj_i_am_interested_in'][$key]['#markup'], Url::fromRoute('view.dsj_search.page_1', [], [
          'query' => [
            'f' => [
              'digital_technology:' . $technology['value'],
            ],
          ],
        ]));
        $variables['content']['field_dsj_i_am_interested_in'][$key]['#markup'] = $url->toString();
      }
    }
    // Hide the fields from the user display if the user logged in is not the
    // one the page is being displayed for.
    if (\Drupal::currentUser()->id() != $variables['user']->id()) {
      unset($variables['elements']['#group_children']['field_dsj_i_am_student']);
      unset($variables['elements']['#group_children']['field_dsj_i_am_job_seeker']);
      unset($variables['elements']['#group_children']['field_dsj_skill_level_interest']);
      unset($variables['content']['field_dsj_i_am_student']);
      unset($variables['content']['field_dsj_i_am_job_seeker']);
      unset($variables['content']['field_dsj_skill_level_interest']);
    }
    _edsjp_set_user_pledger_status($variables);
  }
  elseif ($variables['elements']['#view_mode'] == 'private_message') {
    _edsjp_preprocess_user_private_message($variables);
  }
}

/**
 * Sets user pledger state.
 *
 * @param array $variables
 *   The preprocess variables.
 */
function _edsjp_set_user_pledger_status(array &$variables) {
  $content = \Drupal::entityQuery('node')
    ->condition('type', ['dsj_pledge', 'dsj_pledge_initiative'], 'IN')
    ->condition('uid', \Drupal::currentUser()->id())
    ->condition('status', 1)
    ->accessCheck(TRUE)
    ->range(0, 1)
    ->execute();
  $variables['is_pledger'] = !empty($content);
}

/**
 * Helper function for preprocessing users visible on "My messages" block.
 */
function _edsjp_preprocess_user_private_message(&$variables) {
  $route_user = \Drupal::routeMatch()->getParameter('user');
  if (!empty($route_user)) {
    $route_user = User::load($route_user);
    $user = $variables['user'];
    $thread = \Drupal::service('private_message.mapper')->getThreadIdForMembers([
      $route_user,
      $user,
    ]);
    $thread = empty($thread) ? 'last' : $thread;
    $url = Url::fromRoute('view.dsj_private_messages.private_messages_page', [
      'user' => $route_user->id(),
      'arg_1' => $thread,
    ]);
    $variables['thread_url'] = $url->toString();
  }
}

/**
 * Function for providing custom author variables.
 */
function _edsp_get_author_variables($variables, $account, $include_picture = TRUE, $imageStyle = 'thumbnail') {
  $names = _edsjp_get_user_fullname($account);
  $variables['author_name'] = implode(' ', $names);
  if (!empty($names) && count($names) > 1) {
    $authorInitials = mb_strtoupper(mb_substr($names[0], 0, 1) . mb_substr(end($names), 0, 1));
  }
  else {
    $authorInitials = mb_strtoupper(mb_substr($account->getDisplayName(), 0, 2));
  }

  if ($include_picture) {
    if (!$account->get('field_dsj_picture')->isEmpty()) {
      $variables['author_avatar'] = $account->get('field_dsj_picture')->view($imageStyle);
    }
    else {
      $class = $account->isAnonymous() ? 'is-anonymous' : 'is-logged-in';
      $variables['author_avatar']['#prefix'] = '<p data-letters="' . $authorInitials . '" class="' . $class . '">';
      $variables['author_avatar']['#suffix'] = '</p>';
    }
  }

  return $variables;
}

/**
 * Gets the user full name.
 *
 * @param \Drupal\user\UserInterface $account
 *   The user object to get the name from.
 *
 * @return array
 *   An array containing the full name.
 */
function _edsjp_get_user_fullname(UserInterface $account) {
  $names = [];
  /** @var \Drupal\Core\Field\FieldItemList $firstNameValue */
  $firstNameValue = $account->get('field_dsj_firstname');
  if (!$firstNameValue->isEmpty()) {
    $names[] = $firstNameValue->value;
  }

  /** @var \Drupal\Core\Field\FieldItemList $firstNameValue */
  $lastNameValue = $account->get('field_dsj_lastname');
  if (!$lastNameValue->isEmpty()) {
    $names[] = $lastNameValue->value;
  }

  return !empty($names) ? $names : [$account->getDisplayName()];
}

/**
 * Implements hook_preprocess_HOOK().
 */
function edsjp_preprocess_region(&$variables) {
  if ($variables['region'] === 'content') {
    $route_match = \Drupal::routeMatch();
    $route_names = [
      'entity.user.canonical',
      'simplenews.newsletter_subscriptions_user',
      'entity.user.edit_form',
      'message_subscribe_ui.tab',
      'entity.webform_submission.user',
      'view.groups.dsj_user_groups',
      'view.dsj_assessments.page_1',
      'view.webform_submissions.user_webform_submissions',
      'view.dsj_private_messages.private_messages_page',
    ];
    if (in_array($route_match->getRouteName(), $route_names) && \Drupal::currentUser()->id() == $route_match->getRawParameter('user')) {
      $block = \Drupal::entityTypeManager()->getStorage('block_content')->load(20);
      if ($block instanceof BlockContentInterface) {
        $variables['dashboard_info'] = \Drupal::entityTypeManager()->getViewBuilder('block_content')->view($block);
      }
    }
  }
}
