<?php

namespace Drupal\dsjp_content\Plugin\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\node\Plugin\Action\AssignOwnerNode;

/**
 * Assigns ownership of a node to a user.
 *
 * @Action(
 *   id = "dsjp_node_owner_action",
 *   label = @Translation("DSJP - Change owner of node"),
 *   type = "node"
 * )
 */
class DsjpNodeOwner extends AssignOwnerNode {

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\node\NodeInterface $object */
    // Remove edit access for object owner as we use moderation states.
    $result = $object->access('update', $account, TRUE);

    return $return_as_object ? $result : $result->isAllowed();
  }

}
