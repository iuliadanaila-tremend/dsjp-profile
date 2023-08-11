<?php

namespace Drupal\dsjp_coalition;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\State;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Provides a service for Slovenia NC integration.
 */
class SloveniaMigrationService implements MigrationServiceInterface {

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
   * The state service.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * DrupalMigrationService constructor.
   *
   * @param \GuzzleHttp\ClientInterface $client
   *   The queue factory.
   * @param \Drupal\Component\Serialization\Json $json
   *   The json serialization component.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   The logger channel.
   * @param \Drupal\Core\State\State $state
   *   The state service.
   */
  public function __construct(ClientInterface $client, Json $json, LoggerChannelFactoryInterface $loggerChannelFactory, State $state) {
    $this->httpClient = $client;
    $this->json = $json;
    $this->logger = $loggerChannelFactory;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseUrl(array $data) {
    $endpoint = $data['from_content_type'];
    switch ($data['from_content_type']) {
      case 'articles':
      case 'skills-intelligence-publications':
        $endpoint = 'contnews';
        break;

      case 'digital-skills-resources':
        $endpoint = 'materials';
        break;

      case 'funding-opportunities':
        $endpoint = 'financing';
        break;

      case 'initiatives':
        $endpoint = 'iniciative';
        break;

      case 'good-practices':
        $endpoint = 'good_practise';
        break;

      case 'training-offers':
        $endpoint = 'education';
        break;
    }
    return $data['api_base_url'] . '/' . $endpoint;
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalPages(array $data) {
    $totalPages = 0;
    $config = $this->state->get('group_nc_' . $data['group_id'], []);
    $token = $config['auth_header'] ?? '';
    $options['headers'] = [
      'Content-Type' => "application/json",
      'Auth-token' => $token,
    ];
    $options['query'] = [
      'publish_core' => 1,
      'sort_by' => 'updated_at',
      'sort_type' => 'DESC',
    ];
    // If there is a last imported date, filter by it.
    try {
      $response = $this->httpClient->request('GET', $this->getBaseUrl($data), $options);
      $response = $this->json->decode($response->getBody()->getContents());
      // The number of pages is the round of the results counter per number of
      // results per page.
      if (isset($response['total']) && !empty($response['total'])) {
        $totalPages = ceil($response['total'] / $response['per_page']);
      }
      // Make sure that there are items to imported based on the last import
      // date.
      if (!empty($response['data'])) {
        $item = $response['data'][0];
        if (strtotime($item['updated_at']) < strtotime($data['last_imported'])) {
          $totalPages = 0;
        }
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
  public function fetchData($page, array $data) {
    $url = $this->getBaseUrl($data);
    $config = $this->state->get('group_nc_' . $data['group_id'], []);
    $token = $config['auth_header'] ?? '';
    $options = [
      'headers' => [
        'Content-Type' => "application/json",
        'Auth-token' => $token,
      ],
    ];
    $options['query'] = [
      'page' => $page,
      'publish_core' => 1,
      'sort_by' => 'updated_at',
      'sort_type' => 'DESC',
    ];
    try {
      $return = [];
      $response = $this->httpClient->request('GET', $url, $options);
      $statusCode = $response->getStatusCode();

      if ($statusCode == 200) {
        $json = $this->json->decode($response->getBody()->getContents());
        $return = $json['data'];
        if ($data['from_content_type'] == 'articles') {
          $return = array_filter($return, function ($var) {
            return $var['content_type'] == 'news';
          });
        }
        elseif ($data['from_content_type'] == 'skills-intelligence-publications') {
          $return = array_filter($return, function ($var) {
            return $var['content_type'] == 'publications';
          });
        }
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
   * {@inheritdoc}
   */
  public function getBasePath($url) {
    // Because the parse_url function is forbidden, we have to split the url in
    // order to get the base path of the coalition website. Here we get the
    // url string from the beginning until the "jsonapi" part.
    preg_match('/(?<=^)(.*)(?=api\/v1\/content)/', $url, $matches);

    return $matches[0];
  }

}
