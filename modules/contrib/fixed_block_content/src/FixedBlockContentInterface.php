<?php

namespace Drupal\fixed_block_content;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Fixed block content interface.
 */
interface FixedBlockContentInterface extends ConfigEntityInterface {

  /**
   * Disabled state of the automatic block content export option.
   */
  const AUTO_EXPORT_DISABLED = 0;

  /**
   * State value for automatic default content export when block is empty.
   */
  const AUTO_EXPORT_ON_EMPTY = 1;

  /**
   * State value for unconditional automatic export of block content.
   */
  const AUTO_EXPORT_ALWAYS = 2;

  /**
   * Returns the block content entity linked to this fixed block.
   *
   * @param bool $create
   *   (optional) Creates a new block content and links it to this fixed block
   *   if there is no block content currently linked. Defaults to TRUE.
   *
   * @return null|\Drupal\block_content\BlockContentInterface
   *   The custom block linked to this fixed block content, NULL if not found
   *   and the create argument is FALSE.
   */
  public function getBlockContent($create = TRUE);

  /**
   * Returns the block content bundle.
   *
   * @return string
   *   The block content bundle.
   */
  public function getBlockContentBundle();

  /**
   * Export the default content stored in config to the content block.
   *
   * @param bool $update_existing
   *   (optional) Export the contents into the existing block content, if any.
   *
   *   The existing block content entity is deleted and replaced by a new one
   *   unless $update_existing is given. If there is no default content defined
   *   in the fixed block or it is not valid, a new empty block is created.
   */
  public function exportDefaultContent($update_existing = FALSE);

  /**
   * Import the current content block and set as the default content.
   */
  public function importDefaultContent();

  /**
   * Sets the fixed block protected option.
   *
   * When enabled, the linked custom block is set as non reusable.
   *
   * @param bool $value
   *   Boolean indicating to enable or disable the option.
   *
   * @return \Drupal\fixed_block_content\FixedBlockContentInterface
   *   The called fixed block entity object.
   */
  public function setProtected($value = TRUE);

  /**
   * Gets the protected option.
   *
   * @return bool
   *   Boolean indicating that the option is enabled or not.
   */
  public function isProtected();

  /**
   * Sets the automatic default content export state on configuration update.
   *
   * @param int $state
   *   (optional) The automatic export state.
   *
   *   Available auto-export states:
   *   - 0: Disabled.
   *   - 1: Only if the current content is empty. This is the default value.
   *   - 2: Unconditional automatic export of block content.
   *
   * @return \Drupal\fixed_block_content\FixedBlockContentInterface
   *   The called fixed block entity object.
   */
  public function setAutoExportState($state = FixedBlockContentInterface::AUTO_EXPORT_ON_EMPTY);

  /**
   * Gets the automatic default content export state.
   *
   * @return int
   *   The auto-export state. 0 mean disabled, 1 only if empty, 2 always.
   */
  public function getAutoExportState();

}
