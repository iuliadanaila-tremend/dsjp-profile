<?php

namespace Drupal\dsjp_translation;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\tmgmt\Data;
use Drupal\tmgmt\JobItemInterface;
use Drupal\tmgmt\TMGMTException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a service for eTranslation integration.
 */
class Etranslation {

  use StringTranslationTrait;

  /**
   * The http client service.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The tmgmt data service property.
   *
   * @var \Drupal\tmgmt\Data
   */
  protected $tmgmtData;

  /**
   * The request stack service property.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Etranslation service constructor.
   *
   * @param \GuzzleHttp\ClientInterface $client
   *   The http client.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   The logger channel.
   * @param \Drupal\tmgmt\Data $tmgmtData
   *   The tmgmt data service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack service.
   */
  public function __construct(ClientInterface $client, LoggerChannelFactoryInterface $loggerChannelFactory, Data $tmgmtData, RequestStack $requestStack) {
    $this->httpClient = $client;
    $this->logger = $loggerChannelFactory;
    $this->tmgmtData = $tmgmtData;
    $this->requestStack = $requestStack;
  }

  /**
   * Requests multiple translations for the given entities.
   *
   * @param array $entities
   *   The entities to translate.
   * @param array $settings
   *   The eTranslation plugin settings.
   *
   * @return array
   *   Returns an array with the translation status of each job item.
   */
  public function requestTranslation(array $entities, array $settings) {
    $translated = [];
    foreach ($entities as $entity) {
      $translated[$entity->id()] = $this->translateEntity($entity, $settings);
    }

    return $translated;
  }

  /**
   * Prepares the entity data and makes the request translation.
   *
   * @param \Drupal\tmgmt\JobItemInterface $entity
   *   The job item to translate.
   * @param array $settings
   *   The eTranslation plugin settings.
   *
   * @return bool
   *   Returns TRUE if the translation has been added, FALSE otherwise.
   */
  public function translateEntity(JobItemInterface $entity, array $settings) {
    $entityData = $entity->getData();
    $flattenedData = $this->tmgmtData->filterTranslatable($entityData);
    // In some cases, the first word of the input would change the mime type of
    // the encoded document, so we wrap every string in a p tag.
    foreach ($flattenedData as &$fieldData) {
      if (!isset($fieldData['#format'])) {
        $fieldData['#text'] = '<p>' . $fieldData['#text'] . '</p>';
      }
    }
    $implodedInput = implode('<!--delimiter-->', array_column($flattenedData, '#text'));
    if (!empty($implodedInput)) {
      $job = $entity->getJob();

      $caller_information = [
        'application' => $settings['credentials']['username'],
        'username' => $entity->getJob()->getOwner()->getDisplayName(),
      ];
      $url = Url::fromRoute('dsjp_translation.translation_callback', [], ['absolute' => TRUE])->toString();
      // If the basic auth credentials are set, recreate the url using those.
      if (!empty($settings['basic_auth']) && !empty($settings['basic_auth']['username'])) {
        $request = $this->requestStack->getCurrentRequest();
        // Create the base url with basic auth credentials.
        $baseUrl = $request->getScheme() . '://' . $settings['basic_auth']['username'] . ':' . $settings['basic_auth']['password'] . '@' . $request->getHttpHost();
        $url = $baseUrl . Url::fromRoute('dsjp_translation.translation_callback')->toString();
      }
      $destinations = [
        'httpDestinations' => [
          $url,
        ],
      ];
      $data = [
        'sourceLanguage' => $job->getSourceLangcode(),
        'externalReference' => $entity->id(),
        'targetLanguages' => [
          $job->getRemoteTargetLanguage(),
        ],
        'destinations' => $destinations,
        'callerInformation' => $caller_information,
        'documentToTranslateBase64' => [
          'content' => base64_encode($implodedInput),
          'format' => 'html',
        ],
      ];
      $post = json_encode($data);
      try {
        $request = $this->httpClient->request('post', $settings['endpoint'], [
          'headers' => [
            'Content-Type' => 'application/json',
            'Content-length' => strlen($post),
          ],
          'body' => $post,
          'auth' => [
            $settings['credentials']['username'],
            $settings['credentials']['password'],
            'digest',
          ],
        ]);
        $content = $request->getBody()->getContents();
        $content = json_decode($content);
        if ($content < 0) {
          $this->logger->get('dsjp_translation')->error($this->t('Error code @code on eTranslation endpoint for job @job_id.', [
            '@code' => $content,
            '@job_id' => $job->id(),
          ]));
          $entity->addMessage($this->t('Error code @code', [
            '@code' => $content,
          ]));

          return FALSE;
        }
        else {
          $this->logger->get('dsjp_translation')->info($this->t('New translation request made for job id: @job_id', [
            '@job_id' => $job->id(),
          ]));
          return TRUE;
        }
      }
      catch (GuzzleException $e) {
        $this->logger->get('dsjp_translation')->error($e->getMessage());
        return FALSE;
      }
    }

    return FALSE;
  }

  /**
   * Adds the translated data on the job item.
   *
   * @param \Drupal\tmgmt\JobItemInterface $entity
   *   The job item entity to update.
   * @param array $translatedData
   *   The translated data from the request.
   *
   * @return bool
   *   Returns TRUE if the data has been added, FALSE otherwise.
   */
  public function addTranslationData(JobItemInterface $entity, array $translatedData) {
    $entityData = $entity->getData();

    $flattenedData = $this->tmgmtData->filterTranslatable($entityData);
    $index = 0;
    foreach ($flattenedData as $key => $data) {
      if (isset($translatedData[$index])) {
        // If the element is not a text area, strip the p tags added previously.
        if (!isset($data['#format'])) {
          $translatedData[$index] = strip_tags($translatedData[$index]);
        }
        $flattenedData[$key]['#text'] = $translatedData[$index];
        $index++;
      }
    }
    try {
      $unflattenedData = $this->tmgmtData->unflatten($flattenedData);
      $entity->addTranslatedData($unflattenedData);

      return TRUE;
    }
    catch (TMGMTException $e) {
      $this->logger->get('dsjp_translation')->error($e->getMessage());
      return FALSE;
    }
  }

}
