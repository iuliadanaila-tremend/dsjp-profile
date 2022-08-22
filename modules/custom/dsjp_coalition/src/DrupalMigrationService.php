<?php

namespace Drupal\dsjp_coalition;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Provides the Drupal migration service.
 */
class DrupalMigrationService implements MigrationServiceInterface {

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
   * DrupalMigrationService constructor.
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
  public function getBaseUrl($data) {
    return $data['api_base_url'] . '/' . $data['to_content_type'];
  }

  /**
   * {@inheritdoc}
   */
  public function getBasePath($url) {
    // Because the parse_url function is forbidden, we have to split the url in
    // order to get the base path of the coalition website. Here we get the
    // url string from the beginning until the "jsonapi" part.
    preg_match('/(?<=^)(.*)(?=jsonapi)/', $url, $matches);

    return $matches[0];
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalPages($data) {
    $totalPages = 0;
    $options['headers'] = [
      'Content-Type' => "application/json",
    ];
    $options['query'] = [];
    // If there is a last imported date, filter by it.
    if (!empty($data['last_imported'])) {
      $options['query']['filter'] = [
        'field_dsj_publish_in_core' => 1,
        'date_filter' => [
          'condition' => [
            'path' => 'changed',
            'operator' => '>',
            'value' => $data['last_imported'],
          ],
        ],
      ];
    }
    $options['query'] += $this->getQueryParameters($data['to_content_type']);
    try {
      $response = $this->httpClient->request('GET', $this->getBaseUrl($data), $options);
      $response = $this->json->decode($response->getBody()->getContents());
      // The number of pages is the round of the results counter per number of
      // results per page.
      if (!empty($response['meta']['count'])) {
        $totalPages = ceil($response['meta']['count'] / 50);
      }
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
      'page' => [
        'offset' => ($page - 1) * 50,
        'limit' => 50,
      ],
      'filter' => [
        'field_dsj_publish_in_core' => 1,
        'date_filter' => [
          'condition' => [
            'path' => 'changed',
            'operator' => '>',
            'value' => $data['last_imported'],
          ],
        ],
      ],
      'sort' => '-changed',
    ];
    $options['query'] += $this->getQueryParameters($data['to_content_type']);
    try {
      $return = [];
      $response = $this->httpClient->request('GET', $url, $options);
      $statusCode = $response->getStatusCode();

      // @todo check other status codes.
      if ($statusCode == 200) {
        $json = $this->json->decode($response->getBody()->getContents());
        foreach ($json['data'] as &$jsonData) {
          $jsonData['id'] = $jsonData['drupal_internal__nid'];
        }
        $return = $json['data'];
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

  /**
   * Get the endpoint query parameters for fields relationships.
   *
   * @param string $contentType
   *   The content type to get the parameters for.
   *
   * @return array
   *   The query parameters array.
   */
  protected function getQueryParameters($contentType) {
    // Because Drupal's jsonapi endpoint is using relationship endpoints for the
    // files related, we have to include them into the parameters in order to
    // have the file url available in the main array.
    $includes = [
      'dsj_article' => [
        'field_dsj_image',
        'field_dsj_geographic_scope',
      ],
      'dsj_digital_skills_resource' => [
        'field_dsj_image',
        'field_dsj_main_file_for_download',
        'field_dsj_supporting_document',
        'field_dsj_geographic_scope',
        'field_dsj_organization',
      ],
      'dsj_event' => [
        'field_dsj_image',
        'field_dsj_geographic_scope',
        'field_dsj_organization',
      ],
      'dsj_funding_opportunity' => [
        'field_dsj_image',
        'field_dsj_geographic_scope',
        'field_dsj_form_of_funding',
      ],
      'dsj_good_practice' => [
        'field_dsj_image',
        'field_dsj_media_image',
        'field_dsj_media_audio',
        'field_dsj_media_video',
        'field_dsj_media_document',
        'field_dsj_geographic_scope',
        'field_dsj_organization',
      ],
      'dsj_initiative' => [
        'field_dsj_image',
        'field_dsj_geographic_scope',
      ],
      'dsj_organization' => [
        'field_dsj_image',
        'field_dsj_geographic_scope',
      ],
      'dsj_page' => [
        'field_dsj_image',
      ],
      'dsj_skills_intelligence' => [
        'field_dsj_image',
        'field_dsj_geographic_scope',
      ],
      'dsj_strategy' => [
        'field_dsj_image',
        'field_dsj_file_for_download',
        'field_dsj_geographic_scope',
        'field_dsj_organization',
      ],
      'dsj_training_offer' => [
        'field_dsj_image',
        'field_dsj_geographic_scope',
        'field_dsj_organization',
      ],
    ];

    return [
      'include' => implode(',', $includes[$contentType]),
      'jsonapi_include' => 1,
    ];
  }

}
