<?php

namespace Drupal\dsjp_private_message\Plugin\Block;

use Drupal\block_content\BlockContentInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the private message page text block.
 *
 * @Block(
 *   id = "private_messages_page_block",
 *   admin_label = @Translation("Private message page text block"),
 * )
 */
class PrivateMessagesPageBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager property.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * PRivateMEssagesPageBlock constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager interface.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $block = $this->entityTypeManager->getStorage('block_content')->load(21);
    if ($block instanceof BlockContentInterface) {
      $build = $this->entityTypeManager->getViewBuilder('block_content')->view($block);
    }

    return $build;
  }

}
