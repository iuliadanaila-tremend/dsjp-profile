<?php

namespace Drupal\mentions\Plugin\Filter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\mentions\MentionsPluginInterface;
use Drupal\mentions\MentionsPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FilterMentions.
 *
 * @package Drupal\mentions\Plugin\Filter
 *
 * @Filter(
 *   id = "filter_mentions",
 *   title = @Translation("Mentions Filter"),
 *   description = @Translation("Configure via the <a
 *   href='/admin/structure/mentions'>Mention types</a> page."), type =
 *   Drupal\filter\Plugin\FilterInterface::TYPE_HTML_RESTRICTOR, settings = {
 *     "mentions_filter" = {}
 *   },
 *   weight = -10
 * )
 */
class MentionsFilter extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * @var \Drupal\mentions\MentionsPluginManager
   */
  protected $mentionsManager;

  /**
   * MentionsFilter constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, RendererInterface $render, MentionsPluginManager $mentions_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->mentionsManager = $mentions_manager;
    $this->renderer = $render;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('renderer'),
      $container->get('plugin.manager.mentions')
    );
  }

  /**
   * Returns the settings.
   *
   * @return array
   *   A list of settings.
   */
  public function getSettings() {
    return $this->settings;
  }

  /**
   * Checks if a textFormat filter should be applied.
   *
   * @return bool
   *   TRUE if filter should applied, otherwise FALSE.
   */
  public function shouldApplyFilter() {
    return !empty($this->settings['mentions_filter']);
  }

  /**
   * Gets the mentions in text.
   *
   * @param string $text
   *   The text to find mentions in.
   *
   * @return array
   *   A list of mentions.
   */
  public function getMentions($text) {
    $mentions = [];
    $enabledTypes = $this->entityTypeManager->getStorage('mentions_type')->loadMultiple($this->settings['mentions_filter']);
    /** @var \Drupal\mentions\Entity\MentionsTypeInterface $enabledType */
    foreach ($enabledTypes as $enabledType) {
      $settings = $enabledType->getInputSettings();
      $input_settings = [
        'prefix' => !empty($settings['prefix']) ? $settings['prefix'] : '',
        'suffix' => !empty($settings['suffix']) ? $settings['suffix'] : '',
        'entity_type' => !empty($settings['entity_type']) ? $settings['entity_type'] : '',
        'value' => !empty($settings['inputvalue']) ? $settings['inputvalue'] : '',
      ];

      if (!isset($input_settings['entity_type']) || empty($this->settings['mentions_filter'][$enabledType->id()])) {
        continue;
      }
      $settings = $enabledType->getOutputSettings();
      $output_settings = [
        'value' => !empty($settings['outputvalue']) ? $settings['outputvalue'] : '',
        'renderlink' => !empty($settings['renderlink']),
        'rendertextbox' => !empty($settings['renderlinktextbox']) ? $settings['renderlinktextbox'] : '',
      ];
      $mention_type = $enabledType->mentionType();
      $mention = $this->mentionsManager->createInstance($mention_type);

      if ($mention instanceof MentionsPluginInterface) {
        $pattern = '/(?:' . preg_quote($input_settings['prefix']) . ')([a-zA-Z0-9_]+)' . preg_quote($input_settings['suffix']) . '/';
        preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
          $target = $mention->targetCallback($match[1], $input_settings);

          if ($target !== FALSE) {
            $mentions[$match[0]] = [
              'type' => $mention_type,
              'source' => [
                'string' => $match[0],
                'match' => $match[1],
              ],
              'target' => $target,
              'config_name' => $enabledType->id(),
              'output_settings' => $output_settings,
            ];
          }
        }
      }
    }

    return $mentions;
  }

  /**
   * Filters mentions in a text.
   *
   * @param string $text
   *   The text containing the possible mentions.
   *
   * @return string
   *   The processed text.
   */
  public function filterMentions($text) {
    $mentions = $this->getMentions($text);

    foreach ($mentions as $match) {
      $mention = $this->mentionsManager->createInstance($match['type']);

      if ($mention instanceof MentionsPluginInterface) {
        $output = $mention->outputCallback($match, $match['output_settings']);
        $build = [
          '#theme' => 'mention_link',
          '#mention_id' => $match['target']['entity_id'],
          '#link' => $output['link'],
          '#render_link' => $match['output_settings']['renderlink'],
          '#render_value' => $output['value'],
        ];
        $mentions = $this->renderer->render($build);
        $text = str_replace($match['source']['string'], $mentions, $text);
      }
    }

    return $text;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    if ($this->shouldApplyFilter()) {
      $text = $this->filterMentions($text);
      return new FilterProcessResult($text);
    }

    return new FilterProcessResult($text);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $types = $this->entityTypeManager->getStorage('mentions_type')->loadMultiple();
    $candidate_entitytypes = [];

    foreach ($types as $type) {
      $candidate_entitytypes[$type->id()] = $type->get('name');
    }

    if (count($candidate_entitytypes) == 0) {
      return NULL;
    }

    $form['mentions_filter'] = [
      '#type' => 'checkboxes',
      '#options' => $candidate_entitytypes,
      '#default_value' => $this->settings['mentions_filter'],
      '#title' => $this->t('Mentions types'),
    ];

    return $form;
  }

}
