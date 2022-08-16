<?php

namespace Drupal\group\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\Form\GroupContentDeleteForm;

/**
 * Provides a form for leaving a group.
 */
class GroupLeaveForm extends GroupContentDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $message = 'Are you sure you want to leave %group?';
    $replace = ['%group' => $this->getEntity()->getGroup()->label()];
    return $this->t($message, $replace);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Leave group');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->messenger()->addMessage($this->t('You have successfully left this group'));
  }

}
