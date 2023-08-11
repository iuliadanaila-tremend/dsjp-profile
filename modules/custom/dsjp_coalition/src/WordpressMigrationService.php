<?php

namespace Drupal\dsjp_coalition;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Provides the Wordpress migration service.
 */
class WordpressMigrationService implements MigrationServiceInterface {

  use StringTranslationTrait;

  /**
   * The http client service.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The json serialization service.
   *
   * @var \Drupal\Component\Serialization\Json
   */
  protected $json;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * WordpressMigrationService constructor.
   *
   * @param \GuzzleHttp\ClientInterface $client
   *   The queue factory.
   * @param \Drupal\Component\Serialization\Json $json
   *   The json serialization component.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   The logger channel.
   */
  public function __construct(ClientInterface $client, Json $json, LoggerChannelFactoryInterface $loggerChannelFactory) {
    $this->httpClient = $client;
    $this->json = $json;
    $this->logger = $loggerChannelFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseUrl(array $data) {
    return $data['api_base_url'] . '/' . $data['from_content_type'];
  }

  /**
   * {@inheritdoc}
   */
  public function getBasePath($url) {
    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalPages(array $data) {
    $totalPages = 0;
    $options['headers'] = [
      'Content-Type' => "application/json",
    ];
    $options['query'] = [
      'orderby' => 'modified',
      'order' => 'desc',
      'per_page' => 100,
    ];
    if (!empty($data['last_imported'])) {
      $options['query']['modified_after'] = $data['last_imported'];
    }
    $options['query']['filter'] = [
      'meta_key' => 'publish_in_core_platform',
      'meta_value' => 1,
    ];
    try {
      $response = $this->httpClient->request('HEAD', $this->getBaseUrl($data), $options);
      $totalPages = $response->getHeader('X-WP-TotalPages')[0];
    }
    catch (GuzzleException $e) {
      $this->logger->get('dsjp_coalition')->error($e->getMessage());
    }
    if ($totalPages < 1) {
      $this->logger->get('dsjp_coalition')->warning($this->t('No pages found for url: @url', [
        '@url' => $this->getBaseUrl($data),
      ]));
    }

    return $totalPages;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchData($page, $data) {
    $url = $this->getBaseUrl($data);

    $options = [
      'headers' => ['Content-Type' => "application/json"],
    ];

    $options['query'] = [
      'page' => $page,
      'orderby' => 'modified',
      'order' => 'asc',
      'per_page' => 100,
    ];
    // Filter by modified date if this request was made before.
    if (!empty($data['last_imported'])) {
      $options['query']['modified_after'] = $data['last_imported'];
    }
    $options['query']['filter'] = [
      'meta_key' => 'publish_in_core_platform',
      'meta_value' => 1,
    ];
    try {
      $return = [];
      $response = $this->httpClient->request('GET', $url, $options);
      $statusCode = $response->getStatusCode();

      // @todo check other status codes.
      if ($statusCode == 200) {
        $return = $this->json->decode($response->getBody()->getContents());
      }
      else {
        $this->logger->get('dsjp_coalition')->error($this->t('@endpoint returned a @code status code.', [
          '@endpoint' => $url,
          '@code' => $statusCode,
        ]));
      }
      return $return;
    }
    catch (GuzzleException $e) {
      $this->logger->get('dsjp_coalition')->error($e->getMessage());
      return [];
    }
  }

}
