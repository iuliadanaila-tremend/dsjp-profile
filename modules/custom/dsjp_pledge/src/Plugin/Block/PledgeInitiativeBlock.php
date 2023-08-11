<?php

namespace Drupal\dsjp_pledge\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;
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
   * The current user service property.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The group service property.
   *
   * @var \Drupal\dsjp_pledge\GroupServiceInterface
   */
  protected $groupService;

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
    $instance->currentUser = $container->get('current_user');
    $instance->groupService = $container->get('dsjp_pledge.group_service');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $pledge = $this->routeMatch->getParameter('node');
    if ($pledge instanceof NodeInterface) {
      $account = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
      $membership = $this->groupService->userHasOrganizationGroupRole($account, [
        'dsj_organization-coordinator',
        'dsj_organization-admin',
      ]);
      $currentState = $pledge->get('moderation_state')->value;
      if ($this->checkUserAccess($currentState, $membership, $account, 'block')) {
        $urlAccess = $this->checkUserAccess($currentState, $membership, $account, 'url', $pledge->getOwnerId());
        $build = [
          '#theme' => 'pledge_page_initiatives',
          '#url_access' => $urlAccess,
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
            if ($this->checkInitiativeAccess($account, $initiative)) {
              $type = $initiative->get('field_dsj_initiative_type')->value;
              if (!empty($type)) {
                $build["#$type"]['initiatives'][] = $this->entityTypeManager->getViewBuilder('node')->view($initiative, 'related');
              }
            }
          }
        }
      }
    }

    return $build;
  }

  /**
   * Checks if a user has access to view an initiative.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user object.
   * @param \Drupal\node\NodeInterface $initiative
   *   The node object.
   *
   * @return bool
   *   Returns TRUE if user has access, FALSE otherwise.
   */
  public function checkInitiativeAccess(UserInterface $account, NodeInterface $initiative) {
    $access = FALSE;
    if ($account->id() == $initiative->getOwnerId()) {
      $access = TRUE;
    }
    elseif ($initiative->access('update', $account)) {
      $access = TRUE;
    }
    elseif ($account->hasRole('administrator') || $account->hasRole('dsj_reviewer')) {
      $access = TRUE;
    }
    elseif ($initiative->isPublished()) {
      // If the initiative is published it should be displayed for anyone.
      $access = TRUE;
    }

    return $access;
  }

  /**
   * Checks if user has access on the block & urls visible.
   *
   * @param string $currentState
   *   The current pledge moderation state.
   * @param bool $membership
   *   Boolean indicating if user is a member of an organization.
   * @param \Drupal\user\UserInterface $account
   *   The user object.
   * @param string $type
   *   The type of check to make.
   * @param string $ownerId
   *   The pledge owner id.
   *
   * @return bool
   *   Returns TRUE if user has access, FALSE otherwise.
   */
  protected function checkUserAccess($currentState, $membership, UserInterface $account, $type, $ownerId = '') {
    $access = FALSE;
    if ($type == 'block') {
      if ($currentState == 'published') {
        $access = TRUE;
      }
      elseif ($membership || (in_array('dsj_reviewer', $account->getRoles()))) {
        $access = TRUE;
      }
    }
    elseif ($type == 'url') {
      $access = $ownerId == $account->id() || $membership || (in_array('dsj_reviewer', $account->getRoles()));
    }

    return $access;
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
