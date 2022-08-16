<?php

namespace Drupal\dsjp_community\Form;

use Drupal\Core\Url;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Form\GroupLeaveForm;

/**
 * Provides a form for leaving a group.
 */
class GroupLeaveOverrideForm extends GroupLeaveForm {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    /** @var \Drupal\group\Entity\GroupContent $group_content */
    $group_content = $this->getEntity();
    $group = $group_content->getGroup();
    $route_params = [
      'user' => $this->currentUser()->id(),
    ];

    if ($group instanceof GroupInterface) {
      switch ($group->getGroupType()->id()) {
        case 'dsj_organization':
          $route_name = 'view.groups.user_pledge_organisations';
          break;

        default:
          $route_name = 'view.groups.dsj_user_groups';
      }
    }
    else {
      return parent::getCancelURL();
    }

    return new Url($route_name, $route_params);
  }

}
