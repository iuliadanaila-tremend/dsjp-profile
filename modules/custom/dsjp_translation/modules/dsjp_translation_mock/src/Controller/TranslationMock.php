<?php

namespace Drupal\dsjp_translation_mock\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Laminas\Diactoros\Response\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a mock endpoint for the translation.
 */
class TranslationMock extends ControllerBase {

  /**
   * The http client service property.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * TranslationMock constructor.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The http client interface.
   */
  public function __construct(ClientInterface $httpClient) {
    $this->httpClient = $httpClient;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client')
    );
  }

  /**
   * Callback for the mock endpoint.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   */
  public function callback(Request $request) {
    $requestData = json_decode($request->getContent());
    $targetLanguage = reset($requestData->targetLanguages);

    try {
      $url = Url::fromRoute('dsjp_translation.translation_callback', [], ['absolute' => TRUE])->toString();
      $response = base64_encode("title translated to $targetLanguage<!--delimiter-->body translated to $targetLanguage");
      $this->httpClient->request('post', $url, [
        'query' => [
          'external-reference' => $requestData->externalReference,
          'target-language' => $targetLanguage,
          'request-id' => 1,
        ],
        'body' => $response,
      ]);
    }
    catch (GuzzleException $e) {
      $this->loggerFactory->get('dsjp_translation_mock')->error($e->getMessage());
    }

    return new JsonResponse(1);
  }

}
