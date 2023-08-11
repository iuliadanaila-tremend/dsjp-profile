<?php

namespace Drupal\dsjp_pledge\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides an event subscriber to alter pledge routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('view.pledges.user_pledges')) {
      $route->setRequirement('_custom_access', '\Drupal\dsjp_pledge\Access\UserPledgesAccess::checkRoleAccess');
    }
    if ($route = $collection->get('view.groups.user_pledge_organisations')) {
      $route->setRequirement('_custom_access', '\Drupal\dsjp_pledge\Access\UserPledgesAccess::checkGeneralPageAccess');
    }
    if ($route = $collection->get('view.group_nodes.page_group_pledges')) {
      $route->setRequirement('_custom_access', '\Drupal\dsjp_pledge\Access\UserPledgesAccess::checkGroupNodeAccess');
    }
  }

}
