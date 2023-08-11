<?php

namespace Drupal\dsjp_community\Controller;

use Drupal\group\Controller\GroupMembershipController;
use Drupal\group\Entity\GroupInterface;

/**
 * Provides a form for leaving a group.
 */
class GroupMembershipControllerOverride extends GroupMembershipController {

  /**
   * The _title_callback for the join form route.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to join.
   *
   * @return string
   *   The page title.
   */
  public function joinTitle(GroupInterface $group) {
    return $this->t('%label', ['%label' => $group->label()]);
  }

}
