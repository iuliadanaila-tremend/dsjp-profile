<?php

namespace Drupal\dsjp_content\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\FileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the configuration form for the popup.
 */
class PopupConfigForm extends ConfigFormBase {

  /**
   * File manager storage property.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;


  /**
   * The file usage service.
   *
   * @var \Drupal\file\FileUsage\FileUsageInterface
   */
  protected $fileUsage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->fileUsage = $container->get('file.usage');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'dsjp_content.popup_config',
      'dsjp_content.cta_config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'popup_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory()->get('dsjp_content.popup_config');
    $form['show_popup'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show popup on pages'),
      '#default_value' => $config->get('show_popup') ?? FALSE,
    ];
    $form['popup_data'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="show_popup"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['popup_data']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $config->get('title') ?? '',
    ];
    $form['popup_data']['description'] = [
      '#type' => 'text_format',
      '#format' => 'full_html',
      '#title' => $this->t('Description'),
      '#default_value' => $config->get('description') ?? '',
    ];
    $form['popup_data']['image'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Image'),
      '#default_value' => $config->get('image') ? [$config->get('image')] : '',
      '#upload_location' => 'public://popup/',
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg webp'],
      ],
    ];
    $form['popup_data']['cta_button'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Cta button'),
      'cta_text' => [
        '#type' => 'textfield',
        '#title' => $this->t('Cta text'),
        '#default_value' => $config->get('cta_text') ?? '',
      ],
      'cta_link' => [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'node',
        '#attributes' => [
          'data-autocomplete-first-character-blacklist' => '/#?',
        ],
        '#process_default_value' => FALSE,
        '#title' => $this->t('Cta link'),
        '#default_value' => $config->get('cta_link') ? $this->getUriAsDisplayString($config->get('cta_link')) : '',
        '#element_validate' => [
          [
            'Drupal\typed_link\Plugin\Field\FieldWidget\TypedLinkWidget',
            'validateUriElement',
          ],
        ],
      ],
    ];

    $config = $this->configFactory()->get('dsjp_content.cta_config');

    $form['show_cta_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show cta button on header'),
      '#default_value' => $config->get('show_cta_button') ?? '',
    ];
    $form['cta_nav_button'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="show_cta_button"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['cta_nav_button']['cta_nav_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cta text'),
      '#default_value' => $config->get('cta_text') ?? '',
    ];
    $form['cta_nav_button']['cta_nav_url'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#attributes' => [
        'data-autocomplete-first-character-blacklist' => '/#?',
      ],
      '#process_default_value' => FALSE,
      '#title' => $this->t('Cta link'),
      '#default_value' => $config->get('cta_url') ? $this->getUriAsDisplayString($config->get('cta_url')) : '',
      '#element_validate' => [
        [
          'Drupal\typed_link\Plugin\Field\FieldWidget\TypedLinkWidget',
          'validateUriElement',
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Gets the URI without the 'internal:' or 'entity:' scheme.
   *
   * @param string $uri
   *   The saved uri to format.
   *
   * @return string
   *   The formatter url.
   */
  protected function getUriAsDisplayString($uri) {
    if (strpos($uri, 'internal:') !== FALSE) {
      $uri = mb_substr($uri, 9);
    }

    return $uri;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('dsjp_content.popup_config');
    $config->set('title', $form_state->getValue('title'))
      ->set('description', $form_state->getValue(['description', 'value']))
      ->set('cta_text', $form_state->getValue('cta_text'))
      ->set('cta_link', $form_state->getValue('cta_link'))
      ->set('show_popup', $form_state->getValue('show_popup'));
    $image = $form_state->getValue('image');
    if (!empty($image[0])) {
      $file = $this->entityTypeManager->getStorage('file')->load($image[0]);
      if ($file instanceof FileInterface && $file->isPermanent() == FALSE) {
        $file->setPermanent();
        $usage = $this->fileUsage->listUsage($file);
        if (empty($usage)) {
          $this->fileUsage->add($file, 'dsjp_content', 'image', $file->id());
        }
        $file->save();
        $config->set('image', $file->id());
      }
    }
    else {
      $config->set('image', '');
    }
    $config->save();
    $config = $this->configFactory()->getEditable('dsjp_content.cta_config');
    $config->set('cta_text', $form_state->getValue('cta_nav_text'))
      ->set('cta_url', $form_state->getValue('cta_nav_url'))
      ->set('show_cta_button', $form_state->getValue('show_cta_button'));
    $config->save();
    // Invalidate the cache for the block & pages cached with the popup tag.
    Cache::invalidateTags([
      'popup_cta_block',
      'cta_nav_button',
    ]);
    parent::submitForm($form, $form_state);
  }

}
