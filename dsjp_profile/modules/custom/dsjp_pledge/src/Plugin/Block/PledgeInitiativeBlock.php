<?php

namespace Drupal\dsjp_pledge\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a custom block for pledge's page initiatives.
 *
 * @Block(
 *   id = "pledge_initiative_block",
 *   admin_label = @Translation("Pledge Initiatives"),
 * )
 */
class PledgeInitiativeBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
    $build = [];
    $pledge = $this->routeMatch->getParameter('node');
    if ($pledge instanceof NodeInterface) {
      $build = [
        '#theme' => 'pledge_page_initiatives',
        '#education' => [
          'initiatives' => [],
          'url' => Url::fromRoute('node.add', ['node_type' => 'dsj_pledge_initiative'], [
            'query' => [
              'type' => 'education',
              'pledge_id' => $pledge->id(),
            ],
          ])->toString(),
        ],
        '#labour_force' => [
          'initiatives' => [],
          'url' => Url::fromRoute('node.add', ['node_type' => 'dsj_pledge_initiative'], [
            'query' => [
              'type' => 'labour_force',
              'pledge_id' => $pledge->id(),
            ],
          ])->toString(),
        ],
        '#ict_professionals' => [
          'initiatives' => [],
          'url' => Url::fromRoute('node.add', ['node_type' => 'dsj_pledge_initiative'], [
            'query' => [
              'type' => 'ict_professionals',
              'pledge_id' => $pledge->id(),
            ],
          ])->toString(),
        ],
        '#other' => [
          'initiatives' => [],
          'url' => Url::fromRoute('node.add', ['node_type' => 'dsj_pledge_initiative'], [
            'query' => [
              'type' => 'other',
              'pledge_id' => $pledge->id(),
            ],
          ])->toString(),
        ],
      ];
      $initiatives = $this->entityTypeManager->getStorage('node')->loadByProperties([
        'type' => 'dsj_pledge_initiative',
        'field_dsj_pledge' => $pledge->id(),
      ]);
      if (!empty($initiatives)) {
        foreach ($initiatives as $initiative) {
          $type = $initiative->get('field_dsj_initiative_type')->value;
          if (!empty($type)) {
            $build["#$type"]['initiatives'][] = $this->entityTypeManager->getViewBuilder('node')->view($initiative, 'related');
          }
        }
      }
    }

    return $build;
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
    $pledge = $this->routeMatch->getRawParameter('node');
    return Cache::mergeTags(parent::getCacheTags(), ['node:' . $pledge]);
  }

}
