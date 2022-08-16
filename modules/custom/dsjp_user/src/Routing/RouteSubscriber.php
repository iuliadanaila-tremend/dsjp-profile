<?php

namespace Drupal\dsjp_user\Routing;

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
    if ($route = $collection->get('user.login')) {
      $route->setPath('/eulogin');
    }
    if ($route = $collection->get('legal.legal')) {
      $route->setDefault('_title', 'Terms of Service');
    }
    if ($route = $collection->get('system.403')) {
      $route->setDefault('_title', 'Login required');
    }
    if ($route = $collection->get('view.groups.dsj_user_groups')) {
      $route->setRequirement('_custom_access', '\Drupal\dsjp_user\Access\UserGroupsAccess::checkAccess');
    }
    // Hide the user webform submissions page as we use views to list them.
    if ($route = $collection->get('entity.webform_submission.user')) {
      $route->setRequirements(['_access' => 'FALSE']);
    }
    // Don't allow normal users to see the table version of the submission.
    if ($route = $collection->get('entity.webform_submission.table')) {
      $route->setRequirements(['_role' => 'administrator']);
    }
  }

}
