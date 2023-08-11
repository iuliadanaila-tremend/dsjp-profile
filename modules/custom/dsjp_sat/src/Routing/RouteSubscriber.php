<?php

namespace Drupal\dsjp_sat\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('view.dsj_assessments.page_1')) {
      $route->setRequirement('_custom_access', '\Drupal\dsjp_user\Access\UserGroupsAccess::checkAccess');
    }
  }

}
