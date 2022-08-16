<?php

namespace Drupal\dsjp_content\Plugin\PageHeaderMetadata;

use Drupal\oe_theme_helper\Plugin\PageHeaderMetadata\NodeViewRoutesBase;

/**
 * Page header metadata for the content entities.
 *
 * @PageHeaderMetadata(
 *   id = "node_title_metadata",
 *   label = @Translation("Metadata extractor for the content types"),
 *   weight = -2
 * )
 */
class NodeTitleMetadata extends NodeViewRoutesBase {

  const TYPE_TO_TITLE = [
    'dsj_article' => 'Articles',
    'dsj_digital_skills_resource' => 'Resources',
    'dsj_event' => 'Events',
    'dsj_funding_opportunity' => 'Funding',
    'dsj_good_practice' => 'Good Practices',
    'dsj_initiative' => 'European Initiatives',
    'dsj_national_coalition' => 'National Coalitions',
    'dsj_organization' => 'Organisations',
    'dsj_page' => 'Basic Pages',
    'dsj_skills_intelligence' => 'Research',
    'dsj_strategy' => 'National Strategies',
    'dsj_training_offer' => 'Training',
  ];

  /**
   * {@inheritdoc}
   */
  public function applies(): bool {
    $node = $this->getNode();

    if (!$node) {
      return FALSE;
    }

    return isset(self::TYPE_TO_TITLE[$node->bundle()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(): array {
    $metadata = parent::getMetadata();

    $node = $this->getNode();
    $bundle = $node->bundle();

    if (isset(self::TYPE_TO_TITLE[$bundle])) {
      if (!isset($metadata['metas'])) {
        $metadata['metas'] = [];
      }

      $metadata['metas']['custom_title'] = self::TYPE_TO_TITLE[$bundle];
    }

    return $metadata;
  }

}
