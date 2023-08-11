<?php

namespace Drupal\dsjp_learning_states\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the dsjp_learning_states entity edit forms.
 */
class DsjpLearningStatesForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    $entity = $this->getEntity();
    $result = $entity->save();
    $link = $entity->toLink($this->t('View'))->toString();

    $message_arguments = ['%label' => $this->entity->label()];
    $logger_arguments = $message_arguments + ['link' => $link];

    if ($result == SAVED_NEW) {
      $this->messenger()->addStatus($this->t('New dsjp_learning_states %label has been created.', $message_arguments));
      $this->logger('dsjp_learning_states')->notice('Created new dsjp_learning_states %label', $logger_arguments);
    }
    else {
      $this->messenger()->addStatus($this->t('The dsjp_learning_states %label has been updated.', $message_arguments));
      $this->logger('dsjp_learning_states')->notice('Updated new dsjp_learning_states %label.', $logger_arguments);
    }

    $form_state->setRedirect('entity.dsjp_learning_states.collection', ['dsjp_learning_states' => $entity->id()]);

    return $result;
  }

}
