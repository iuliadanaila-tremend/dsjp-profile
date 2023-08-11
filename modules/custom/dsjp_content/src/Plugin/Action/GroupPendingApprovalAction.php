<?php

namespace Drupal\dsjp_content\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\dsjp_content\Services\ActionHelper;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Convert enquiry to booking.
 *
 * @Action(
 *   id = "dsj_move_to_pending_approval",
 *   label = @Translation("Move to Pending approval"),
 *   type = "group"
 * )
 */
final class GroupPendingApprovalAction extends ActionBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;
  /**
   * The destination state of the action.
   *
   * @var string
   */
  protected $newState = "pending_approval";

  /**
   * Current account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Class that contains methods common for our actions.
   *
   * @var \Drupal\dsjp_content\Services\ActionHelper
   */
  protected $actionHelper;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    AccountInterface $account,
    ActionHelper $actionHelper
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->account = $account;
    $this->actionHelper = $actionHelper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('dsjp_content.action_helper'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $message = $this->t('Published with bulk operations');
    $this->actionHelper->actionExecute($entity, $this->newState, NodeInterface::NOT_PUBLISHED, $message);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $this->actionHelper->actionAccess($object, $account, $return_as_object, $this->newState);
  }

}
