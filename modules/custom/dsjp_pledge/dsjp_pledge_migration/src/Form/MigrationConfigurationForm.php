<?php

namespace Drupal\dsjp_pledge_migration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\FileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a configuration form for pledges migration.
 */
class MigrationConfigurationForm extends ConfigFormBase {

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
  protected function getEditableConfigNames() {
    return [
      'dsjp_pledge_migration.configuration',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dsjp_pledge_migration_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory()->get('dsjp_pledge_migration.configuration');
    $form['pledges'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Pledges'),
      '#upload_location' => 'public://pledges.csv',
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
    ];
    $pid = $config->get('pledge');
    if (!empty($pid)) {
      $form['pledges']['#default_value'] = [$pid];

    }
    $form['initiatives'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Initiatives'),
      '#upload_location' => 'public://initiatives.csv',
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
    ];
    $iid = $config->get('initiative');
    if (!empty($iid)) {
      $form['initiatives']['#default_value'] = [$iid];

    }
    $form['organisations'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Organisations'),
      '#upload_location' => 'public://organisations.csv',
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
    ];
    $oid = $config->get('organisation');
    if (!empty($oid)) {
      $form['organisations']['#default_value'] = [$oid];

    }
    $form['queue'] = [
      '#type' => 'submit',
      '#name' => 'queue',
      '#value' => $this->t('Queue data'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    if ($element['#name'] == 'op') {
      $this->makeFilePermanent($form_state->getValue('pledges'));
      $this->makeFilePermanent($form_state->getValue('initiatives'));
      $this->makeFilePermanent($form_state->getValue('organisations'));
    }
  }

  /**
   * Makes a file entity permanent.
   *
   * @param array $formValue
   *   Form values.
   */
  protected function makeFilePermanent(array $formValue) {
    if (!empty($formValue)) {
      $file = $this->entityTypeManager->getStorage('file')->load($formValue[0]);
      if ($file instanceof FileInterface) {
        $file->setPermanent();
        $file->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    if ($element['#name'] == 'op') {
      $this->configFactory()->getEditable('dsjp_pledge_migration.configuration')
        ->set('pledge', $form_state->getValue('pledges')[0])
        ->set('initiative', $form_state->getValue('initiatives')[0])
        ->set('organisation', $form_state->getValue('organisations')[0])
        ->save();
      parent::submitForm($form, $form_state);
    }
    else {
      $this->configFactory()->getEditable('dsjp_pledge_migration.configuration')
        ->set('pledge_migration_step', 1)
        ->save();
      $this->messenger()->addStatus($this->t('Data queued for migration.'));
    }
  }

}
