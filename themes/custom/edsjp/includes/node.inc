<?php

/**
 * @file
 * Theme and preprocess functions for nodes.
 */

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupContent;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;
use Drupal\views\Views;

/**
 * Implements hook_preprocess_HOOK().
 *
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 */
function edsjp_preprocess_node(&$variables) {
  global $base_url;

  $variables['title'] = $variables['node']->label();
  $variables['base_url'] = $base_url;

  if ('teaser' === $variables['view_mode']) {
    if ('dsj_event' === $variables['node']->getType() && empty($variables['node']->get('field_dsj_image')->getValue())) {
      // If no image was uploaded, add a class on the article tag.
      $variables['attributes']['class'][] = 'no-image';
    }
  }
  elseif ('full' === $variables['view_mode']) {
    _edsjp_load_author_info($variables);
    _edsjp_add_social_share_block($variables);
    _edsjp_moderation_state_alter($variables);
  }
  elseif ('most_popular_card' === $variables['view_mode']) {
    // Expose the comment count as a variable for the most popular card
    // display mode.
    $variables['comment_count'] = $variables['node']->get('field_dsj_comments')->comment_count;
    $fragment = 'node-' . str_replace('_', '-', $variables['node']->getType()) . '-field-dsj-comments';
    $variables['comment_form_url'] = Url::fromRoute(
      'entity.node.canonical',
      ['node' => $variables['node']->id()],
      ['fragment' => $fragment],
    );
  }
  elseif ($variables['view_mode'] === 'search_result') {
    _edsjp_handle_search_results($variables);
  }

  // Set image based on fields for specific content types.
  if (isset($variables["content"]["field_dsj_image"]) && empty($variables['node']->get('field_dsj_image')
    ->getValue())) {
    if ($variables['view_mode'] == 'full' &&
      in_array($variables['node']->getType(), ['dsj_article', 'dsj_event'])) {
      unset($variables["content"]["field_dsj_image"][0]);
    }
    else {
      _edsjp_set_node_image_placehoders($variables);
    }
  }

  if ('dsj_discussion' === $variables['node']->getType()) {
    if (in_array($variables['view_mode'], ['listing', 'teaser'])) {
      _edsjp_preprocess_discussion_node($variables);
    }
  }
  elseif ('dsj_event' === $variables['node']->getType()) {
    _edsjp_preprocess_event_node($variables);
  }
  elseif ('dsj_good_practice' === $variables['node']->getType()) {
    _edsjp_preprocess_good_practice_node($variables);
  }

  _edsjp_set_listing_url($variables);
  _edsjp_preprocess_dsj_pledge($variables);
  _edsjp_preprocess_dsj_pledge_initiative($variables);
  // Hide learning state form.
  // _edsjp_add_learning_states_form($variables);
}

/**
 * Implements hook_preprocess_HOOK().
 */
function edsjp_preprocess_node__dsj_listing(&$variables) {
  if (isset($variables['view_mode']) && $variables['view_mode'] == 'full') {
    $view = Views::getView('dsj_search');
    $view->setDisplay('listing_block');
    $view->execute();

    $variables['content']['view'] = $view->buildRenderable('listing_block');
    $variables['content']['view']['#weight'] = 120;
  }
}

/**
 * Implements hook_preprocess_HOOK().
 */
function _edsjp_preprocess_event_node(&$variables) {
  $event = $variables['node'];

  $event_settings = $event->get('field_dsj_event_setting')->getValue();
  $event_data = [];
  if (!empty($event_settings)) {
    $total_settings = count($event_settings);
    // Always have the event settings as fallback, and make sure that the label
    // is hidden.
    if (isset($variables['content']['field_dsj_event_setting'])) {
      $event_data = $variables['content']['field_dsj_event_setting'];
      $event_data['#label_display'] = 'hidden';
      _edsjp_add_event_list_suffix($event_data, $total_settings);
    }
    $values = ['on location', 'blended'];
    $settings_field_values = array_column($event_settings, 'value');
    if (!empty(array_intersect($settings_field_values, $values))) {
      $event_location = $event->get('field_dsj_event_location')->getValue();
      if (!empty($event_location) && isset($variables['content']['field_dsj_event_location'])) {
        $event_data = $variables['content']['field_dsj_event_location'];
      }
    }
  }
  if ($variables['view_mode'] === 'listing') {
    $variables['content']['field_dsj_event_setting'] = $event_data;
  }
  else {
    $variables['event_data'] = $event_data;
  }
}

/**
 * Adds comma suffix for every event setting displayed.
 *
 * @param array $event_data
 *   The renderable array to add the suffix to.
 * @param int $total_settings
 *   The number of available event settings.
 */
function _edsjp_add_event_list_suffix(array &$event_data, $total_settings) {
  // If there is more than one event setting, add the delimiter to the values.
  if ($total_settings > 1) {
    foreach ($event_data['#items'] as $key => $data) {
      if ($key < $total_settings - 1) {
        $event_data[$key]['#suffix'] = ', ';
      }
    }
  }
}

/**
 * Implements hook_preprocess_HOOK().
 */
function _edsjp_preprocess_good_practice_node(&$variables) {
  // Display 2 rows for date if it's different.
  if ($variables['view_mode'] === 'full') {
    // Get start and end date.
    $dsjDate = $variables['content']['field_dsj_date'];

    if (isset($dsjDate[0]['end_date']['#text']) &&
      !empty($dsjDate[0]['end_date']['#text'])
    ) {
      unset($variables['content']['field_dsj_date'][0]['separator']);

      if ($dsjDate[0]['end_date']['#text'] != $dsjDate[0]['start_date']['#text']) {
        $variables['content']['field_dsj_date_end'] = $variables['content']['field_dsj_date'];
        $variables['content']['field_dsj_date_end']['#field_name'] = 'field_dsj_date_end';
        $variables['content']['field_dsj_date']['#title'] = t('Start date');
        $variables['content']['field_dsj_date_end']['#title'] = t('End date');
        unset($variables['content']['field_dsj_date_end'][0]['start_date']);
        unset($variables['content']['field_dsj_date'][0]['end_date']);

        $variables['elements']['#fieldgroups']['group_good_practice_details']->children[] = 'field_dsj_date_end';
        $variables['elements']['#group_children']['field_dsj_date_end'] = 'group_good_practice_details';
      }
      unset($variables['content']['field_dsj_date'][0]['end_date']);

    }
  }

}

/**
 * Function for modifying the content moderation.
 */
function _edsjp_moderation_state_alter(&$variables) {
  if ($variables['node']->hasField('moderation_state')) {
    $users = _edsjp_get_group_users($variables['node']);
    $current_state = $variables['node']->get('moderation_state')->getString();
    $states = ['needs_changes', 'draft'];
    $roles = \Drupal::currentUser()->getRoles();
    if (($current_state == 'imported' && empty(array_intersect([
      'dsj_content_editor',
      'dsj_reviewer',
    ], $roles))) ||
      // If the current node is on "Needs changes" or "Draft" status and the
      // current user is not the author, hide the moderation control options.
      (!in_array('dsj_reviewer', $roles) && in_array($current_state, $states))) {
      if (in_array(\Drupal::currentUser()->id(), $users) && !in_array($current_state, $states)) {
        unset($variables['content']['content_moderation_control']);
      }
    }
  }
}

/**
 * Gets NC group members ids.
 *
 * @param \Drupal\node\NodeInterface $node
 *   The node object.
 *
 * @return array
 *   Returns an array of members ids.
 */
function _edsjp_get_group_users(NodeInterface $node) {
  $users = [$node->getOwnerId()];
  if (!$node->isNew()) {
    $group_content = GroupContent::loadByEntity($node);
    if (!empty($group_content)) {
      foreach ($group_content as $gcontent) {
        $group = $gcontent->getGroup();
        if ($group->getGroupType()->id() == 'dsj_national_coalition') {
          $users = [];
          $members = $group->getMembers();
          foreach ($members as $member) {
            $users[] = $member->getUser()->id();
          }
        }
      }
    }
  }

  return $users;
}

/**
 * Helper function for discussion nodes.
 *
 * @param array $variables
 *   Hook preprocess node variables.
 */
function _edsjp_preprocess_discussion_node(array &$variables) {
  $variables['rating_count'] = _edsjp_add_rating_count($variables);
  $variables['comment_count'] = $variables['node']->get('field_dsj_comments')->comment_count;
  $variables['comment_form_url'] = Url::fromRoute(
    'entity.node.canonical',
    ['node' => $variables['node']->id()],
    ['fragment' => 'node-dsj-discussion-field-dsj-comments'],
  );

  $account = User::load($variables['node']->getOwnerId());

  $user_view_mode = ($variables['view_mode'] == 'teaser') ? 'thumbnail_image_with_crop' : 'compact';
  $variables = _edsp_get_author_variables($variables, $account, TRUE, $user_view_mode);
}

/**
 * Helper function that renders the social share block.
 */
function _edsjp_add_social_share_block(array &$variables) {
  // Expose social share block as a variable.
  $block_manager = \Drupal::service('plugin.manager.block');
  // You can hard code configuration or you load from settings.
  $config = [];
  $plugin_block = $block_manager->createInstance('social_share', $config);
  // Some blocks might implement access check.
  $access_result = $plugin_block->access(\Drupal::currentUser());
  // Return empty render array if user doesn't have access.
  // $access_result can be boolean or an AccessResult class.
  if (is_object($access_result) && $access_result->isForbidden() || is_bool($access_result) && !$access_result) {
    return;
  }
  $variables['socialShare'] = $plugin_block->build();
}

/**
 * Function for providing author information.
 */
function _edsjp_load_author_info(array &$variables) {
  $builder = \Drupal::entityTypeManager()->getViewBuilder('user');
  $variables['author_extended_info'] = $builder->view($variables['node']->getOwner(), 'token');
}

/**
 * Custom function to insert rating count in variables.
 */
function _edsjp_add_rating_count(array $variables) {
  $variables['rating_count'] = 0;
  $flag_service = \Drupal::service('flag.count');
  $counts = $flag_service->getEntityFlagCounts($variables['node']);

  if (isset($counts['voting'])) {
    $variables['rating_count'] = $counts['voting'];
  }

  return $variables['rating_count'];
}

/**
 * Custom function to set placeholder based on ct and categories.
 *
 * @param array $variables
 *   Preprocess node variables.
 */
function _edsjp_set_node_image_placehoders(array &$variables) {
  /** @var \Drupal\Core\Extension\ExtensionPathResolver $extension_path */
  $extension_path = Drupal::service('extension.path.resolver');
  $view_mode = $variables['view_mode'];
  $fieldName = NULL;
  $contentType = $variables['node']->getType();

  [
    $fieldName,
    $fieldValue,
  ] = _get_placeholder_field_name_and_value($variables);

  if ($fieldName && $fieldValue) {
    $filePaths = [
      $contentType . '/' . $fieldName . '-' . $fieldValue . '-' . $view_mode . '.png',
      $contentType . '/' . $fieldName . '-' . $fieldValue . '.png',
      $contentType . '/' . $fieldName . '.png',
      $fieldName . '-' . $fieldValue . '-' . $view_mode . '.png',
      $fieldName . '-' . $fieldValue . '.png',
      $fieldName . '.png',
      "$contentType/default.png",
      'default.png',
    ];

    $filePaths = _edsjp_set_pledge_placeholder($contentType, $filePaths, $view_mode);
    foreach ($filePaths as $filePath) {
      $uri = $extension_path->getPath('theme', 'edsjp') . '/assets/placeholders/' . $filePath;
      if (file_exists($uri)) {
        $filesUri = 'public://placeholders/' . $filePath;
        if (_edsjp_prepare_placehoder($uri, $filesUri)) {
          // Change theme based on prev theme since we don't have item var.
          if ($variables['content']['field_dsj_image'][0]['#theme'] == 'image_formatter') {
            $variables['content']['field_dsj_image'][0]['#theme'] = 'image_style';
            $variables['content']['field_dsj_image'][0]['#style_name'] = $variables['content']['field_dsj_image'][0]['#image_style'];
          }
          elseif ($variables['content']['field_dsj_image'][0]['#theme'] == 'responsive_image_formatter') {
            $variables['content']['field_dsj_image'][0]['#theme'] = 'responsive_image';
          }

          // Set new image uri.
          $variables['content']['field_dsj_image'][0]['#uri'] = $filesUri;

          // Wrap image in url.
          if (isset($variables['content']['field_dsj_image'][0]['#url'])) {
            $url = $variables['content']['field_dsj_image'][0]['#url']->toString();
            $variables['content']['field_dsj_image'][0]['#prefix'] = "<a href='" . $url . "' >";
            $variables['content']['field_dsj_image'][0]['#suffix'] = '</a>';
          }
        }

        break;
      }
    }
  }
}

/**
 * Set the pledge image placeholder paths.
 *
 * @param string $contentType
 *   The content type name.
 * @param array $filePaths
 *   The file paths currently available.
 *
 * @return array
 *   Returns the new file paths.
 */
function _edsjp_set_pledge_placeholder($contentType, array $filePaths, $view_mode = NULL) {
  if ($contentType == 'dsj_pledge') {
    $filePaths = [
      "$contentType/$view_mode.png",
      "$contentType/default_1.png",
      'default.png',
    ];
  }

  return $filePaths;
}

/**
 * Move placeholder in files if doesn't exist.
 *
 * @param string $uri
 *   Placeholder uri in theme.
 * @param string $filesUri
 *   Placehoder uri in files.
 */
function _edsjp_prepare_placehoder($uri, $filesUri) {
  if (file_exists($filesUri) && sha1_file($uri) == sha1_file($filesUri)) {
    return TRUE;
  }
  // Copy from theme to files.
  /** @var \Drupal\Core\File\FileSystemInterface $file_system */
  $file_system = \Drupal::service('file_system');
  $dir = $file_system->dirname($filesUri);

  try {
    $file_system->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY);
    $file_system->copy($uri, $filesUri, FileSystemInterface::EXISTS_REPLACE);
  }
  catch (Exception $e) {
    return FALSE;
  }

  return TRUE;
}

/**
 * Helper function to output needed field name based on CT.
 *
 * @param array $variables
 *   Node preprocess variables.
 *
 * @return array|null
 *   Field name involved in placeholder name generation.
 *
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 */
function _get_placeholder_field_name_and_value(array $variables): ?array {
  $fieldName = NULL;
  $fieldValue = NULL;

  /** @var \Drupal\node\Entity\Node $node */
  $node = $variables['node'];

  $contentType = $node->getType();

  switch ($contentType) {
    case('dsj_funding_opportunity'):
      // Get value of field_dsj_form_of_funding.
      $fieldName = 'field_dsj_form_of_funding';
      $fundingOpportunityTerms = $node->get($fieldName)->referencedEntities();
      foreach ($fundingOpportunityTerms as $fundingOpportunityTerm) {
        if ($fundingOpportunityTerm->hasField('field_dsj_form_of_funding_code') && !$fundingOpportunityTerm->get('field_dsj_form_of_funding_code')->isEmpty()) {
          $fieldValue = $fundingOpportunityTerm->get('field_dsj_form_of_funding_code')->value;
          break;
        }
      }
      break;

    case('dsj_initiative'):
    case('dsj_good_practice'):
      // Get value of field_dsj_type_of_initiative.
      $fieldName = 'field_dsj_type_of_initiative';
      $fieldValue = $node->get($fieldName)->value;
      break;

    case('dsj_training_offer'):
      // Get value of field_dsj_type_of_training.
      $fieldName = 'field_dsj_typology_of_training';
      $fieldValueRaw = $node->get($fieldName)->value;
      // Split the code by slashes and get the country code.
      $code = explode('/', $fieldValueRaw);
      $fieldValue = mb_strtolower(end($code));
      break;

    case('dsj_digital_skills_resource'):
      // Get value of field_dsj_skills_resource_type.
      $fieldName = 'field_dsj_skills_resource_type';
      $fieldValue = $node->get($fieldName)->value;
      break;

    case('dsj_strategy'):
    case('dsj_national_coalition'):
    case 'dsj_pledge':
      $fieldName = 'field_dsj_geographic_scope';
      $countryTerms = $node->get($fieldName)->referencedEntities();
      foreach ($countryTerms as $countryTerm) {
        if ($countryTerm->hasField('field_dsj_country_code') && !$countryTerm->get('field_dsj_country_code')->isEmpty()) {
          $skosCode = $countryTerm->get('field_dsj_country_code')
            ->getValue()[0]['target_id'];

          // Split the code by slashes and get the country code.
          $code = explode('/', $skosCode);
          $fieldValue = mb_strtolower(end($code));
          break;
        }
      }

      break;
  }

  return [$fieldName, $fieldValue];
}

/**
 * Remove ... from search excerpt.
 *
 * @param array $variables
 *   Preprocess node variables.
 */
function _edsjp_handle_search_results(array &$variables) {
  if (isset($variables['content']['search_api_excerpt'])) {
    if (!empty($variables['content']['search_api_excerpt']['#markup'])) {
      // Remove starting ...
      $variables['content']['search_api_excerpt']['#markup'] = ltrim($variables['content']['search_api_excerpt']['#markup'], '…');
      unset($variables['content']['body']);
    }
  }
}

/**
 * Helper function to add link to listing pages per content type.
 *
 * @param array $variables
 *   Hook preprocess node variables.
 */
function _edsjp_set_listing_url(array &$variables) {
  $ct_pill = [];
  $ctToListing = [
    'dsj_article' => '/articles',
    'dsj_event' => '/events',
    'dsj_organization' => '/organisations',
    'dsj_initiative' => '/initiatives',
    'dsj_funding_opportunity' => '/funding-opportunities',
    'dsj_good_practice' => '/good-practices',
    'dsj_digital_skills_resource' => '/resources',
    'dsj_skills_intelligence' => '/publications',
    'dsj_pledge' => '/inspiration/pledges',
    'dsj_strategy' => '/strategies',
    'dsj_training_offer' => '/training-offers',
    'dsj_discussion' => '/discussions',
    'dsj_national_coalition' => '/national-coalitions',
  ];

  $contentType = $variables['node']->getType();
  $nid = $variables['node']->id();
  $contentTypeLabel = $variables['node']->type->entity->label();

  $ct_colors = [
    'dsj_article' => 'latest',
    'dsj_event' => 'latest',
    'dsj_initiative' => 'actions',
    'dsj_funding_opportunity' => 'opportunities',
    'dsj_good_practice' => 'inspiration',
    'dsj_digital_skills_resource' => 'inspiration',
    'dsj_pledge' => 'inspiration',
    'dsj_skills_intelligence' => 'inspiration',
    'dsj_strategy' => 'actions',
    'dsj_training_offer' => 'opportunities',
    'dsj_discussion' => 'community',
  ];

  if (isset($ctToListing[$contentType])) {
    $path = $ctToListing[$contentType];
    $options = [
      'attributes' => [
        'class' => [
          'type-pill',
          'color-' . ($ct_colors[$contentType] ?? 'default'),
        ],
      ],
    ];

    if ($contentType == 'dsj_discussion') {
      $groupId = _dsjp_community_node_is_group_content($nid);
      if ($groupId) {
        $path = "/group/$groupId/discussions";
      }
    }
    elseif ($contentType == 'dsj_article') {
      $contentTypeLabel = t('News');
      $path = '/news';

      $articleTypes = $variables['node']->get('field_dsj_type_of_article')->referencedEntities();
      if (isset($articleTypes) && !empty($articleTypes)) {
        $contentTypeLabel = $articleTypes[0]->getName();
        if (!$articleTypes[0]->get('path')->isEmpty()) {
          $path = $articleTypes[0]->get('path')->alias;
        }
      }
    }

    $url = Url::fromUserInput($path, $options);
    $ct_pill = Link::fromTextAndUrl($contentTypeLabel, $url)
      ->toRenderable();
  }

  $variables['ct_pill'] = $ct_pill;
}

/**
 * Implements hook_preprocess_HOOK().
 */
function edsjp_preprocess_field_group_html_element(&$variables) {
  // Alter the article's detail page div title based on the article type field.
  if ($variables['element']['#group_name'] == 'group_article_details') {
    $node = \Drupal::routeMatch()->getParameter('node');
    if (!empty($node) && $node instanceof NodeInterface) {
      $type = $node->get('field_dsj_type_of_article')->referencedEntities();
      if (!empty($type)) {
        $variables['title'] = t('@type details', [
          '@type' => reset($type)->label(),
        ]);
      }
    }
  }
}

/**
 * Adds the form that saves the learning states.
 *
 * @param array $variables
 *   Current node preprocess variables.
 */
function _edsjp_add_learning_states_form(array &$variables) {
  if ((in_array($variables['node']->getType(), [
    'dsj_learning_content',
    'dsj_learning_path',
    'dsj_digital_skills_resource',
    'dsj_skills_intelligence',
    'dsj_training_offer',
  ])) && (in_array($variables['view_mode'], [
    'full',
    'listing',
    'listing_wide',
    'learning_experience',
  ]))) {
    $mode = in_array($variables['view_mode'], ['listing', 'listing_wide']) ? 'listing' : $variables['view_mode'];
    $variables['learning_states_form'] = \Drupal::formBuilder()
      ->getForm('\Drupal\dsjp_learning_states\Form\DsjpLearningStatesChangeStateForm', [
        'entity' => $variables['node'],
        'mode' => $mode,
      ]);
  }
}
