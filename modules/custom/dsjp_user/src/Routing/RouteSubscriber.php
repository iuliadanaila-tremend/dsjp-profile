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
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   * @SuppressWarnings(PHPMD.NPathComplexity)
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
    // Allow community manager to see the Groups tab.
    if ($route = $collection->get('view.groups.dsj_user_groups')) {
      $route->setRequirement('_custom_access', '\Drupal\dsjp_user\Access\UserGroupsAccess::checkAccessAdminCommunityManager');
    }
    // Allow community manager to see the Pledges tab.
    if ($route = $collection->get('view.pledges.user_pledges')) {
      $route->setRequirement('_custom_access', '\Drupal\dsjp_user\Access\UserGroupsAccess::checkAccessAdminCommunityManager');
    }
    // Allow community manager to see the Organisations tab.
    if ($route = $collection->get('view.groups.user_pledge_organisations')) {
      $route->setRequirement('_custom_access', '\Drupal\dsjp_user\Access\UserGroupsAccess::checkAccessAdminCommunityManager');
    }
    // Hide the user webform submissions page as we use views to list them.
    if ($route = $collection->get('entity.webform_submission.user')) {
      $route->setRequirements(['_access' => 'FALSE']);
    }
    // Don't allow normal users to see the table version of the submission.
    if ($route = $collection->get('entity.webform_submission.table')) {
      $route->setRequirements(['_role' => 'administrator']);
    }
    // Don't allow normal users to see "All entities" tab.
    if ($route = $collection->get('entity.group_content.collection')) {
      $route->setRequirements(['_role' => 'administrator,dsj_reviewer']);
    }
    // Manage tabs on organisation group type.
    if ($route = $collection->get('view.dsj_discussions.dsj_group_discussions')) {
      $route->setRequirement('_custom_access', '\Drupal\dsjp_user\Access\UserGroupsAccess::checkOrganisationAccess');
    }
    if ($route = $collection->get('entity.group_permission.canonical')) {
      $route->setRequirement('_custom_access', '\Drupal\dsjp_user\Access\UserGroupsAccess::checkOrganisationAccess');
    }
    if ($route = $collection->get('view.group_nodes.page_1')) {
      $route->setRequirement('_custom_access', '\Drupal\dsjp_user\Access\UserGroupsAccess::checkOrganisationAccess');
    }
    // Allow to see only own profile information.
    if ($route = $collection->get('view.dsj_learning_experience.dsj_user_learning')) {
      $route->setRequirement('_custom_access', '\Drupal\dsjp_user\Access\UserGroupsAccess::checkAccess');
    }
  }

}
