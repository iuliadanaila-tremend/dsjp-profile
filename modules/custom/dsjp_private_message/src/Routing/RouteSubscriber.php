<?php

namespace Drupal\dsjp_private_message\Routing;

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
    // Make sure a default value is added to the thread argument.
    if ($route = $collection->get('view.dsj_private_messages.private_messages_page')) {
      $route->setDefault('arg_1', 'last');
      $route->setRequirement('_custom_access', '\Drupal\dsjp_user\Access\UserGroupsAccess::checkAccess');
    }
  }

}
