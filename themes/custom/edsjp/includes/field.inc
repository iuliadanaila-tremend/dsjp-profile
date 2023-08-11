<?php

/**
 * @file
 * Theme and preprocess functions for nodes.
 */

use Drupal\Core\Link;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;

/**
 * Implements hook_preprocess_field().
 *
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 */
function edsjp_preprocess_field(&$variables) {

  if ($variables['field_name'] === 'body' && isset($variables['attributes']['class'])) {
    $pos = array_search('ecl-editor', $variables['attributes']['class']);
    if ($pos !== FALSE) {
      unset($variables['attributes']['class'][$pos]);
    }
  }

  // For full view mode we build the format in twig.
  // For the other view modes we need to print only the start date.
  if ('field_dsj_date' === $variables['element']['#field_name']) {
    if (isset($variables['element']['#bundle']) &&
      'dsj_event' === $variables['element']['#bundle']) {
      _edsjp_handle_event_date($variables);
    }
    _edsjp_handle_pledge_date($variables);
  }
  elseif ('field_dsj_country' === $variables['element']['#field_name']) {
    // For user listing we need to add the flag classes.
    _edsjp_add_flag_variable($variables);
    _edsjp_add_view_more($variables);
  }
  elseif ('field_dsj_geographic_scope' === $variables['element']['#field_name']) {
    if ($variables['element']['#view_mode'] == 'full') {
      _edsjp_add_child_terms($variables);
      _edsjp_add_view_more_link($variables);
    }
  }
  elseif ('field_dsj_image' === $variables['element']['#field_name']) {
    _edsjp_add_dsj_image_style_class($variables);
  }
  elseif ('field_dsj_digital_technology' === $variables['element']['#field_name']) {
    _edsjp_preproess_field_digital_technology($variables);
  }
  elseif ('field_dsj_digital_skill_level' === $variables['element']['#field_name']) {
    _edsjp_preproess_field_digital_skill_level($variables);
  }
  elseif (in_array($variables['element']['#field_name'], [
    'field_dsj_introductory_learning',
    'field_dsj_advanced_learning',
  ])) {
    $variables['#show_more_label'] = t('See more materials');
    $variables['#show_less_label'] = t('See less materials');
    _edsjp_add_view_more_link($variables, 3);
  }

  _edsjp_add_field_tooltip($variables);
  _edsjp_add_field_link($variables);
  if ($variables['element']['#field_name'] == 'field_dsj_comments') {
    if ($variables['element']['#bundle'] == 'dsj_discussion' && $variables['element']['#view_mode'] == 'listing') {
      $url = Url::fromRoute('entity.node.canonical', ['node' => $variables['element']['#object']->id()]);
      $variables['view_all_link'] = Link::fromTextAndUrl(t('View all comments'), $url)->toRenderable();
      unset($variables['comment_form']);
    }
  }
}

/**
 * Adds view more functionality on organization countries list.
 *
 * @param array $variables
 *   The field preprocess variables.
 */
function _edsjp_add_view_more(array &$variables) {
  if (isset($variables['element']['#bundle']) && $variables['element']['#bundle'] == 'dsj_organization') {
    _edsjp_add_view_more_link($variables);
  }
}

/**
 * Process function for pledge date field.
 */
function _edsjp_handle_pledge_date(&$variables) {
  if (isset($variables['element']['#bundle']) &&
    'dsj_pledge' === $variables['element']['#bundle']) {
    $variables['items'][0]['content']['start_date']['#prefix'] = '<div class="datetime-label">' . t('Start date') . '</div>';
    $variables['items'][0]['content']['end_date']['#prefix'] = '<div class="datetime-label">' . t('End date') . '</div>';
  }
}

/**
 * Adds link for the vocabulary terms display on the listing pages.
 *
 * @param array $variables
 *   The field preprocess variables.
 */
function _edsjp_add_field_link(array &$variables) {
  $fields = [
    'field_dsj_typology_of_training',
    'field_dsj_training_duration',
    'field_dsj_assessment_type',
    'field_dsj_form_of_funding',
  ];
  if (in_array($variables['element']['#field_name'], $fields) && strpos(\Drupal::routeMatch()->getRouteName(), 'view.dsj_content_listing') == 0) {
    foreach ($variables['items'] as &$item) {
      if ($variables['element']['#field_name'] == 'field_dsj_form_of_funding' && $item['content']['#theme'] == 'item_list') {
        foreach ($item['content']['#items'] as $key => &$subitem) {
          $subitem['#title'] = $subitem['#plain_text'];
          unset($subitem['#plain_text']);
          $subitem['#type'] = 'link';
          $subitem['#url'] = Url::fromRoute('view.dsj_search.page_1', [], [
            'query' => [
              'fulltext' => $subitem['#title'],
            ],
          ]);
          $item['content']['#items'][$key] = $subitem;
        }
      }
      else {
        if (isset($item['content']['#markup'])) {
          $item['content']['#title'] = $item['content']['#markup'];
        }
        else {
          $item['content']['#title'] = $item['content']['#plain_text'];
          // If the plain_text key is set, that value
          // will be rendered instead of the link.
          unset($item['content']['#plain_text']);
        }
        $item['content']['#type'] = 'link';
        $item['content']['#url'] = Url::fromRoute('view.dsj_search.page_1', [], [
          'query' => [
            'fulltext' => $item['content']['#title'],
          ],
        ]);
      }
    }
  }
}

/**
 * Preprocess function for the digital technology field.
 *
 * @param array $variables
 *   The pre-process variables.
 */
function _edsjp_preproess_field_digital_technology(array &$variables) {
  _edsjp_add_digital_technology_url($variables);
  if (in_array($variables['element']['#bundle'], [
    'dsj_discussion',
    'dsj_article',
    'dsj_good_practice',
    'dsj_initiative',
    'dsj_skills_intelligence',
    'dsj_strategy',
    'dsj_digital_skills_resource',
    'dsj_event',
    'dsj_funding_opportunity',
    'dsj_training_offer',
    'dsj_national_coalition',
    'dsj_pledge',
    'dsj_learning_content',
    'dsj_learning_path',
  ])
    && in_array($variables['element']['#view_mode'], [
      'listing',
      'listing_wide',
      'learning_material',
      'learning_experience',
    ])) {
    _edsjp_add_view_more_link($variables);
  }
}

/**
 * Preprocess function for the digital skill level field.
 *
 * @param array $variables
 *   The pre-process variables.
 */
function _edsjp_preproess_field_digital_skill_level(array &$variables) {
  _edsjp_add_digital_skill_level_url($variables);
}

/**
 * Replace Digital technology field with link to search.
 *
 * @param array $variables
 *   Field variables.
 */
function _edsjp_add_digital_technology_url(array &$variables) {
  $values = $variables['element']['#items']->getValue();

  foreach ($variables['items'] as $delta => &$item) {
    $item['content']['#title'] = $item['content']['#markup'];

    $item['content']['#type'] = 'link';
    $item['content']['#url'] = Url::fromRoute('view.dsj_search.page_1', [], [
      'query' => [
        'f[0]' => 'digital_technology:' . $values[$delta]['value'],
      ],
    ],
    );
  }
}

/**
 * Replace Digital skill level field with link to search.
 *
 * @param array $variables
 *   Field variables.
 */
function _edsjp_add_digital_skill_level_url(array &$variables) {
  $values = $variables['element']['#items']->getValue();

  foreach ($variables['items'] as $delta => &$item) {
    $item['content']['#title'] = $item['content']['#markup'];

    $item['content']['#type'] = 'link';
    $item['content']['#url'] = Url::fromRoute('view.dsj_search.page_1', [], [
      'query' => [
        'f[0]' => 'digital_skill_level:' . $values[$delta]['value'],
      ],
    ],
    );
  }
}

/**
 * Adds tooltip attribute to a field.
 *
 * @param array $variables
 *   Hook preprocess field variables.
 */
function _edsjp_add_field_tooltip(array &$variables) {
  $tooltip = [];
  if (!empty($variables['items']) &&
    $variables['entity_type'] === 'node' &&
    in_array($variables['element']['#object']->getType(), [
      'dsj_event',
      'dsj_good_practice',
      'dsj_digital_skills_resource',
      'dsj_skills_intelligence',
    ])) {
    if (in_array($variables['element']['#field_name'], [
      'field_dsj_event_setting',
      'field_dsj_digital_technology',
    ])) {
      foreach ($variables['items'] as $data) {
        $tooltip[] = $data['content']['#markup'];
      }
    }
    elseif (in_array($variables['element']['#field_name'], [
      'field_dsj_skills_resource_type',
      'field_dsj_type_of_initiative',
      'field_dsj_publication_type',
    ])) {
      $tooltip[] = $variables['element']['#title'];
    }
    elseif ($variables['element']['#field_name'] === 'field_dsj_event_location') {
      $tooltip = $variables['items'][0]['content']['#address_items'];
    }
    if (!empty($tooltip)) {
      $variables['attributes']['title'] = implode(', ', $tooltip);
    }
  }
}

/**
 * Helper function that add the country code as a variable.
 *
 * The country code is taken from the SKOS field key.
 *
 * @param array $variables
 *   The variables array from the preprocess function.
 */
function _edsjp_add_flag_variable(array &$variables) {
  $referencedItem = $variables['element']['#items']->referencedEntities();
  if (isset($referencedItem[0]) && !empty($referencedItem[0])) {
    $skosCode = $referencedItem[0]->id();
    if ($skosCode) {
      // Split the code by slashes and get the country code.
      $code = explode('/', $skosCode);
      if (!empty(end($code))) {
        $variables['countryCode'] = mb_strtolower(end($code));
      }
    }
  }

}

/**
 * Helper function to replace country field output with child terms.
 *
 * @param array $variables
 *   The variables array from the preprocess function.
 *
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 */
function _edsjp_add_child_terms(array &$variables) {
  $referencedItems = $variables['element']['#items']->referencedEntities();
  // Get all childrens and display them instead of parent.
  $termStorage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');

  $newItems = [];
  if (!empty($referencedItems)) {
    foreach ($referencedItems as $id => $referencedItem) {
      $childrens = $termStorage->loadChildren($referencedItem->id());
      if (!empty($childrens)) {
        $tmp = $variables['items'][$id];
        unset($variables['items'][$id]);

        foreach ($childrens as $children) {
          $newItem = $tmp;
          $newItem['content']['#plain_text'] = $children->label();
          $newItem['cache']['tags'][] = 'taxonomy_term:' . $children->id();

          $newItems[] = $newItem;
        }
      }
      else {
        $parents = $termStorage->loadParents($referencedItem->id());
        foreach ($parents as $parent) {
          $newItem = $variables['items'][$id];
          $newItem['#cache']['tags'][] = 'taxonomy_term:' . $parent->id();
          $newItems[] = $newItem;
        }
      }
    }
  }

  $variables['items'] = $newItems;
}

/**
 * Helper function to display view more link on countries list.
 *
 * @param array $variables
 *   The variables array from the preprocess function.
 * @param int $items_show_limit
 *   The number of displayed items.
 */
function _edsjp_add_view_more_link(array &$variables, int $items_show_limit = 4) {
  $element = $variables['element'];
  $variables['attributes']['class'] = 'field-load-more';
  $field_wrapper_class = 'field--name-' . str_replace('_', '-', $element['#field_name']);
  $variables['#attached']['drupalSettings']['field_load_more'][$field_wrapper_class] = [
    'limit' => $items_show_limit,
  ];
  // Add view more link.
  foreach ($variables['items'] as $key => $item) {
    if ($key > $items_show_limit - 1) {
      $attributes = [
        'class' => ['hidden'],
      ];

      $variables['items'][$key]['attributes'] = new Attribute($attributes);
    }
  }

  if (count($variables['items']) > $items_show_limit) {
    $variables['items'][]['content'] = [
      '#prefix' => '<a href="#" class="load-more-btn button" data-field-class="' . $field_wrapper_class . '"',
      '#suffix' => '</a>',
      '#plain_text' => $variables['#show_more_label'] ?? t('Show more'),
      '#cache' => [
        'tags' => [
          $items_show_limit . '-' . $element['#field_name'],
        ],
      ],
    ];
  }
  // Add the view less button for discussion listing tags.
  if (in_array($variables['element']['#bundle'], [
    'dsj_discussion',
    'dsj_article',
    'dsj_organization',
    'dsj_good_practice',
    'dsj_initiative',
    'dsj_skills_intelligence',
    'dsj_strategy',
    'dsj_digital_skills_resource',
    'dsj_event',
    'dsj_funding_opportunity',
    'dsj_training_offer',
    'dsj_national_coalition',
    'dsj_pledge',
    'dsj_learning_path',
    'dsj_learning_content',
  ])) {
    $variables['items'][]['content'] = [
      '#prefix' => '<a href="#" class="view-less-btn button hidden" data-field-class="' . $field_wrapper_class . '"',
      '#suffix' => '</a>',
      '#plain_text' => $variables['#show_less_label'] ?? t('Show less'),
      '#cache' => [
        'tags' => [
          $items_show_limit . '-' . $element['#field_name'],
        ],
      ],
    ];
  }
}

/**
 * Add image style class to field images.
 *
 * @param array $variables
 *   Preprocess variables.
 */
function _edsjp_add_dsj_image_style_class(array &$variables) {
  foreach ($variables['items'] as &$item) {
    if (isset($item['content']['#image_style'])) {
      $item['attributes']->addClass($item['content']['#image_style']);
      $variables['attributes'] = new Attribute(['class' => [$item['content']['#image_style']]]);
      break;
    }
  }

}

/**
 * Helper function to handle event date.
 *
 * @param array $variables
 *   Field Preprocess variables.
 */
function _edsjp_handle_event_date(array &$variables) {
  // Get start and end date.
  if ($variables['element']['#view_mode'] != 'full') {
    if (isset($variables['items'][0]['content']['end_date']['#text']) &&
      !empty($variables['items'][0]['content']['end_date']['#text'])) {
      $variables['items'][0]['content']['end_date']['#text'] = '';
      $variables['items'][0]['content']['separator']['#plain_text'] = '';
    }
  }
}
