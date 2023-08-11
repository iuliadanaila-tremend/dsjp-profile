<?php

namespace Drupal\dsjp_user\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the organisations autocomplete page controller.
 */
class OrganisationAutocomplete extends ControllerBase {

  /**
   * Handles the automcomplete results.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current page request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The json response containing the organisation names.
   */
  public function handleAutocomplete(Request $request) {
    $results = [];
    $input = $request->query->get('q');
    if (!empty($input)) {
      $input = Xss::filter($input);

      $query = $this->entityTypeManager()->getStorage('node')->getQuery()
        ->condition('title', $input, 'CONTAINS')
        ->condition('type', 'dsj_organization')
        ->sort('created', 'DESC')
        ->accessCheck(TRUE)
        ->range(0, 10);

      $ids = $query->execute();
      $nodes = $ids ? $this->entityTypeManager()->getStorage('node')->loadMultiple($ids) : [];
      foreach ($nodes as $node) {
        if ($node->isPublished()) {
          $results[] = [
            'value' => trim($node->label()),
            'label' => $node->label(),
          ];
        }
      }
    }

    return new JsonResponse($results);
  }

}
