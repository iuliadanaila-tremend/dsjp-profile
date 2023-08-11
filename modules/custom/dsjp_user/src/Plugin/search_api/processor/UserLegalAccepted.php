<?php

namespace Drupal\dsjp_user\Plugin\search_api\processor;

use Drupal\search_api\IndexInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Excludes users who did not accept the legal terms & conditions.
 *
 * @SearchApiProcessor(
 *   id = "user_legal_accepted",
 *   label = @Translation("User legal status"),
 *   description = @Translation("Exclude users who didn't accept the legal terms & conditons."),
 *   stages = {
 *     "alter_items" = 0,
 *   },
 * )
 */
class UserLegalAccepted extends ProcessorPluginBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $processor */
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $processor->entityTypeManager = $container->get('entity_type.manager');

    return $processor;
  }

  /**
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index) {
    foreach ($index->getDatasources() as $datasource) {
      $entity_type_id = $datasource->getEntityTypeId();
      if (!$entity_type_id) {
        continue;
      }
      // This processor should work only for users.
      if ($entity_type_id === 'user') {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterIndexedItems(array &$items) {
    /** @var \Drupal\search_api\Item\ItemInterface $item */
    foreach ($items as $item_id => $item) {
      $object = $item->getOriginalObject()->getValue();
      $indexed = TRUE;
      if ($object instanceof UserInterface) {
        // Load the latest version of the legal conditions.
        $conditions = _dsjp_user_get_legal_conditions();
        // Check if there is LegalAccepted entity having the current user id and
        // the latest version.
        $legal = $this->entityTypeManager->getStorage('legal_accepted')->loadByProperties([
          'uid' => $object->id(),
          'version' => $conditions['version'],
        ]);
        if (empty($legal)) {
          $indexed = FALSE;
        }
      }
      if (!$indexed) {
        unset($items[$item_id]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function requiresReindexing(array $old_settings = NULL, array $new_settings = NULL) {
    return TRUE;
  }

}
