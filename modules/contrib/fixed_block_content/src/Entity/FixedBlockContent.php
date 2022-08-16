<?php

namespace Drupal\fixed_block_content\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\fixed_block_content\FixedBlockContentInterface;
use Drupal\block_content\BlockContentInterface;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Configuration entity for the fixed block content.
 *
 * @ConfigEntityType(
 *   id = "fixed_block_content",
 *   label = @Translation("Fixed block content"),
 *   config_prefix = "fixed_block_content",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title"
 *   },
 *   handlers = {
 *     "access" = "Drupal\fixed_block_content\FixedBlockContentAccessControlHandler",
 *     "list_builder" = "Drupal\fixed_block_content\FixedBlockContentListBuilder",
 *     "content_handler" = "Drupal\fixed_block_content\DefaultContentHandler",
 *     "mapping_handler" = "Drupal\fixed_block_content\FixedToContentMappingHandler",
 *     "form" = {
 *       "add" = "Drupal\fixed_block_content\Form\FixedBlockContentForm",
 *       "edit" = "Drupal\fixed_block_content\Form\FixedBlockContentForm",
 *       "delete" = "Drupal\fixed_block_content\Form\FixedBlockContentDeleteForm",
 *       "export" = "Drupal\fixed_block_content\Form\ExportConfirmForm",
 *       "import" = "Drupal\fixed_block_content\Form\ImportConfirmForm"
 *     }
 *   },
 *   links = {
 *     "collection" = "/admin/structure/block/block-content/fixed-block-content",
 *     "canonical" = "/admin/structure/block/block-content/fixed-block-content/manage/{fixed_block_content}",
 *     "edit-form" = "/admin/structure/block/block-content/fixed-block-content/manage/{fixed_block_content}/edit",
 *     "delete-form" = "/admin/structure/block/block-content/fixed-block-content/manage/{fixed_block_content}/delete",
 *     "export-form" = "/admin/structure/block/block-content/fixed-block-content/manage/{fixed_block_content}/export",
 *     "import-form" = "/admin/structure/block/block-content/fixed-block-content/manage/{fixed_block_content}/import"
 *   },
 *   config_export = {
 *     "id",
 *     "title",
 *     "block_content_bundle",
 *     "default_content",
 *     "auto_export",
 *     "protected"
 *   }
 * )
 */
class FixedBlockContent extends ConfigEntityBase implements FixedBlockContentInterface {

  /**
   * The block ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The block title.
   *
   * @var string
   */
  protected $title;

  /**
   * The block content bundle.
   *
   * @var string
   */
  protected $block_content_bundle;

  /**
   * The serialized default content for this fixed block.
   *
   * @var string
   */
  protected $default_content;

  /**
   * Option to automatic export of default content on config update.
   *
   * @var int
   */
  protected $auto_export;

  /**
   * Option to set the custom block as non reusable.
   *
   * @var bool
   */
  protected $protected;

  /**
   * The current block content linked to this fixed block.
   *
   * @var \Drupal\block_content\BlockContentInterface
   *
   * @deprecated in fixed_block_content:8.x-1.0 and is removed from
   *   fixed_block_content:8.x-2.0. Use the mapping handler instead to get
   *   a cached version of the linked block content.
   *
   * @see https://www.drupal.org/project/fixed_block_content/issues/3070773
   */
  protected $blockContent;

  /**
   * {@inheritdoc}
   */
  public function getBlockContent($create = TRUE) {
    $block_content = $this->getMappingHandler()->getBlockContent($this->id());
    if (!$block_content && $create) {
      $this->exportDefaultContent();
      $block_content = $this->getMappingHandler()->getBlockContent($this->id());
    }

    return $this->blockContent = $block_content;
  }

  /**
   * Links a block content with this fixed block.
   *
   * Any existing block content is not deleted, unless on protected fixed
   * blocks. If the given block is new, it will be saved to reference it.
   *
   * @param \Drupal\block_content\BlockContentInterface $block_content
   *   (optional) The new block content to link to this fixed block. New
   *    empty block is created if none given.
   *
   * @throws \InvalidArgumentException
   *   When the content type of the given block mismatches the configured type.
   */
  protected function setBlockContent(BlockContentInterface $block_content = NULL) {
    // Create a new empty block content if no one given.
    if (!$block_content) {
      $block_content = $this->newBlockContent();
    }

    // Link this fixed block with the content block.
    $this->getMappingHandler()->setBlockContent($this, $block_content);
    $this->blockContent = $block_content;
  }

  /**
   * Creates a new block content.
   *
   * @return \Drupal\block_content\BlockContentInterface
   *   The new block content.
   */
  protected function newBlockContent() {
    /** @var \Drupal\block_content\BlockContentInterface $block_content */
    $block_content = $this->getBlockContentStorage()->create([
      'type' => $this->block_content_bundle,
      'info' => $this->title,
      'langcode' => $this->languageManager()->getDefaultLanguage()->getId(),
    ]);
    $block_content->enforceIsNew(TRUE);
    $block_content->setNewRevision(FALSE);

    // Set the reusable flag in the new custom block according to the non
    // reusable option in the fixed block content.
    $block_content->set('reusable', !$this->isProtected());

    return $block_content;
  }

  /**
   * {@inheritdoc}
   */
  public function getBlockContentBundle() {
    return $this->block_content_bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function exportDefaultContent($update_existing = FALSE) {
    $new_block_content = $this->getDefaultContentHandler()->exportDefaultContent($this)
      ?: $this->newBlockContent();

    if ($current_block = $this->getMappingHandler()->getBlockContent($this->id())) {
      if ($update_existing) {
        // Copy entity identifiers from the existing block into the new one.
        $new_block_content->set('id', $current_block->id());
        $new_block_content->set('uuid', $current_block->uuid());
        // Set the new block as not new entity.
        $new_block_content->enforceIsNew(FALSE);
        // Do not create new revision.
        $new_block_content->setNewRevision(FALSE);
        // Save the block, will update the existing.
        $new_block_content->save();
      }
      else {
        // Delete the current block content.
        $current_block->delete();
      }
    }

    $this->setBlockContent($new_block_content);
  }

  /**
   * {@inheritdoc}
   */
  public function importDefaultContent() {
    $this->getDefaultContentHandler()->importDefaultContent($this);
  }

  /**
   * {@inheritdoc}
   */
  public function setAutoExportState($state = FixedBlockContentInterface::AUTO_EXPORT_ON_EMPTY) {
    $this->auto_export = $state;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAutoExportState() {
    return $this->auto_export ?: FixedBlockContentInterface::AUTO_EXPORT_DISABLED;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    // Add dependency on the linked block content.
    if ($block_content = $this->getBlockContent(FALSE)) {
      $this->addDependency($block_content->getConfigDependencyKey(), $block_content->getConfigDependencyName());
    }

    // Add dependency on the configured block content type.
    $block_content_type = $this->entityTypeManager()->getStorage('block_content_type')->load($this->block_content_bundle);
    $this->addDependency($block_content_type->getConfigDependencyKey(), $block_content_type->getConfigDependencyName());

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);
    static::invalidateBlockPluginCache();

    /** @var \Drupal\fixed_block_content\FixedToContentMappingHandler $mapping_handler */
    $mapping_handler = \Drupal::entityTypeManager()
      ->getHandler('fixed_block_content', 'mapping_handler');
    foreach ($entities as $entity) {
      // Release the fixed block content.
      $mapping_handler->releaseBlockContent($entity);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    static::invalidateBlockPluginCache();
  }

  /**
   * Invalidates the block plugin cache after changes and deletions.
   */
  protected static function invalidateBlockPluginCache() {
    // Invalidate the block cache to update custom block-based derivatives.
    \Drupal::service('plugin.manager.block')->clearCachedDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function setProtected($value = TRUE) {
    $this->protected = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function isProtected() {
    return (bool) $this->protected;
  }

  /**
   * Gets the default content handler.
   *
   * @return \Drupal\fixed_block_content\DefaultContentHandlerInterface
   *   The default content handler.
   */
  protected function getDefaultContentHandler() {
    return $this->entityTypeManager()
      ->getHandler($this->getEntityTypeId(), 'content_handler');
  }

  /**
   * Gets the mapping handler.
   *
   * @return \Drupal\fixed_block_content\FixedToContentMappingHandlerInterface
   *   The fixed block content mapping handler.
   */
  protected function getMappingHandler() {
    return $this->entityTypeManager()
      ->getHandler($this->getEntityTypeId(), 'mapping_handler');
  }

  /**
   * Gets the block content entity type storage.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The block content entity storage.
   */
  protected function getBlockContentStorage() {
    return $this->entityTypeManager()
      ->getStorage('block_content');
  }

}
