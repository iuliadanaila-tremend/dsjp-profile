<?php

namespace Drupal\dsjp_coalition;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\State;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Provides the austrian migration service.
 */
class AustriaMigrationService implements MigrationServiceInterface {

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
   * The state service property.
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
    return $data['api_base_url'] . '/' . $this->getCtEndpoint($data['from_content_type']);
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalPages(array $data) {
    $options['headers'] = [
      'Content-Type' => "application/json",
    ];
    $config = $this->state->get('group_nc_' . $data['group_id'], []);
    if (!isset($config['access_token'])) {
      $token = $this->getAccessToken($data);
    }
    else {
      $token = $config['access_token'];
    }

    if (!empty($token)) {
      $options['query']['access_token'] = $token;
      $date = !empty($data['last_imported']) ? $data['last_imported'] : 1;
      $url = $this->getBaseUrl($data) . '/' . $date;
      try {
        $request = $this->requestEndpoint('GET', $url, $options);
        if ($request->getStatusCode() == 200) {
          $content = $request->getBody()->getContents();
          $content = $this->json->decode($content);
          if (!empty($content['result'])) {
            return 1;
          }

          return 0;
        }
      }
      catch (GuzzleException $e) {
        $this->logger->get('dsjp_coalition')->error($e->getMessage());
        $code = $e->getResponse()->getStatusCode();
        // Token expired.
        if ($code == 403) {
          $options['query']['access_token'] = $this->getAccessToken($data);
          $request = $this->requestEndpoint('GET', $url, $options);
          if ($request->getStatusCode() != 200) {
            $this->logger->get('dsjp_coalition')->error($this->t('Token not valid for @url', [
              '@url' => $url,
            ]));

            return 0;
          }
        }
      }
    }
    else {
      $this->logger->get('dsjp_coalition')->warning($this->t('Unable to retrieve the token.', [
        '@url' => $this->getBaseUrl($data),
      ]));
      return 0;
    }

    return 0;
  }

  /**
   * Makes a new request.
   *
   * @param string $method
   *   The request method.
   * @param string $url
   *   The url to make the request on.
   * @param array $options
   *   The request options.
   */
  protected function requestEndpoint($method, $url, array $options) {
    return $this->httpClient->request($method, $url, $options);
  }

  /**
   * Gets the access token.
   *
   * @param array $data
   *   The service config data.
   *
   * @return false|mixed
   *   Returns the token on success, FALSE otherwise.
   */
  protected function getAccessToken(array $data) {
    $config = $this->state->get('group_nc_' . $data['group_id'], []);

    if (!empty($config)) {
      $this->requestToken($config, $data['api_base_url'] . '/oauth');
      if (isset($config['access_token'])) {
        $this->state->set('group_nc_' . $data['group_id'], $config);
        return $config['access_token'];
      }
    }

    return FALSE;
  }

  /**
   * Requests a new access token.
   *
   * @param array $config
   *   The group config.
   * @param string $url
   *   The url to make the request on.
   */
  protected function requestToken(array &$config, $url) {
    try {
      $request = $this->requestEndpoint('POST', $url, [
        'headers' => [
          'Content-Type' => 'application/x-www-form-urlencoded',
        ],
        'form_params' => [
          'grant_type' => 'password',
          'client_id' => $config['client_id'] ?? '',
          'client_secret' => $config['client_secret'] ?? '',
          'username' => $config['username'] ?? '',
          'password' => $config['password'] ?? '',
          'scope' => 'api:external',
        ],
      ]);
      if ($request->getStatusCode() == 200) {
        $contents = $request->getBody()->getContents();
        $contents = $this->json->decode($contents);
        if (isset($contents['access_token'])) {
          $config['access_token'] = $contents['access_token'];
        }
      }
    }
    catch (GuzzleException $e) {
      $this->logger->get('dsjp_coalition')->error($e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fetchData($page, array $data) {
    $options['headers'] = [
      'Content-Type' => "application/json",
    ];
    $config = $this->state->get('group_nc_' . $data['group_id'], []);
    $options['query']['access_token'] = $config['access_token'];
    $date = !empty($data['last_imported']) ? $data['last_imported'] : 1;
    $url = $this->getBaseUrl($data) . '/' . $date;
    try {
      $request = $this->requestEndpoint('GET', $url, $options);
      $contents = $request->getBody()->getContents();
      $contents = $this->json->decode($contents);
      foreach ($contents['result'] as $key => $data) {
        if (!empty($data['website'])) {
          foreach ($data['website'] as $wKey => $website) {
            $contents['result'][$key]['website'][$wKey]['uri'] = $website['websiteUrl'];
            unset($contents['result'][$key]['website'][$wKey]['websiteUrl']);
            $contents['result'][$key]['website'][$wKey]['title'] = $website['websiteText'];
            unset($contents['result'][$key]['website'][$wKey]['websiteText']);
            $contents['result'][$key]['website'][$wKey]['link_type'] = $website['websiteType'];
            unset($contents['result'][$key]['website'][$wKey]['websiteType']);
          }
        }
      }
      return $contents['result'];
    }
    catch (GuzzleException $e) {
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getBasePath($url) {
    return '';
  }

  /**
   * Get the proper endpoint based on the content type.
   *
   * @param string $contentType
   *   The given content type.
   *
   * @return string
   *   The endpoint.
   */
  protected function getCtEndpoint($contentType) {
    $data = [
      'organizations' => 'content-organizations',
      'articles' => 'content-articles',
      'digital-skills-resources' => 'content-training-resources',
      'events' => 'content-events',
      'funding-opportunities' => 'content-funding-opportunities',
      'good-practices' => 'content-good-practices',
      'initiatives' => 'content-eu-initiatives',
      'skills-intelligence-publications' => 'content-skills-intelligences',
      'strategies' => 'content-strategies',
      'training-offers' => 'content-training-offers',
    ];

    return $data[$contentType];
  }

}
