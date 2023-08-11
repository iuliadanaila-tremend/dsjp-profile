<?php

namespace Drupal\dsjp_pledge\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a custom block for pledge's page listing.
 *
 * @Block(
 *   id = "pledge_welcome_block",
 *   admin_label = @Translation("Pledge Welcome"),
 * )
 */
class PledgeWelcomeBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service property.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    $instance->entityTypeManager = $container->get('entity_type.manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    /** @var \Drupal\views\ViewExecutable $executable */
    $executable = $this->entityTypeManager->getStorage('view')->load('pledges')->getExecutable();
    $display = $executable->executeDisplay('page');

    if (!empty($this->configuration["text_content"]["value"]) && !empty($display['#rows'])) {
      $build = [
        '#theme' => 'pledge_welcome',
        '#content' => ['#markup' => $this->configuration['text_content']['value']],
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['text_content'] = [
      '#type' => 'text_format',
      '#format' => $this->configuration['text_content']['value']['format'] ?? 'plain_text',
      '#title' => $this->t('Text block'),
      '#default_value' => $this->configuration['text_content']['value'] ?? '',
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['text_content'] = $form_state->getValue('text_content');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), [
      'languages:language_content',
      'languages:language_interface',
      'url',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), [
      'config:field.storage.node.body',
      'node_list',
    ]);
  }

}
