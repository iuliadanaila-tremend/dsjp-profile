<?php

declare(strict_types = 1);

namespace Drupal\language_selection_page_test\Plugin\LanguageNegotiation;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Symfony\Component\HttpFoundation\Request;

/**
 * Test language negotiator.
 *
 * @LanguageNegotiation(
 *     id=\Drupal\language_selection_page_test\Plugin\LanguageNegotiation\TestLanguageNegotiator::METHOD_ID,
 *     types={
 *         \Drupal\Core\Language\LanguageInterface::TYPE_INTERFACE,
 *         \Drupal\Core\Language\LanguageInterface::TYPE_CONTENT,
 *         \Drupal\Core\Language\LanguageInterface::TYPE_URL
 *     },
 *     weight=-20,
 *     name=@Translation("Test Language Negotiator"),
 *     description=@Translation("Test Language Negotiator"),
 * )
 */
class TestLanguageNegotiator extends LanguageNegotiationUrl {

  /**
   * The language negotiation method id.
   */
  public const METHOD_ID = 'language-selection-page-test-negotiator';

  /**
   * {@inheritdoc}
   */
  public function getLangcode(?Request $request = NULL) {
    $request_path = urldecode(trim($request->getPathInfo(), '/'));

    if (strpos($request_path, '-fake') !== FALSE) {
      return 'fake';
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    return str_replace('-fake', '', $path);
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], ?Request $request = NULL, ?BubbleableMetadata $bubbleable_metadata = NULL) {
    return $path . '-fake';
  }

}
