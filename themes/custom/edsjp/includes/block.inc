<?php

/**
 * @file
 * Theme and preprocess functions for blocks.
 */

use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\node\NodeTypeInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Implements hook_preprocess_HOOK().
 */
function edsjp_preprocess_pattern_page_header(&$variables) {
  $variables['show_breadcrumb'] = TRUE;
  $route_match = \Drupal::routeMatch();
  // If we are on a listing page, add a node creating link in header.
  $route_name = $route_match->getRouteName();
  if (_edsjp_page_is_listing($route_match)) {
    _edsjp_preprocess_page_header_listing_pages($variables, $route_match);
  }
  elseif ($route_name === 'entity.node.canonical') {
    _edsjp_preprocess_page_header_node_pages($variables, $route_match->getParameter('node'));
  }

  elseif ((strpos($route_name, 'entity.user') === 0 && $route_name != 'entity.user.collection') || in_array($route_name, [
    'message_subscribe_ui.tab',
    'entity.webform_submission.user',
    'view.groups.dsj_user_groups',
    'simplenews.newsletter_subscriptions_user',
    'view.dsj_assessments.page_1',
    'view.webform_submissions.user_webform_submissions',
    'view.pledges.user_pledges',
  ])) {
    _edsjp_preprocess_user_dashboard_page_header($variables, $route_match->getRawParameter('user'));
  }
  elseif (\Drupal::routeMatch()->getRouteName() === 'view.dsj_search.page_1') {
    $variables['add_node_link'] = _edsjp_create_propose_content_link();
  }
  elseif ($route_name == 'entity.private_message_thread.delete_form') {
    if (!empty($variables['language_switcher'])) {
      $variables['language_switcher'] = [];
    }
  }
  _edsjp_preprocess_private_message_header($variables);
  _edsjp_page_header_add_color_class($variables);
  _edsjp_page_header_add_color_class_by_route($variables);
  _edsjp_page_header_add_link($variables);
}

/**
 * Helper function for private message pages header.
 */
function _edsjp_preprocess_private_message_header(&$variables) {
  $route_match = \Drupal::routeMatch();
  $route_name = $route_match->getRouteName();
  if ($route_name == 'view.dsj_private_messages.private_messages_page') {
    $uid = $route_match->getRawParameter('user');
    $user = User::load($uid);
    $variables['breadcrumb'][1]['label']['#markup'] = \Drupal::currentUser()->id() == $user->id() ? t('My dashboard') : implode(' ', _edsjp_get_user_fullname($user));
    $argument = $route_match->getRawParameter('arg_1');
    if (!empty($argument) && $argument != 'last') {
      $breadcrumb = $variables['breadcrumb'];
      unset($breadcrumb[2]);
      $variables['breadcrumb'] = array_values($breadcrumb);
    }
  }
}

/**
 * Add the page header color class.
 *
 * @param array $variables
 *   The page header variables.
 */
function _edsjp_page_header_add_color_class(array &$variables) {
  $route_match = \Drupal::routeMatch();
  $extra_classes = [];
  if ($variables['ecl_branding'] == 'core') {
    $extra_classes[] = 'ecl-u-mb-xl';
  }
  if (!empty($variables['breadcrumb']) && isset($variables['breadcrumb'][1])) {
    if (isset($variables['breadcrumb'][1]['href'])) {
      $parameters = $variables['breadcrumb'][1]['href']->getRouteParameters();
    }
    else {
      $parameters = $route_match->getRawParameters()->all();
    }
    if (!empty($parameters['node'])) {
      $color_class = 'default';
      $node = Node::load($parameters['node']);
      if ($node->bundle() == 'dsj_landing_page' && $node->hasField('field_dsj_header_color')) {
        $color = $node->get('field_dsj_header_color')->getString();
        if (!empty($color)) {
          $color_class = $color;
        }
      }
      $extra_classes[] = 'color-' . $color_class;
    }
  }
  $variables['extra_classes'] = implode(' ', $extra_classes);
}

/**
 * Add the page header color class by route.
 *
 * @param array $variables
 *   The page header variables.
 */
function _edsjp_page_header_add_color_class_by_route(array &$variables) {
  $route_match = \Drupal::routeMatch();
  $extra_classes = $variables['extra_classes'] ? explode(' ', $variables['extra_classes']) : [];
  switch ($route_match->getRouteName()) {
    case 'view.pledges.page':
    case 'dsjp_pledge.initiative_update':
      $extra_classes[] = 'color-inspiration';
      break;

    case 'entity.group_content.create_form':
      $plugin_id = $route_match->getRawParameter('plugin_id');
      if ($plugin_id == 'group_node:dsj_pledge') {
        $extra_classes[] = 'color-inspiration';
      }
      break;

    case 'view.group_nodes.page_group_pledges':
    case 'view.dsj_pledge_organizations.page_pledger_organization':
      $extra_classes[] = 'color-community';
      break;
  }
  _edsjp_page_header_add_color_class_by_node_route($extra_classes);
  $variables['extra_classes'] = implode(' ', $extra_classes);
}

/**
 * Add the page header color class by node route.
 *
 * @param array $extra_classes
 *   Extra classes for the page header.
 */
function _edsjp_page_header_add_color_class_by_node_route(array &$extra_classes) {
  $node_types_pledge = ['dsj_pledge', 'dsj_pledge_initiative'];
  $route_match = \Drupal::routeMatch();
  switch ($route_match->getRouteName()) {
    case 'node.add':
      $node_type = $route_match->getRawParameter('node_type');
      if ($node_type == 'dsj_pledge_initiative') {
        $extra_classes[] = 'color-inspiration';
      }
      break;

    case 'entity.node.canonical':
    case 'entity.node.edit_form':
      /** @var \Drupal\node\Entity\Node $node */
      $node = $route_match->getParameter('node');
      if (in_array($node->bundle(), $node_types_pledge)) {
        $extra_classes[] = 'color-inspiration';
      }
      break;
  }
}

/**
 * Add to the page header link.
 *
 * @param array $variables
 *   The page header variables.
 */
function _edsjp_page_header_add_link(array &$variables) {
  $route_match = \Drupal::routeMatch();

  switch ($route_match->getRouteName()) {
    case 'view.pledges.page':
      $variables['add_node_link'] = _dsjp_pledge_get_add_pledge_link();
      break;

    case 'view.group_nodes.page_group_pledges':
      $variables['add_node_link'] = [
        '#type' => 'container',
        '#attributes' => ['class' => 'container-links'],
        'add_new_pledge' => _dsjp_pledge_get_add_pledge_link(),
        'add_exist_pledge' => _dsjp_community_add_exist_related_content_link([
          'administrator',
          'dsj_reviewer',
          'dsj_critical_reviewer',
          'community_manager',
        ]),
      ];
      break;
  }
}

/**
 * Preprocess the page header for listing pages.
 *
 * @param array $variables
 *   The preprocess variables.
 * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
 *   The route match object.
 */
function _edsjp_preprocess_page_header_listing_pages(array &$variables, RouteMatchInterface $route_match) {
  $object = $route_match->getRouteObject();
  $content_type_id = _edsjp_get_content_type_by_display($object->getDefault('display_id'));
  if (!empty($content_type_id)) {
    $content_type = NodeType::load($content_type_id);
    if ($content_type instanceof NodeTypeInterface) {
      $user = \Drupal::currentUser();
      if (!$user->hasPermission('create ' . $content_type_id . ' content')) {
        $link = _edsjp_create_propose_content_link();
      }
      else {
        $link = _edsjp_block_build_discussion_link($route_match, $content_type);
      }
      $variables['add_node_link'] = $link->toRenderable();
    }
  }
}

/**
 * Creates a new "Propose your content" link.
 *
 * @return \Drupal\Core\Link
 *   Returns the link created.
 */
function _edsjp_create_propose_content_link() {
  $text = t('Propose your content');

  return _edsjp_block_generate_link($text, 'entity.webform.canonical', ['webform' => 'content_proposal']);
}

/**
 * Adds the propose your content link on the page header.
 *
 * @param array $variables
 *   The preprocess variables.
 */
function _edsjp_add_propose_content_link(array &$variables) {
  if (\Drupal::routeMatch()->getRouteName() === 'view.dsj_search.page_1') {
    $text = t('Propose your content');
    $link = _edsjp_block_generate_link($text, 'entity.webform.canonical', ['webform' => 'content_proposal']);
    $variables['add_node_link'] = $link->toRenderable();
  }
}

/**
 * Preprocess the page header on user dashboard pages.
 *
 * @param array $variables
 *   The page header variables.
 * @param int $user
 *   The user id from url.
 */
function _edsjp_preprocess_user_dashboard_page_header(array &$variables, $user) {
  $user = User::load($user);
  if ($user instanceof UserInterface) {
    $variables['title'] = \Drupal::currentUser()->id() == $user->id() ? t('My dashboard') : implode(' ', _edsjp_get_user_fullname($user));
    $variables['breadcrumb'][1]['label']['#markup'] = $variables['title'];
    if (\Drupal::routeMatch()->getRouteName() == 'entity.user.edit_form' && isset($variables['breadcrumb'][2])) {
      unset($variables['breadcrumb'][2]);
    }
  }
}

/**
 * Helper function that builds the Add discussion link on listings.
 *
 * @param object $route_match
 *   The route object.
 * @param \Drupal\node\NodeTypeInterface $content_type
 *   The content type.
 *
 * @return \Drupal\Core\Link
 *   A built link object.
 */
function _edsjp_block_build_discussion_link($route_match, NodeTypeInterface $content_type) {
  $content_type_id = $content_type->id();
  if ('dsj_discussion' === $content_type_id) {
    $text = t('Start a @content_type', ['@content_type' => $content_type->label()]);
  }
  else {
    $text = t('Add your @content_type', ['@content_type' => $content_type->label()]);
  }
  // For group discussion list we need to change the link
  // so it creates the node inside the group.
  if ('view.dsj_discussions.dsj_group_discussions' === $route_match->getRouteName()) {
    $link = _edsjp_block_generate_link(
      $text,
      'entity.group_content.create_form',
      [
        'group' => $route_match->getParameter('group')->id(),
        'plugin_id' => 'group_node:dsj_discussion',
      ],
    );
  }
  else {
    $link = _edsjp_block_generate_link($text, 'node.add', ['node_type' => $content_type_id]);
  }

  return $link;
}

/**
 * Helper function that checks if a view page is a listing.
 *
 * The function returns TRUE if the current page is a view listing
 * and FALSE otherwise. This is used to add the "Add new content" link
 * in the header.
 *
 * @param object $route_match
 *   The route_match service.
 *
 * @return bool
 *   TRUE if the current page is a view listing, FALSE otherwise.
 */
function _edsjp_page_is_listing($route_match) {
  if (strpos($route_match->getRouteName(), 'view.dsj_content_listing') === 0
    || strpos($route_match->getRouteName(), 'entity.taxonomy_term.canonical') === 0) {
    return TRUE;
  }

  $discussionsRoutes = [
    'view.dsj_discussions.dsj_discussion_listing',
    'view.dsj_discussions.dsj_group_discussions',
  ];

  if (in_array($route_match->getRouteName(), $discussionsRoutes)) {
    return TRUE;
  }

  return FALSE;
}

/**
 * Preprocess the page header for node pages.
 *
 * @param array $variables
 *   The page header variables.
 * @param \Drupal\node\NodeInterface $entity
 *   The entity from route.
 */
function _edsjp_preprocess_page_header_node_pages(array &$variables, NodeInterface $entity) {
  $bundles = [
    'dsj_landing_page',
    'dsj_discussion',
  ];
  if ($entity instanceof NodeInterface && in_array($entity->bundle(), $bundles)) {
    if ($entity->hasField('field_dsj_content_proposal_link')) {
      $content_proposal = $entity->get('field_dsj_content_proposal_link')->getValue();
    }

    if (isset($content_proposal) && !empty($content_proposal) && $content_proposal[0]['value'] == 1) {
      $link = _edjsp_build_discussion_detail_header_link($entity);
      $variables['add_node_link'] = $link->toRenderable();
    }
  }
}

/**
 * Helper function that builds the Add content button in header.
 *
 * @param \Drupal\node\NodeInterface $entity
 *   The loaded node object.
 *
 * @return object
 *   The link object.
 */
function _edjsp_build_discussion_detail_header_link(NodeInterface $entity) {
  $user = \Drupal::currentUser();
  if (!$user->hasPermission('create ' . $entity->bundle() . ' content')) {
    $text = t('Propose your content');
    $link = _edsjp_block_generate_link($text, 'entity.webform.canonical', ['webform' => 'content_proposal']);
  }
  else {
    // Load the content type.
    $contentType = NodeType::load($entity->bundle());
    $text = t('Start a @contentType', ['@contentType' => mb_strtolower($contentType->label())]);
    if ('dsj_discussion' === $entity->bundle() && $groupId = _dsjp_community_node_is_group_content($entity->id())) {
      $link = _edsjp_block_generate_link($text, 'entity.group_content.create_form',
        [
          'group' => $groupId,
          'plugin_id' => 'group_node:dsj_discussion',
        ],
      );
    }
    else {
      $link = _edsjp_block_generate_link($text, 'node.add', ['node_type' => $entity->bundle()]);
    }
  }

  return $link;
}

/**
 * Generate a link from a given route and text.
 *
 * @param \Drupal\Core\StringTranslation\TranslatableMarkup $text
 *   The text of the link.
 * @param string $route_name
 *   The route name.
 * @param array $parameters
 *   Array of parameters for the route.
 *
 * @return \Drupal\Core\Link
 *   Returns the generated link.
 */
function _edsjp_block_generate_link(TranslatableMarkup $text, $route_name, array $parameters) {
  return Link::fromTextAndUrl(
    $text,
    Url::fromRoute($route_name, $parameters),
  );
}

/**
 * Gets the content type machine name based on the listing id.
 *
 * @param string $display
 *   The listing display id.
 *
 * @return string
 *   The content type machine name.
 */
function _edsjp_get_content_type_by_display($display) {
  $content_types = [
    'articles' => 'dsj_article',
    'funding_opportunities' => 'dsj_funding_opportunity',
    'events' => 'dsj_event',
    'dsj_discussion_listing' => 'dsj_discussion',
    'dsj_group_discussions' => 'dsj_discussion',
    'digital_skills_resources' => 'dsj_digital_skills_resource',
    'good_practices' => 'dsj_good_practice',
    'training_offers' => 'dsj_training_offer',
    'national_coalitions' => 'dsj_national_coalition',
    'organizations' => 'dsj_organization',
    'publications' => 'dsj_skills_intelligence',
    'strategies' => 'dsj_strategy',
    'initiatives' => 'dsj_initiative',
  ];

  return !empty($content_types[$display]) ? $content_types[$display] : '';
}

/**
 * Implements hook_preprocess_HOOK().
 */
function edsjp_preprocess_block(&$variables) {
  if ($variables['plugin_id'] === 'system_main_block' && \Drupal::routeMatch()->getRouteName() === 'system.403') {
    $current_request = \Drupal::requestStack()->getCurrentRequest();
    $query = [
      'returnto' => $current_request->getRequestUri(),
    ];
    $text = t('Please <a href=":login_url">login</a> to the platform to access this content.<br>
    Don\'t have an account? Sign up now to join our growing community of digital skills stakeholders from all across Europe.<br>
    You can create an account quickly and easily using your EU Login credentials, just <a href=":register_url">click here</a> to get started.', [
      ':login_url' => Url::fromRoute('user.login', [], [
        'query' => $query,
        'absolute' => TRUE,
      ])->toString(),
      ':register_url' => Url::fromRoute('user.register', [], [
        'query' => $query,
        'absolute' => TRUE,
      ])->toString(),
    ]);

    $node = $current_request->get('node');
    if ($node instanceof NodeInterface && $node->hasField('moderation_state')) {
      $state = $node->get('moderation_state')->getString();
      if ($state === 'archived') {
        $text = t('@page_name: Page not available.<br>
        This page is not available any more on the Platform. The information may have been removed or archived since no longer up to date.<br>
        We invite you to explore other sections of the Platform (<a href=":initiative_url">initiatives</a>, <a href=":training_url">training</a>, <a href=":funding_url">funding</a>, <a href=":news_url">news</a>, etc.) and to search for other related content using the Keyword search tool.', [
          '@page_name' => $node->label(),
          ':initiative_url' => Url::fromUserInput('/initiatives')->toString(),
          ':training_url' => Url::fromUserInput('/training-offers')->toString(),
          ':funding_url' => Url::fromUserInput('/funding-opportunities')->toString(),
          ':news_url' => Url::fromUserInput('/latest/news')->toString(),
        ]);
      }
    }
    $variables['content']['#markup'] = $text;
  }
  elseif (strpos($variables['plugin_id'], 'block_private_messages') != FALSE) {
    if (count($variables['content']['#view']->result) == 0) {
      $variables['attributes']['class'][] = 'is-empty';
    }
    $variables['show_vbo_checkbox'] = FALSE;
    if (count($variables['content']['#view']->result) > 0) {
      $variables['show_vbo_checkbox'] = TRUE;
    }
  }
}
