<?php

namespace Drupal\dsjp_content\Form;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class for custom form - facet keywords filter.
 *
 * @package Drupal\dsjp_content\Form
 */
class Keywords extends FormBase {

  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructor for our class.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')
    );
  }

  /**
   * Return form ID.
   *
   * @inheritDoc
   */
  public function getFormId() {
    return 'dsjp_keywords_form';
  }

  /**
   * Build keywords form.
   *
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $keywords = Xss::filter($this->requestStack->getCurrentRequest()
      ->get('keywords'));

    $form['keywords'] = [
      "#type" => "textfield",
      "#size" => 30,
      "#required" => FALSE,
      "#input" => TRUE,
      "#maxlength" => 128,
      '#default_value' => mb_strlen($keywords) ? $keywords : '',
      '#attributes' => [
        'placeholder' => mb_strlen($keywords) ? $keywords : $this->t('Keywords'),
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Filter'),
        '#wrapper_attributes' => [
          'class' => [
            'form-actions',
          ],
        ],
      ],
    ];
    $form['#attributes'] = [
      'class' => [
        'views-exposed-form',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect(
      '<current>',
      [
        'keywords' => $form_state->getValue('keywords'),
      ],
      [
        'query' => [
          'keywords' => $form_state->getValue('keywords'),
        ],
      ],
    );
  }

}
