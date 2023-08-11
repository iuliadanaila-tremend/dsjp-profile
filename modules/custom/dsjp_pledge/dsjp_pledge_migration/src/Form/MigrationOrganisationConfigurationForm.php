<?php

namespace Drupal\dsjp_pledge_migration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\FileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a configuration form for organisations migration.
 */
class MigrationOrganisationConfigurationForm extends ConfigFormBase {

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
      'dsjp_pledge_migration.organisations_configuration',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dsjp_pledge_migration_organisation_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory()->get('dsjp_pledge_migration.organisations_configuration');
    $form['organisations'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Organisations'),
      '#upload_location' => 'public://organisations_socials.csv',
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
      $this->configFactory()->getEditable('dsjp_pledge_migration.organisations_configuration')
        ->set('organisation', $form_state->getValue('organisations')[0])
        ->save();
      parent::submitForm($form, $form_state);
    }
    else {
      $this->configFactory()->getEditable('dsjp_pledge_migration.organisations_configuration')
        ->set('migration_step', 1)
        ->save();
      $this->messenger()->addStatus($this->t('Data queued for migration.'));
    }
  }

}
