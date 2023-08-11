<?php

namespace Drupal\dsjp_content\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // Define custom access for '/node/add/dsj_pledge'.
    if ($route = $collection->get('node.add')) {
      $route->setRequirement('_custom_access', 'dsjp_content.services_access_checker::access');
    }
  }

}
