<?php

namespace Drupal\fixed_block_content\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\ConfigFormBaseTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\fixed_block_content\FixedBlockContentInterface;

/**
 * Fixed block content form.
 */
class FixedBlockContentForm extends EntityForm implements ContainerInjectionInterface {

  use ConfigFormBaseTrait;

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['fixed_block_content.fixed_block_content.' . $this->entity->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    // We need at least one custom block type.
    $types = $this->entityTypeManager->getStorage('block_content_type')->loadMultiple();
    if (count($types) === 0) {
      return [
        '#markup' => $this->t('You have not created any block types yet. Go to the <a href=":url">block type creation page</a> to add a new block type.', [
          ':url' => Url::fromRoute('block_content.type_add')->toString(),
        ]),
      ];
    }

    $form = parent::form($form, $form_state);

    /** @var \Drupal\fixed_block_content\FixedBlockContentInterface $fixed_block */
    $fixed_block = $this->entity;

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#maxlength' => 255,
      '#default_value' => $fixed_block->label(),
      '#description' => $this->t("The block title."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $fixed_block->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => [
        'source' => ['title'],
        'exists' => ['Drupal\fixed_block_content\Entity\FixedBlockContent', 'load'],
      ],
      '#disabled' => !$fixed_block->isNew(),
    ];

    // Block content type (bundle).
    $form['block_content_bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Block content'),
      '#description' => $this->t('The block content type.'),
      '#options' => [],
      '#required' => TRUE,
      '#default_value' => $fixed_block->getBlockContentBundle(),
    ];

    // Options.
    $form['options'] = [
      '#type' => 'details',
      '#title' => $this->t('Options'),
      '#open' => $fixed_block->isNew(),
    ];

    // Protected option.
    $protected_description = $this->t('When enabled, the standard custom block will not appear in the list of available blocks, being only available as a fixed block.');
    if (!$fixed_block->isNew()
      && $block_content = $fixed_block->getBlockContent(FALSE)) {
      $protected_description = [
        [
          '#markup' => $protected_description,
        ],
        [
          '#type' => 'html_tag',
          '#tag' => 'br',
        ],
        [
          '#markup' => $this->t('<em>This option cannot be changed because a custom block is already linked to this fixed block</em>.'),
        ],
      ];
    }
    $form['options']['protected'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Available only as fixed block'),
      '#description' => $protected_description,
      '#default_value' => $fixed_block->isProtected(),
      '#disabled' => !empty($block_content),
    ];

    // Auto-export option.
    $form['options']['auto_export'] = [
      '#type' => 'radios',
      '#title' => $this->t('Automatic block content update'),
      '#description' => $this->t('The automatic block content update takes place during the site configuration import.'),
      '#description_display' => 'before',
      '#default_value' => $fixed_block->getAutoExportState(),
      '#options' => [
        FixedBlockContentInterface::AUTO_EXPORT_DISABLED => $this->t('Disabled'),
        FixedBlockContentInterface::AUTO_EXPORT_ON_EMPTY => $this->t('On empty'),
        FixedBlockContentInterface::AUTO_EXPORT_ALWAYS => $this->t('Always'),
      ],
      FixedBlockContentInterface::AUTO_EXPORT_DISABLED => [
        '#description' => $this->t('No action. No block content creation or update takes place.'),
      ],
      FixedBlockContentInterface::AUTO_EXPORT_ON_EMPTY => [
        '#description' => $this->t("Create new block content, empty or with the default content, only if it doesn't exist."),
      ],
      FixedBlockContentInterface::AUTO_EXPORT_ALWAYS => [
        '#description' => [
          [
            '#markup' => $this->t('Create new block content or update existing with the default content if it was changed.'),
          ],
          [
            '#type' => 'html_tag',
            '#tag' => 'br',
          ],
          [
            '#markup' => $this->t('<em>Use with caution, any modifications in the in the custom block content will be lost.</em>'),
          ],
        ],
      ],
    ];

    /** @var \Drupal\block_content\Entity\BlockContentType $block_content_type */
    foreach ($types as $key => $block_content_type) {
      $form['block_content_bundle']['#options'][$key] = $block_content_type->label();
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, FormStateInterface $form_state) {
    // No actions if there are no form.
    return isset($form['title']) ? parent::actions($form, $form_state) : [];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.fixed_block_content.collection');
    $this->entity->save();
  }

}
