<?php

namespace Drupal\dsjp_private_message\Plugin\Action;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\private_message\Entity\PrivateMessageThreadInterface;
use Drupal\user\UserInterface;
use Drupal\views_bulk_operations\Plugin\Action\EntityDeleteAction;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Delete pm entity action with default confirmation form.
 *
 * @Action(
 *   id = "private_message_delete_action",
 *   label = @Translation("Delete selected PM entities / translations"),
 *   type = "",
 *   confirm = TRUE,
 * )
 */
class PmDeleteAction extends EntityDeleteAction implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service property.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The account proxy service property.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * PmDeleteAction constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Session\AccountProxy $accountProxy
   *   The account proxy service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, AccountProxy $accountProxy) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $accountProxy;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if ($entity instanceof PrivateMessageThreadInterface) {
      $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
      if ($user instanceof UserInterface) {
        $entity->delete($user);

        return $this->t('Delete entities');
      }
    }

    return parent::execute($entity);
  }

}
