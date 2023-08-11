<?php

namespace Drupal\dsjp_community\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Implements breadcrumb for Group Forms.
 */
class BreadcrumbGroupsBuilder implements BreadcrumbBuilderInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $parameters = $route_match->getParameters()->all();
    $current_path = $route_match->getRouteObject()->getPath();

    if (isset($parameters['group']) && $parameters['group'] instanceof GroupInterface && ($current_path == '/group/{group}/join' || $current_path == '/group/{group}/leave' ||
        $current_path == '/group/{group}/invite-members/confirm' || $current_path == '/group/{group}/content/{group_content}/delete')) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();

    $group = $route_match->getParameter('group');
    $current_path = $route_match->getRouteObject()->getPath();

    switch ($current_path) {
      case '/group/{group}/join':

      case '/group/{group}/leave':
        $breadcrumb->addLink(Link::createFromRoute('Home', '<front>'));
        break;

      case '/group/{group}/invite-members/confirm':
        $breadcrumb->addLink(Link::createFromRoute('Home', '<front>'));

        $new_title = 'Invite members';

        $link = Link::createFromRoute($new_title, 'dsjp_community.invitation.bulk.confirm', ['group' => $group->id()]);

        $breadcrumb->addLink($link);

        $breadcrumb->addCacheableDependency($new_title);

        break;

      case '/group/{group}/content/{group_content}/delete':
        $breadcrumb->addLink(Link::createFromRoute('Home', '<front>'));

        $new_title = $route_match->getParameter('group_content')->label();

        $link = Link::createFromRoute($new_title, '<none>', ['group' => $group->id()]);

        $breadcrumb->addLink($link);

        $breadcrumb->addCacheableDependency($new_title);

        break;

    }
    $breadcrumb->addCacheContexts(['url']);

    $breadcrumb->addCacheTags(["group:{$group->id()}"]);

    return $breadcrumb;
  }

}
