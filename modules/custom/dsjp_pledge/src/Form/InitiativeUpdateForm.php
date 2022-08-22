<?php

namespace Drupal\dsjp_pledge\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for updating initiative quantities.
 */
class InitiativeUpdateForm extends FormBase {

  /**
   * The entity type manager service property.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dsj_initiative_update_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $node = $this->getRouteMatch()->getParameter('node');
    if ($node instanceof NodeInterface && $node->bundle() == 'dsj_pledge_initiative') {
      $form['#prefix'] = '<a href="' . Url::fromRoute('entity.node.canonical', [
        'node' => $node->get('field_dsj_pledge')->target_id,
      ])->toString() . '">' . $this->t('Back to pledge') . '</a>';
      $initiativeType = $node->get('field_dsj_initiative_type')->value;
      $form['pledge'] = $this->entityTypeManager->getViewBuilder('node')->view($node, 'teaser');
      $form['#attributes']['class'][] = $initiativeType;
      $beneficiaries = $node->get('field_dsj_initiative_beneficiary')->getValue();
      $default_value = [];
      $json = $node->get('field_dsj_initiative_status')->getValue();
      if (!empty($json[0]['value'])) {
        $default_value = json_decode($json[0]['value'], TRUE);
      }
      $form['initiative_beneficiary'] = [
        '#type' => 'fieldset',
        '#tree' => TRUE,
      ];
      foreach ($beneficiaries as $index => $beneficiary) {
        if ($beneficiary['status'] == 1) {
          $form['initiative_beneficiary'][$index] = [
            '#type' => 'fieldset',
            '#legend_display' => TRUE,
            '#tree' => TRUE,
            '#prefix' => '<div id="initiative-' . str_replace(' ', '_', mb_strtolower($beneficiary['value'])) . '">',
            '#suffix' => '</div>',
          ];
          $form['initiative_beneficiary'][$index]['current_number'] = [
            '#type' => 'number',
            '#title' => $this->t('Beneficiary: @beneficiary', [
              '@beneficiary' => $beneficiary['value'],
            ]),
            '#default_value' => isset($default_value[$index]) ? $default_value[$index]['value'] : 0,
            '#min' => isset($default_value[$index]) ? $default_value[$index]['value'] : 0,
            '#max' => $beneficiary['number'],
          ];
          $form['initiative_beneficiary'][$index]['target_number'] = [
            '#type' => 'number',
            '#title' => $this->t('KPI Target'),
            '#default_value' => $beneficiary['number'],
            '#disabled' => TRUE,
          ];
        }
      }
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
      ];
      $form['cancel'] = [
        '#type' => 'submit',
        '#value' => $this->t('Cancel'),
        '#name' => 'cancel',
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $op = $form_state->getTriggeringElement();
    if (isset($op['#name']) && $op['#name'] == 'cancel') {
      $node = $this->getRouteMatch()->getParameter('node');
      if ($node instanceof NodeInterface) {
        $form_state->setRedirect('entity.node.canonical', [
          'node' => $node->get('field_dsj_pledge')->target_id,
        ]);
      }
    }
    else {
      $json = [];
      foreach ($form_state->getValue('initiative_beneficiary') as $index => $value) {
        $json[$index] = [
          'value' => $value['current_number'],
        ];
      }
      if (!empty($json)) {
        $node = $this->getRouteMatch()->getParameter('node');
        if ($node instanceof NodeInterface) {
          $node->set('field_dsj_initiative_status', json_encode($json));
          $node->save();
          Cache::invalidateTags(['node:' . $node->get('field_dsj_pledge')->target_id]);
          $this->messenger()->addStatus($this->t('Initiative updated.'));
        }
      }
    }
  }

}
