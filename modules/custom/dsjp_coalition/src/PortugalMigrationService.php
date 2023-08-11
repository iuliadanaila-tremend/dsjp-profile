<?php

namespace Drupal\dsjp_coalition;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\State;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Provides a migration service for Portugal integration.
 */
class PortugalMigrationService implements MigrationServiceInterface {

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
    $config = $this->state->get('group_nc_' . $data['group_id'], []);
    $options['headers'] = [
      'Content-Type' => "application/json",
      'Authorization' => 'Basic ' . base64_encode($config['username'] . ':' . $config['password']),
    ];

    if ($data['from_content_type'] == 'training-offers') {
      $url = $this->getBaseUrl($data);
      $request = $this->requestEndpoint('GET', $url, $options);
      if ($request->getStatusCode() == 200) {
        $content = $request->getBody()->getContents();
        $content = $this->json->decode($content);
        if (!empty($content)) {
          return 1;
        }
        return 0;
      }
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
   * {@inheritdoc}
   */
  public function fetchData($page, array $data) {
    $config = $this->state->get('group_nc_' . $data['group_id'], []);
    $options['headers'] = [
      'Content-Type' => "application/json",
      'Authorization' => 'Basic ' . base64_encode($config['username'] . ':' . $config['password']),
    ];
    $url = $this->getBaseUrl($data);
    try {
      $request = $this->requestEndpoint('GET', $url, $options);
      $contents = $request->getBody()->getContents();
      $contents = $this->json->decode($contents);

      return $contents;
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
      'training-offers' => 'trainings',
    ];

    return $data[$contentType] ?? '';
  }

}
