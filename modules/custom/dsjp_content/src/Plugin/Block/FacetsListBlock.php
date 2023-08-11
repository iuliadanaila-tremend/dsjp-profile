<?php

namespace Drupal\dsjp_content\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Block\Plugin\Block\Broken;
use Drupal\Core\Cache\UncacheableDependencyTrait;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\facets\FacetManager\DefaultFacetManager;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Facets Block' block.
 *
 * @Block(
 *  id = "dsjp_facets_block",
 *  admin_label = @Translation("Facets list Block"),
 * )
 */
class FacetsListBlock extends BlockBase implements ContainerFactoryPluginInterface {

  use UncacheableDependencyTrait;

  /**
   * The Default Facet Manager.
   *
   * @var \Drupal\facets\FacetManager\DefaultFacetManager
   */
  protected $facetsManager;

  /**
   * The Module Handler Interface.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The Block Manager Interface.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $pluginManagerBlock;

  /**
   * The Account Proxy Interface.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Current Route.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  private $currentRouteMatch;

  /**
   * Facet Source ID.
   *
   * @var string
   */
  private $facetSourceId;

  /**
   * Exposed Form block ID.
   *
   * @var string
   */
  private $exposedFormBid;

  /**
   * Current Route name.
   *
   * @var string
   */
  private $currentRouteName;

  /**
   * Form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  private $formBuilder;

  /**
   * Current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  private $currentPath;

  /**
   * Path manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  private $pathManager;

  /**
   * FacetsBlock constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\facets\FacetManager\DefaultFacetManager $facets_manager
   *   The Facets manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Block\BlockManagerInterface $plugin_manager_block
   *   The Plugin manager block.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   Current user.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   *   Current route.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   Form builder.
   * @param \Drupal\Core\Path\CurrentPathStack $currentPath
   *   Current path.
   * @param \Drupal\path_alias\AliasManagerInterface $pathManager
   *   Path manager.
   *
   * @SuppressWarnings(PHPMD.ExcessiveParameterList)
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DefaultFacetManager $facets_manager, ModuleHandlerInterface $module_handler, BlockManagerInterface $plugin_manager_block, AccountProxyInterface $current_user, CurrentRouteMatch $currentRouteMatch, FormBuilderInterface $formBuilder, CurrentPathStack $currentPath, AliasManagerInterface $pathManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->facetsManager = $facets_manager;
    $this->moduleHandler = $module_handler;
    $this->pluginManagerBlock = $plugin_manager_block;
    $this->currentUser = $current_user;
    $this->currentRouteMatch = $currentRouteMatch;
    $this->formBuilder = $formBuilder;
    $this->currentPath = $currentPath;
    $this->pathManager = $pathManager;

    // @todo Find better solution to get facet source id.
    $this->currentRouteName = $currentRouteMatch->getRouteName();
    if (mb_substr($this->currentRouteName, 0, 5) === 'view.') {
      $split = explode('.', $this->currentRouteName,);
      $this->facetSourceId = implode("__", [
        "search_api:views_page",
        $split[1],
        $split[2],
      ]);

      $this->exposedFormBid = "views_exposed_filter_block:" . $split[1] . "-" . $split[2];
    }
    elseif ($this->currentRouteName == 'entity.taxonomy_term.canonical') {
      $this->facetSourceId = 'search_api:views_page__dsj_articles_listing__articles';
    }
    elseif ($this->currentRouteName == 'entity.node.canonical') {
      $this->facetSourceId = 'search_api:views_block__dsj_search__listing_block';
    }
    elseif ($this->currentRouteName == 'dsj_map.country_listing') {
      $this->facetSourceId = 'search_api:views_page__dsj_regionally_related_content__page_1';
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('facets.manager'),
      $container->get('module_handler'),
      $container->get('plugin.manager.block'),
      $container->get('current_user'),
      $container->get('current_route_match'),
      $container->get('form_builder'),
      $container->get('path.current'),
      $container->get('path_alias.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['block_settings'] = [
      '#type' => 'fieldset',
      '#title' => 'Settings',
    ];

    $form['block_settings']['show_title'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Facet titles'),
      '#default_value' => $this->configuration['show_title'],
    ];

    $form['block_settings']['exclude_empty_facets'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude empty facets'),
      '#default_value' => $this->configuration['exclude_empty_facets'],
    ];

    $form['block_settings']['include_search'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include search form.'),
      '#description' => $this->t('If the current search view has exposed filter enabled, this checkbox will display it on top of filters. This feature is not available on search page.'),
      '#default_value' => $this->configuration['include_search'],
    ];

    $form['block_settings']['exclude_fields'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Exclude fields'),
      '#description' => $this->t("Comma separated list of fields exclude(if present in facets lists for current view)."),
      '#default_value' => $this->configuration['exclude_fields'] ?? TRUE,
      '#maxlength' => 400,
      '#size' => 400,
    ];

    $form['block_settings']['select_fields'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Select fields to display'),
      '#description' => $this->t("Comma separated list of fields to filter by(if present in facets lists for current view)."),
      '#default_value' => $this->configuration['select_fields'] ?? TRUE,
      '#maxlength' => 400,
      '#size' => 400,
    ];

    $form['block_settings']['titles'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Filters titles'),
      '#description' => $this->t("Titles of filters block by view."),
      '#cols' => 5,
      '#default_value' => $this->configuration['titles'] ?? '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['show_title'] = $form_state->getValue([
      'block_settings',
      'show_title',
    ]);
    $this->configuration['exclude_empty_facets'] = $form_state->getValue([
      'block_settings',
      'exclude_empty_facets',
    ]);
    $this->configuration['include_search'] = $form_state->getValue([
      'block_settings',
      'include_search',
    ]);
    $this->configuration['exclude_fields'] = $form_state->getValue([
      'block_settings',
      'exclude_fields',
    ]);
    $this->configuration['select_fields'] = $form_state->getValue([
      'block_settings',
      'select_fields',
    ]);
    $this->configuration['titles'] = $form_state->getValue([
      'block_settings',
      'titles',
    ]);
  }

  /**
   * Get facets based on source id.
   *
   * @param string $facetSourceId
   *   Id of facet source.
   * @param string $exclude_fields
   *   Comma separated fields to exclude.
   * @param string $select_fields
   *   Comma separated fields to include.
   * @param string $label
   *   The facets block label.
   *
   * @return array
   *   List of active facets
   */
  private function getAvailableFacets($facetSourceId, $exclude_fields, $select_fields, $label) {
    $available_facets = $this->facetsManager->getFacetsByFacetSourceId($facetSourceId);

    if (!empty($available_facets)) {
      usort($available_facets, function ($a, $b) {
        $a_weight = $a->getWeight();
        $b_weight = $b->getWeight();

        if ($a_weight == $b_weight) {
          return 0;
        }

        return ($a_weight < $b_weight) ? -1 : 1;
      });
      if (!empty($exclude_fields)) {
        $available_facets = $this->processFacets($available_facets, $exclude_fields, $facetSourceId, $label, TRUE);
      }
      elseif (!empty($select_fields)) {
        $available_facets = $this->processFacets($available_facets, $select_fields, $facetSourceId, $label);
      }
    }

    return $available_facets;
  }

  /**
   * Process facets according to flags.
   *
   * @param array $available_facets
   *   The current facets.
   * @param string $fields
   *   The list of fields.
   * @param string $facetSourceId
   *   Id of facet source.
   * @param string $label
   *   Block label.
   * @param bool $exclude
   *   Exclude flag.
   *
   * @return array
   *   List of active facets
   */
  private function processFacets(array $available_facets, string $fields, string $facetSourceId, string $label, bool $exclude = FALSE) {
    $fields_arr = array_map('trim', explode(',', $fields));

    // For this particular view we have a different behaviour.
    if ($facetSourceId == 'search_api:views_page__dsj_regionally_related_content__page_1') {
      if ($exclude || $label != 'Filter your results') {
        if (($key = array_search("field_dsj_type_of_initiative", $fields_arr)) !== FALSE) {
          unset($fields_arr[$key]);
        }
      }
    }

    if ($exclude) {
      $available_facets = array_filter($available_facets, function ($var) use ($fields_arr) {
        return !in_array($var->getFieldIdentifier(), $fields_arr);
      });
    }
    else {
      $available_facets = array_filter($available_facets, function ($var) use ($fields_arr) {
        return in_array($var->getFieldIdentifier(), $fields_arr);
      });
    }

    return $available_facets;
  }

  /**
   * Check if facet block is empty or not.
   *
   * @param array $build
   *   Block array.
   *
   * @return bool
   *   Empty or not boolean.
   */
  private function isFacetBlockEmpty(array $build) {
    $is_empty = FALSE;
    if (!$build) {
      $is_empty = TRUE;
    }
    elseif (isset($build[0]['#attributes']['class']) && in_array('facet-empty',
        $build[0]['#attributes']['class'])) {
      $is_empty = TRUE;
    }
    elseif (isset($build['#items']) && count($build['#items']) == 0) {
      $is_empty = TRUE;
    }

    return $is_empty;
  }

  /**
   * Builds facets.
   *
   * @param string $available_facets
   *   A list of facets to display.
   * @param bool $exclude_empty_facets
   *   Exclude empty facets from rendering.
   * @param string $bid
   *   Exposed search block id.
   * @param bool $include_search
   *   Include or not search box.
   *
   * @return array
   *   An array of facets.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function buildFacets($available_facets, $exclude_empty_facets, $bid, $include_search) {
    $facets = [];

    if ($include_search) {
      if (empty($bid)) {
        $form = $this->formBuilder->getForm('Drupal\dsjp_content\Form\Keywords');
        $facets[] = [
          'content' => $form,
        ];
      }
      else {
        $block_plugin = $this->pluginManagerBlock->createInstance($bid, []);
        if (!$block_plugin instanceof Broken) {
          $facets[] = [
            'content' => $block_plugin->build(),
          ];
        }
      }
    }

    /** @var \Drupal\facets\Entity\Facet $facet */
    foreach ($available_facets as $facet) {
      $facet_title = $facet->getName();
      $plugin_id = 'facet_block:' . $facet->id();

      $block_plugin = $this->pluginManagerBlock->createInstance($plugin_id, []);
      if ($block_plugin && $block_plugin->access($this->currentUser)) {
        $build = $block_plugin->build();

        if ($exclude_empty_facets) {
          if ($this->isFacetBlockEmpty($build)) {
            continue;
          }
        }

        $facets[] = [
          'title' => $facet_title,
          'content' => $build,
        ];
      }
    }

    return $facets;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $show_title = $this->configuration['show_title'] ?? TRUE;
    $excludeFields = $this->configuration['exclude_fields'] ?? "";
    $selectFields = $this->configuration['select_fields'] ?? "";
    $exclude_empty_facets = $this->configuration['exclude_empty_facets'] ?? TRUE;
    $include_search = $this->configuration['include_search'] ? TRUE : FALSE;
    $titles = $this->configuration['titles'] ?? "";
    $current_title = $this->getLabel($titles);
    $label = $this->configuration['label'];

    if (!empty($this->facetSourceId)) {
      $available_facets = $this->getAvailableFacets($this->facetSourceId, $excludeFields, $selectFields, $label);
      $exposedFormBid = $this->exposedFormBid;
      if ($this->currentRouteName == "view.dsj_search.page_1") {
        $include_search = FALSE;
      }

      if (!empty($available_facets)) {
        $facets = $this->buildFacets($available_facets, $exclude_empty_facets, $exposedFormBid, $include_search);
        if (!empty($facets)) {
          $build = [
            '#theme' => 'facets_list_block',
            '#show_title' => $show_title,
            '#facets' => $facets,
            '#attached' => [
              'library' => ['dsjp_content/facet-select2'],
            ],
          ];

          if ($current_title) {
            $build['#title'] = $current_title;
          }

          return $build;
        }
      }
    }

    return [];
  }

  /**
   * Get the block title by route.
   *
   * @param string $titles
   *   The block titles.
   *
   * @return false|string
   *   Return value.
   */
  public function getLabel(string $titles) {
    $titles = explode(PHP_EOL, $titles);
    $current_path = $this->currentPath->getPath();
    $result = mb_substr($this->pathManager->getAliasByPath($current_path), 1);

    foreach ($titles as $title) {
      if (str_contains($title, $result)) {
        return mb_substr($title, strpos($title, "|") + 1);
      }
      elseif (str_contains($result, 'european-interactive-map') && str_contains($title, 'european-interactive-map')) {
        return mb_substr($title, strpos($title, "|") + 1);
      }
    }

    return FALSE;
  }

}
