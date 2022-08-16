<?php

namespace Drupal\user_online_status\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user_online_status\StatusService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * An example controller.
 */
class UserOnlineStatusController extends ControllerBase {

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
   * Returns the status of given users.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The response.
   */
  public function content(Request $request) {
    $data = json_decode($request->getContent());

    $statuses = [];

    if (isset($data->uids) && ($data->uids === array_filter($data->uids, 'is_string'))) {
      $statuses = $this->statusService->getMultipleUserStatuses($data->uids);
    }

    $response = new JsonResponse();
    $response->setData($statuses);
    return $response;
  }

}
