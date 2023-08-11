<?php

namespace Drupal\dsjp_learning_states\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\dsjp_learning_states\Manager\LearningStatesManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a default form.
 */
class DsjpLearningStatesChangeStateForm extends FormBase {

  /**
   * The current account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The learning states manager.
   *
   * @var \Drupal\dsjp_learning_states\Manager\LearningStatesManager
   */
  protected $learningStatesManager;

  /**
   * The form builder.
   *
   * @var \Drupal\dsjp_learning_states\Manager\LearningStatesManager
   */
  protected $formBuilder;

  /**
   * The constructor for DsjpLearningStatesChangeStateForm.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user that is using this form.
   * @param \Drupal\dsjp_learning_states\Manager\LearningStatesManager $learningStatesManager
   *   The learning states manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The learning states manager.
   */
  public function __construct(AccountInterface $account, LearningStatesManager $learningStatesManager, FormBuilderInterface $formBuilder) {
    $this->account = $account;
    $this->learningStatesManager = $learningStatesManager;
    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
    // Load the service required to construct this class.
      $container->get('current_user'),
      $container->get('dsjp_learning_states.learning_states_manager'),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'learning_states_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $args = []) {
    $form_unique_id = $form_state->get('form_unique_id') ?? uniqid();

    $form['#prefix'] = '<div id="learning-states-ajax-form-' . $form_unique_id . '" class="learning-states-forms">';
    $form['#suffix'] = '</div>';

    $entity = $form_state->get('entity') ?? $args['entity'];
    $mode = $form_state->get('mode') ?? $args['mode'];
    $form_state->setRequestMethod('POST');
    $form_state->setCached(TRUE);

    $form_state->set('entity', $entity);
    $form_state->set('mode', $mode);
    $form_state->set('form_unique_id', $form_unique_id);

    if ($mode == 'listing') {
      $this->listingForm($form, $form_state);
      return $form;
    }

    $form['learning_states'] = [
      '#type' => 'select',
      '#options' => [],
      '#default_value' => [],
      '#validated' => TRUE,
      '#ajax' => [
        'callback' => [$this, 'changeLearningState'],
        'disable-refocus' => TRUE,
        'event' => 'change',
        'wrapper' => 'learning-states-ajax-form-' . $form_unique_id,
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Verifying entry...'),
        ],
      ],
    ];

    $form['save_learning_state'] = [
      '#type' => 'button',
      '#value' => $this->t('Save'),
      '#data' => 'save_state',
      '#ajax' => [
        'callback' => [$this, 'changeLearningState'],
        'wrapper' => 'learning-states-ajax-form-' . $form_unique_id,
        'disable-refocus' => TRUE,
      ],
      '#button_type' => 'default',
    ];

    $form['#attached']['library'][] = 'dsjp_learning_states/learning_states';

    $this->syncFormAction($form, $form_state);

    return $form;
  }

  /**
   * Ajax callback event.
   *
   * @param array $form
   *   The triggering form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state of current form.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object, holding current path and request uri.
   *
   * @return mixed
   *   Must return AjaxResponse object or render array.
   *   Never return NULL or invalid render arrays. This
   *   could/will break your forms.
   */
  public function changeLearningState(array &$form, FormStateInterface $form_state, Request $request) {
    $entity = $form_state->get('entity');
    $selectedValue = $form_state->getValue('learning_states');
    if (isset($form_state->getTriggeringElement()['#data']) && $form_state->getTriggeringElement()['#data'] == 'save_state') {
      $this->learningStatesManager->addLearningState($entity, $this->account, 'interested');
    }
    else {
      if ($selectedValue) {
        $this->learningStatesManager->addLearningState($entity, $this->account, $selectedValue);
        if ($selectedValue == 'remove') {
          $this->learningStatesManager->removeLearningState($entity, $this->account);
        }
      }
    }

    $response = new AjaxResponse();

    // Rebuild the form for AJAX purpose.
    $reload_form = $this->formBuilder
      ->rebuildForm('learning_states_form', $form_state, $form);

    // Make sure we only replace our form.
    $response->addCommand(new ReplaceCommand('#learning-states-ajax-form-' . $form_state->get('form_unique_id'), $reload_form));
    if ($selectedValue == 'remove') {
      $response->addCommand(new InvokeCommand('#learning-states-ajax-form-' . $form_state->get('form_unique_id'), 'hideListItem', ['.view-dsj-learning-experience li']));
    }

    return $response;

  }

  /**
   * Sync the form according to the values in database.
   *
   * @param array $form
   *   The triggering form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state of current form.
   */
  public function syncFormAction(array &$form, FormStateInterface &$form_state) {
    $learning_state = $this->learningStatesManager->getEntityLearningState($form_state->get('entity'), $this->account);
    // If we have a learning state, generate a select with posible transitions.
    if ($learning_state) {
      $possible_transitions = $this->learningStatesManager->getPosibleTransitions($learning_state->getValue());
      $form['learning_states']['#options'] = $possible_transitions;
      $form['learning_states']['#default_value'] = [$learning_state->getValue()];
      $form['save_learning_state']['#access'] = FALSE;
    }
    else {
      $form['learning_states']['#access'] = FALSE;
      $form['save_learning_state']['#access'] = TRUE;
    }

    if (isset($form_state->getTriggeringElement()['#data']) && ($form_state->getTriggeringElement()['#data'] == 'save_state')) {
      $form['learning_states']['#access'] = TRUE;
      $form['learning_states']['#default_value'] = ['interested'];
      $form['save_learning_state']['#access'] = FALSE;
    }
  }

  /**
   * Update form for the listing components.
   *
   * @param array $form
   *   The triggering form render array.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   Form state of current form.
   */
  public function listingForm(array &$form, FormStateInterface &$form_state) {
    $form['saved_message'] = [
      '#markup' => '<span class="learning-saved">' . $this->t('Saved') . '</span>',
    ];
    $learning_state = $this->learningStatesManager->getEntityLearningState($form_state->get('entity'), $this->account);
    if (!$learning_state) {
      $form['save_learning_state'] = [
        '#type' => 'button',
        '#value' => $this->t('Save'),
        '#data' => 'save_state',
        '#ajax' => [
          'callback' => [$this, 'changeLearningState'],
          'wrapper' => 'learning-states-ajax-form-' . $form_state->get('form_unique_id') ,
          'disable-refocus' => TRUE,
        ],
        '#button_type' => 'default',
      ];
      $form['saved_message']['#access'] = FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // AJAX form.
  }

}
