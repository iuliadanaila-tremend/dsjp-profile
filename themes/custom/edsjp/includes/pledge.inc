<?php

/**
 * @file
 * Contains theme functionality for pledge content types.
 */

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\dsjp_pledge\GroupServiceInterface;
use Drupal\node\NodeInterface;

/**
 * Preprocess function for pledge content type.
 */
function _edsjp_preprocess_dsj_pledge(&$variables) {
  if ('dsj_pledge' === $variables['node']->getType()) {
    switch ($variables['view_mode']) {
      case 'listing':
        if (Drupal::routeMatch()->getRouteName() == 'view.pledges.user_pledges') {
          $variables['content']['current_state']['#markup'] = _edsjp_set_pledge_status($variables);
        }
        $properties = [
          'field_dsj_pledge' => $variables['node']->id(),
          'type' => 'dsj_pledge_initiative',
        ];
        if (Drupal::routeMatch()->getRouteName() == 'view.pledges.page') {
          $properties['status'] = TRUE;
        }
        $initiatives = \Drupal::entityTypeManager()
          ->getStorage('node')->loadByProperties($properties);

        if (!empty($initiatives)) {
          foreach ($initiatives as $initiative) {
            $type = $initiative->get('field_dsj_initiative_type')->value;
            if (!isset($variables['content']['initiatives'][$type])) {
              $variables['content']['initiatives'][$type] = Drupal::entityTypeManager()->getViewBuilder('node')->view($initiative, 'listing');
            }
          }
        }

        $variables['content']['multiple_organization'] = _edjp_preprocess_pledge_check_if_pledge_has_many_organization($variables['node']);
        $variables['content']['organization_name'] = _edjp_preprocess_pledge_get_organization_name($variables['node']);
        $variables['content']['end_date'] = _edjp_preprocess_pledge_get_end_date($variables['node']);
        break;

      case 'card':
        $variables['content']['field_dsj_image']['#markup'] = '';
        $variables['content']['field_dsj_image']['#prefix'] = '<div class="field field--name-field-dsj-image field__item">';
        $variables['content']['field_dsj_image']['#sufix'] = '</div>';
        $variables['content']['field_dsj_image'][0]['#theme'] = 'responsive_image';
        $variables['content']['field_dsj_image'][0]['#responsive_image_style_id'] = 'card_image';
        $variables['content']['field_dsj_image'][0]['#url'] = Url::fromRoute('entity.node.canonical', ['node' => $variables['node']->id()]);
        _edsjp_set_node_image_placehoders($variables);
        break;

    }
  }
}

/**
 * Set pledge listing status based on the moderation state.
 *
 * @param array $variables
 *   The preprocess variables.
 *
 * @return \Drupal\Core\StringTranslation\TranslatableMarkup
 *   Returns the status name.
 */
function _edsjp_set_pledge_status(array $variables): TranslatableMarkup {
  $state = '';

  switch ($variables['node']->get('moderation_state')->value) {
    case 'draft':
      $state = t('Draft');
      break;

    case 'ready_for_review':
    case 'ready_for_publication':
    case 'needs_safe_validation':
      $state = t('Pending');
      break;

    case 'published':
      $state = t('Published');
      break;

    case 'archived':
      $state = t('Archived');
      break;

    case 'needs_changes':
      $state = t('Needs changes');
      break;
  }

  return t('Status: @state', [
    '@state' => $state,
  ]);
}

/**
 * Preprocess function for pledge initiative content type.
 */
function _edsjp_preprocess_dsj_pledge_initiative(&$variables) {
  if ('dsj_pledge_initiative' === $variables['node']->getType()) {
    if ($variables['view_mode'] == 'related') {
      $quantity = $variables['node']->get('field_dsj_quantity')->getValue();
      if (!empty($quantity[0]['value'])) {
        $json = $variables['node']->get('field_dsj_initiative_status')->getValue();
        if (!empty($json[0]['value'])) {
          $data = json_decode($json[0]['value'], TRUE);
          $data_value = array_sum(array_column($data, 'value'));
          $variables['quantity'] = $data_value;
          $percentage = ($data_value * 100) / $quantity[0]['value'];
          $variables['percentage'] = $percentage > 0 ? number_format((float) $percentage, 2) : 0;
        }
      }
    }
    elseif ($variables['view_mode'] == 'teaser' || $variables['view_mode'] == 'listing') {
      _edsjp_preprocess_pledge_initiative_teaser($variables);
    }
  }
}

/**
 * Preprocess function for teaser view mode pledge initiatives.
 */
function _edsjp_preprocess_pledge_initiative_teaser(array &$variables) {
  /** @var \Drupal\Core\Config\ConfigFactory $config */
  $config = Drupal::configFactory()->get('dsjp_pledge.initiative_type_description_config');
  $initiative_type = $variables['node']->get('field_dsj_initiative_type')->value;
  $initiative_name = '';
  switch ($initiative_type) {
    case 'education':
      $initiative_name = t('Education');
      break;

    case 'ict_professionals':
      $initiative_name = t('ICT Professionals');
      break;

    case 'labour_force':
      $initiative_name = t('Labour Force');
      break;

    case 'other':
      $initiative_name = t('All Citizens');
      break;
  }
  $variables['initiative_name'] = $initiative_name;
  $variables['initiative_type'] = $initiative_type;
  $variables['initiative_type_description'] = $config->get($initiative_type) ?? '';
}

/**
 * Check if Pledge has many organizations.
 *
 * @param \Drupal\Core\Entity\ContentEntityInterface $entity
 *   An entity by load Group.
 * @param int $min_count
 *   Min number of organizations what must be in the Pledge.
 *
 * @return bool
 *   Return TRUE if the Pledge has many organizations.
 */
function _edjp_preprocess_pledge_check_if_pledge_has_many_organization(ContentEntityInterface $entity, int $min_count = 2): bool {
  /** @var \Drupal\dsjp_pledge\GroupServiceInterface $service */
  $service = Drupal::service('dsjp_pledge.group_service');
  $organizations = $service->loadMultipleGroupsByEntity($entity, GroupServiceInterface::GROUP_TYPE_ORGANIZATION);

  return count($organizations) >= $min_count;
}

/**
 * Get organization label.
 *
 * @param \Drupal\Core\Entity\ContentEntityInterface $entity
 *   An entity by load Group.
 *
 * @return string
 *   Return Organization name.
 */
function _edjp_preprocess_pledge_get_organization_name(ContentEntityInterface $entity): string {
  /** @var \Drupal\dsjp_pledge\GroupServiceInterface $service */
  $service = Drupal::service('dsjp_pledge.group_service');
  $organization = $service->loadGroupByEntity($entity, GroupServiceInterface::GROUP_TYPE_ORGANIZATION);
  if ($organization) {
    return $organization->get('label')->value;
  }

  return '';
}

/**
 * Get end date.
 *
 * @param \Drupal\node\NodeInterface $node
 *   The Pledge node .
 *
 * @return string
 *   Return end date.
 */
function _edjp_preprocess_pledge_get_end_date(NodeInterface $node): string {
  if (!$node->get('field_dsj_pledge_dates')->isEmpty()) {
    $date_value = $node->get('field_dsj_pledge_dates')->end_value;
    $date = DrupalDateTime::createFromFormat('Y-m-d', $date_value);
    return Drupal::service('date.formatter')->format($date->getTimestamp(), 'default_timeless_short_date');
  }

  return '';
}
