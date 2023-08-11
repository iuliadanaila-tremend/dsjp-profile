<?php

namespace Drupal\dsjp_coalition;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Provides a service for Danish NC integration.
 */
class DanishMigrationService implements MigrationServiceInterface {

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
  public function getBaseUrl(array $data) {
    $endpoint = str_replace('-', '', Unicode::ucwords($data['from_content_type']));

    return $data['api_base_url'] . '/' . $endpoint;
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalPages(array $data) {
    $totalPages = 0;
    $options['headers'] = [
      'Content-Type' => "application/json",
    ];
    // If there is a last imported date, filter by it.
    try {
      $response = $this->httpClient->request('GET', $this->getBaseUrl($data), $options);
      $response = $this->json->decode($response->getBody()->getContents());
      // The number of pages is the round of the results counter per number of
      // results per page.
      if (isset($response['NumberOfResults']) && isset($response['ResultsPerPage'])) {
        $totalPages = ceil($response['NumberOfResults'] / $response['ResultsPerPage']);
      }
      // Make sure that there are items to imported based on the last import
      // date.
      if (!empty($response['Results'])) {
        $item = $response['Results'][0];
        $updated = strtotime($item['Modified']);
        if ($updated < $data['last_imported']) {
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
    $options = [
      'headers' => ['Content-Type' => "application/json"],
    ];
    try {
      $return = [];
      $response = $this->httpClient->request('GET', $url, $options);
      $statusCode = $response->getStatusCode();

      if ($statusCode == 200) {
        $json = $this->json->decode($response->getBody()->getContents());
        $return = $json['Results'];
        $this->setSelectListFieldValues($return);
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
  protected function setSelectListFieldValues(&$return) {
    $fields = [
      'TargetGroups' => 'http://data.europa.eu/snb/target-group/',
      'TargetLanguages' => 'http://publications.europa.eu/resource/authority/language/',
      'AssesmentTypes' => 'http://data.europa.eu/snb/learning-assessment/',
      'Effort' => 'http://data.europa.eu/snb/learning-schedule/',
      'CredentialOffered' => 'http://data.europa.eu/snb/credential/',
      'TypologyOfTrainingOpportunity' => 'http://data.europa.eu/snb/learning-opportunity/',
      'LearningActivity' => 'http://data.europa.eu/snb/learning-activity/',
    ];
    foreach ($return as &$data) {
      foreach ($fields as $key => $value) {
        if (isset($data[$key])) {
          if (in_array($key,
            ['Effort', 'CredentialOffered', 'TypologyOfTrainingOpportunity'])) {
            $data[$key]['Id'] = $value . $data[$key]['Id'];
          }
          else {
            foreach ($data[$key] as &$fieldValue) {
              if (isset($fieldValue['Id'])) {
                $fieldValue['Id'] = $value . $fieldValue['Id'];
              }
            }
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getBasePath($url) {
    // Because the parse_url function is forbidden, we have to split the url in
    // order to get the base path of the coalition website. Here we get the
    // url string from the beginning until the "jsonapi" part.
    preg_match('/(?<=^)(.*)(?=DSJPAPI)/', $url, $matches);

    return $matches[0];
  }

}
