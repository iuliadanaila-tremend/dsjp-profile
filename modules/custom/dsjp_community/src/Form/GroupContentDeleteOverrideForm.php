<?php

namespace Drupal\dsjp_community\Form;

use Drupal\group\Entity\Form\GroupContentDeleteForm;

/**
 * Provides a form for deleting a member.
 */
class GroupContentDeleteOverrideForm extends GroupContentDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $message = $this->getEntity()->getGroup()->label();
    return $message;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $message = '<span class="group-delete-member-message">' . $this->t('Are you sure you want to delete %name?', ['%name' => $this->entity->label()]) . '</span>';
    $warning = '<span class="group-delete-member-warning">' . 'This action will end your membership of the group' . '</span>';
    return $message . '<br />' . $warning;
  }

}
