<?php

namespace Drupal\user_online_status\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user_online_status\StatusService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * An example controller.
 */
class OnlineStatusController extends ControllerBase {

  /**
   * The status service.
   *
   * @var \Drupal\user_online_status\StatusService
   */
  protected $statusService;

  /**
   * OnlineStatusController constructor.
   *
   * @param \Drupal\user_online_status\StatusService $status_service
   *   The status service.
   */
  public function __construct(StatusService $status_service) {
    $this->statusService = $status_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user_online_status.status_service')
    );
  }

  /**
   * Returns Online status of users.
   *
   * @param string $uids
   *   The user id's separated by comma.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The response.
   *
   * @deprecated in 1.0.0-rc4
   *   Use UserOnlineStatusController::content instead.
   */
  public function content($uids) {
    $uids = explode(',', $uids);
    $statuses = $this->statusService->getMultipleUserStatuses($uids);

    // Mark this page as being non-cacheable.
    // Oh, seems JSON response are non-cacheable by default.
    // @see https://spinningcode.org/2017/05/cached-json-responses-in-drupal-8/
    // @see Drupal\Core\Cache\CacheableJsonResponse;
    // @see Drupal\Core\Cache\CacheableMetadata;
    $response = new JsonResponse();
    $response->setData($statuses);
    return $response;
  }

}
