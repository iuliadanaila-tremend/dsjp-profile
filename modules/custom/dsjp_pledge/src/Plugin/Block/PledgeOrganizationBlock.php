<?php

namespace Drupal\dsjp_pledge\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the pledge page organization block.
 *
 * @Block(
 *   id = "pledge_organization_block",
 *   admin_label = @Translation("Pledge Organization"),
 * )
 */
class PledgeOrganizationBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current request service property.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * The entity type manager service property.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    $instance->routeMatch = $container->get('current_route_match');
    $instance->entityTypeManager = $container->get('entity_type.manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->routeMatch->getParameter('node');
    if ($node && $node->bundle() == 'dsj_pledge') {
      $group_content = $this->entityTypeManager->getStorage('group_content')->loadByEntity($node);
      if (!empty($group_content)) {
        $group_content = reset($group_content);
        return $this->entityTypeManager->getViewBuilder('group')->view($group_content->getGroup(), 'dsj_block');
      }
    }
    $organisation = $this->routeMatch->getParameter('group');
    if ($organisation && $organisation->bundle() == 'dsj_organization') {
      return $this->entityTypeManager->getViewBuilder('group')->view($organisation, 'dsj_block');
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['url']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $organization = $this->routeMatch->getRawParameter('node');
    return Cache::mergeTags(parent::getCacheTags(), ['node:' . $organization]);
  }

}
