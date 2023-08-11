<?php

namespace Drupal\dsjp_content\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Cache\UncacheableDependencyTrait;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\facets\FacetManager\DefaultFacetManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Facets Block' block.
 *
 * @Block(
 *  id = "dsjp_facets_summary_block",
 *  admin_label = @Translation("Aggregated Facet Summary Block"),
 * )
 */
class FacetsSummaryBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
  private $summaryFacetId;

  /**
   * Current Route name.
   *
   * @var string
   */
  private $currentRouteName;

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
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DefaultFacetManager $facets_manager, ModuleHandlerInterface $module_handler, BlockManagerInterface $plugin_manager_block, AccountProxyInterface $current_user, CurrentRouteMatch $currentRouteMatch) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->facetsManager = $facets_manager;
    $this->moduleHandler = $module_handler;
    $this->pluginManagerBlock = $plugin_manager_block;
    $this->currentUser = $current_user;
    $this->currentRouteMatch = $currentRouteMatch;

    $this->currentRouteName = $currentRouteMatch->getRouteName();
    // @todo Find better solution to get summary facet id.
    if (mb_substr($this->currentRouteName, 0, 5) === 'view.') {
      $split = explode('.', $this->currentRouteName);
      $this->summaryFacetId = implode("_", [
        $split[1],
        $split[2],
        "summary",
      ]);
    }
    elseif ($this->currentRouteName == 'entity.taxonomy_term.canonical') {
      $this->summaryFacetId = 'dsj_articles_listing_articles_summary';
    }
    elseif ($this->currentRouteName == 'entity.node.canonical') {
      $this->summaryFacetId = 'dsj_listing_component_summary';
    }
    elseif ($this->currentRouteName == 'dsj_map.country_listing') {
      $this->summaryFacetId = 'dsj_regionally_related_content_page_1_summary';
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
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if (!empty($this->summaryFacetId)) {
      // Construct Plugin id based on facet summary ID.
      $plugin_id = 'facets_summary_block:' . $this->summaryFacetId;

      $block_plugin = $this->pluginManagerBlock->createInstance($plugin_id, []);
      if ($block_plugin && $block_plugin->access($this->currentUser)) {
        $build = $block_plugin->build();
        if (!empty($build['#items'])) {
          return $build;
        }
      }
    }

    return [];
  }

}
