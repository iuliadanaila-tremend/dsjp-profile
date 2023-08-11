<?php

/**
 * @file
 * Theme and preprocess functions for groups.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\dsjp_pledge\GroupServiceInterface;
use Drupal\user\Entity\User;

/**
 * Implements hook_preprocess_group().
 *
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 */
function edsjp_preprocess_group(&$variables) {
  global $base_url;
  $variables['base_url'] = $base_url;

  /** @var \Drupal\group\Entity\Group $group */
  $group = $variables['group'];
  $account = $variables['user'];
  $variables['member'] = TRUE;
  $variables['membership_path'] = $group->getGroupType()->id() === 'dsj_public_group' ? 'entity.group.join' : 'entity.group.group_request_membership';
  if ($group->getMember($account)) {
    $variables['member'] = FALSE;
  }
  if ($group->getGroupType()->id() == 'dsj_organization') {
    $variables['show_join_link'] = TRUE;
    $memberships = \Drupal::service('group.membership_loader')->loadByUser(\Drupal::currentUser());
    foreach ($memberships as $membership) {
      if ($membership->getGroup()->getGroupType()->id() == 'dsj_organization') {
        $variables['show_join_link'] = FALSE;
        break;
      }
    }
    $user = User::load(\Drupal::currentUser()->id());
    $variables['has_pending_invitation'] = \Drupal::service('dsjp_pledge.group_service')->userHasPendingInvitation($user, GroupServiceInterface::GROUP_TYPE_ORGANIZATION, $group->id());
    $is_pledger = $variables['group']->get('field_dsj_is_pledger')->getValue();
    $prefix_class = '';
    if (isset($is_pledger[0]['value']) && $is_pledger[0]['value'] == 1) {
      $prefix_class = 'is-pledger';
    }
    $variables['content']['field_dsj_is_pledger']['#prefix'] = '<div class="' . $prefix_class . '">';
    $variables['content']['field_dsj_is_pledger']['#suffix'] = '</div>';
    if ($variables['view_mode'] == 'dsj_listing') {
      $variables['content']['field_dsj_country']['#title'] = t('Geographical scope:');
      _edsjp_organisation_set_states($variables);
      $variables['add_node_link'] = _dsjp_pledge_get_add_pledge_link($variables['group']);
    }
    elseif ($variables['view_mode'] == 'full') {
      $variables['add_node_link'] = _dsjp_pledge_get_add_pledge_link($variables['group']);
      $variables['add_node_link']['#attributes']['class'][] = 'link-subscribe';
    }
    _edsjp_organisation_alter_contact_details($variables);
  }
}

/**
 * Change the contact details of an organisation.
 *
 * @param array $variables
 *   The preprocess variables.
 */
function _edsjp_organisation_alter_contact_details(array &$variables) {
  if ($variables['view_mode'] == 'dsj_block') {
    $mail = $variables['group']->get('field_dsj_contact_email_pledges')->value;
    $name = $variables['group']->get('field_dsj_contact_person_pledges')->value;
    if (!empty($mail) && !empty($name)) {
      $url = Url::fromUri('mailto:' . $mail);
      $variables['content']['field_dsj_contact_person_pledges'][0] = Link::fromTextAndUrl($name, $url)->toRenderable();
    }
  }
}

/**
 * Sets organisation workflow state.
 *
 * @param array $variables
 *   The process variables.
 */
function _edsjp_organisation_set_states(array &$variables) {
  if (\Drupal::routeMatch()->getRouteName() == 'view.groups.user_pledge_organisations') {
    $state = '';
    switch ($variables['group']->get('moderation_state')->value) {
      case 'draft':
        $state = t('Pending approval');
        break;

      case 'published':
        $state = t('Published');
        break;

      case 'rejected':
        $state = t('Rejected');
        break;
    }
    $variables['content']['current_state']['#markup'] = $state;
  }
}

/**
 * Implements hook_form_alter().
 */
function edsjp_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  if (strpos($form_id, 'group_dsj_national_coalition_') !== FALSE) {
    $fields = [
      'field_dsj_article_id',
      'field_dsj_page_id',
      'field_dsj_digital_skills_id',
      'field_dsj_event_id',
      'field_dsj_funding_opportunity_id',
      'field_dsj_good_practice_id',
      'field_dsj_initiative_id',
      'field_dsj_organization_id',
      'field_dsj_skills_intelligence_id',
      'field_dsj_strategy_id',
      'field_dsj_training_offer_id',
    ];
    // Set the field label.
    foreach ($fields as $field) {
      $form[$field]['widget'][0]['value']['#title'] = $form[$field]['widget']['#title'];
    }
  }
}
