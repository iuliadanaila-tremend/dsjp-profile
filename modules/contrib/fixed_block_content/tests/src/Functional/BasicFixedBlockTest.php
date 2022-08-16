<?php

namespace Drupal\Tests\fixed_block_content\Functional;

/**
 * Tests the basic fixed block content functionality.
 *
 * @group fixed_block_content
 */
class BasicFixedBlockTest extends FunctionalFixedBlockTestBase {

  /**
   * Tests that the content block is created on view.
   */
  public function testContentBlockCreationOnView() {
    // Gets the home page.
    $this->drupalGet('<front>');

    // Check that the custom block was created.
    $content_blocks = \Drupal::entityTypeManager()
      ->getStorage('block_content')
      ->loadByProperties(['info' => $this->fixedBlock->label()]);
    $this->assertTrue(!empty($content_blocks), 'Automatic block content creation failed.');
  }

  /**
   * Tests the default content export.
   */
  public function testDefaultContent() {
    // Random content.
    $random_content = $this->randomString(128);

    // Gets the block content. An empty one will be created.
    $block_content = $this->fixedBlock->getBlockContent();

    // Sets the block content in the body field.
    $block_content->get('body')->setValue($random_content);

    // Import and save current block contents as the default content.
    $this->fixedBlock->importDefaultContent();
    $this->fixedBlock->save();

    // Delete the block content.
    $block_content->delete();

    // Gets the home page.
    $this->drupalGet('<front>');

    // Default content is expected.
    $this->assertSession()->pageTextContains($random_content);
  }

}
