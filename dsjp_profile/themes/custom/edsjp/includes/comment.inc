<?php

/**
 * @file
 * Theme and preprocess functions for comments.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Implements hook_form_FORM_ID_alter().
 *
 * Remove textarea help on comment form and force plain text format.
 */
function edsjp_form_comment_dsj_comment_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  if (isset($form['comment_body'])) {
    $form["comment_body"]["widget"][0]['#allowed_formats'] = ['basic_html'];
    $form["comment_body"]["widget"]['#after_build'][] = '_edsjp_allowed_formats_remove_textarea_help';
  }
  if (isset($form['field_dsj_attachments'])) {
    $title = t('Add a picture or a file');
    $form['field_dsj_attachments']['#prefix'] = '<span class="comment-attachment-toggle" title="' . $title . '"></span>';
    $form['field_dsj_attachments']['#attributes']['class'][] = 'hidden';
  }
}

/**
 * Remove textarea help.
 *
 * @param array $form_element
 *   Form element to alter.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   Form state object.
 *
 * @return array
 *   Return changed form element.
 */
function _edsjp_allowed_formats_remove_textarea_help(
  array $form_element,
  FormStateInterface $form_state
) {

  if (isset($form_element[0]['format'])) {
    // All this stuff is needed to hide the help text.
    unset(
      $form_element[0]['format']['guidelines'],
      $form_element[0]['format']['help'],
      $form_element[0]['format']['#type'],
      $form_element[0]['format']['#theme_wrappers']
    );
  }

  return $form_element;
}

/**
 * Implements hook_preprocess_HOOK().
 */
function edsjp_preprocess_comment(&$variables) {
  $parent_comment = $variables['parent_comment'];
  if (!empty($parent_comment)) {
    $variables['reply_to'] = t('<p class="replay-comm">in reply to <a href=":url">@username</a></p>', [
      ':url' => '#comment-' . $parent_comment->id(),
      '@username' => implode(' ', _edsjp_get_user_fullname($parent_comment->get('uid')->entity)),
    ]);
  }
  // Hide the comment links for anonymous users.
  if (\Drupal::currentUser()->isAnonymous()) {
    unset($variables['content']['links']);
  }
  // If we are on discussions listing, alter the reply link in order to redirect
  // to the comment on the discussion page.
  if ($variables['elements']['#view_mode'] == 'dsj_listing_preview') {
    unset($variables['content']['links']);
    /** @var \Drupal\comment\Entity\Comment $comment */
    $comment = $variables['comment'];
    $comment->getCommentedEntityId();
    $child_ids = \Drupal::entityTypeManager()->getStorage('comment')->getChildCids([$comment->id() => $comment->id()]);
    $url = Url::fromRoute('entity.node.canonical', [
      'node' => $comment->getCommentedEntityId(),
    ], [
      'fragment' => 'comment-' . $comment->id(),
    ]);
    $link = Link::fromTextAndUrl(t('Reply@count', [
      '@count' => '(' . count($child_ids) . ')',
    ]), $url);
    $variables['content']['links'] = $link->toRenderable() + [
      '#attributes' => [
        'class' => 'reply-link',
      ],
    ];

  }
}

/**
 * Implements hook_preprocess_HOOK().
 */
function edsjp_preprocess_flag__dsj_comment_voting(&$variables) {
  if (\Drupal::currentUser()->isAnonymous()) {
    $variables['user_login_link'] = Link::createFromRoute(t('Like'), 'user.login', [], [
      'query' => [
        'returnto' => \Drupal::requestStack()->getCurrentRequest()->getRequestUri(),
      ],
    ])->toRenderable();
  }
}
