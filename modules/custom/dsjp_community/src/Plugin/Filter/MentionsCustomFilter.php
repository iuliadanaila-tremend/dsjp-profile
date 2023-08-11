<?php

namespace Drupal\dsjp_community\Plugin\Filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\Entity\FilterFormat;
use Drupal\mentions\MentionsPluginInterface;
use Drupal\mentions\Plugin\Filter\MentionsFilter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FilterMentions.
 *
 * @package Drupal\dsjp_community\Plugin\Filter
 *
 * @Filter(
 *   id = "dsj_filter_mentions",
 *   title = @Translation("DSJ Mentions Filter"),
 *   description = @Translation("Configure via the <a href='/admin/structure/mentions'>Mention types</a> page."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_HTML_RESTRICTOR,
 *   settings = {
 *     "dsj_mentions_filter" = {}
 *   },
 *   weight = -10
 * )
 */
class MentionsCustomFilter extends MentionsFilter {

  /**
   * Array with all mention Types.
   *
   * @var array
   */
  private $mentionTypes = [];

  /**
   * Array storing the input settings.
   *
   * @var array
   */
  private $inputSettings = [];

  /**
   * Array storing output settings.
   *
   * @var array
   */
  private $outputSettings = [];

  /**
   * The text format.
   *
   * @var string
   */
  private $textFormat;

  /**
   * The config factory service property.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->config = $container->get('config.factory');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $configs = $this->config->listAll('mentions.mentions_type');
    $candidate_entitytypes = [];

    foreach ($configs as $config) {
      $mentions_name = str_replace('mentions.mentions_type.', '', $config);
      $candidate_entitytypes[$mentions_name] = $mentions_name;
    }

    if (count($candidate_entitytypes) == 0) {
      return NULL;
    }
    $form['dsj_mentions_filter'] = [
      '#type' => 'checkboxes',
      '#options' => $candidate_entitytypes,
      '#default_value' => $this->settings['dsj_mentions_filter'],
      '#title' => $this->t('Mentions types'),
    ];
    return $form;
  }

  /**
   * Checks if a textFormat filter should be applied.
   *
   * @return bool
   *   TRUE if filter should applied, otherwise FALSE.
   */
  public function shouldApplyFilter(): bool {
    if ($this->checkMentionTypes()) {
      return TRUE;
    }
    elseif ($this->textFormat && ($format = FilterFormat::load($this->textFormat))) {
      $filters = $format->get('filters');
      if (!empty($filters['dsj_filter_mentions']['status'])) {
        $this->settings = $filters['dsj_filter_mentions']['settings'];
        return $this->checkMentionTypes();
      }
    }
    return FALSE;
  }

  /**
   * Checks if there are mentionTypes.
   *
   * @return bool
   *   TRUE if there are mentionTypes, otherwise FALSE.
   */
  public function checkMentionTypes() {
    $settings = $this->settings;
    if (isset($settings['dsj_mentions_filter'])) {
      $configs = $this->config->listAll('mentions.mentions_type');

      foreach ($configs as $config) {
        $this->mentionTypes[] = str_replace('mentions.mentions_type.', '', $config);
      }
    }
    return !empty($this->mentionTypes);
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
  public function getMentions($text): array {
    $mentions = [];
    $config_names = $this->mentionTypes;
    foreach ($config_names as $config_name) {
      $settings = $this->config->get('mentions.mentions_type.' . $config_name);
      $input_settings = [
        'prefix' => $settings->get('input.prefix'),
        'suffix' => $settings->get('input.suffix'),
        'entity_type' => $settings->get('input.entity_type'),
        'value' => $settings->get('input.inputvalue'),
      ];
      $this->inputSettings[$config_name] = $input_settings;

      if (!isset($input_settings['entity_type']) || empty($this->settings['dsj_mentions_filter'][$config_name])) {
        continue;
      }

      $output_settings = [
        'value' => $settings->get('output.outputvalue'),
        'renderlink' => (bool) $settings->get('output.renderlink'),
        'rendertextbox' => $settings->get('output.renderlinktextbox'),
      ];
      $this->outputSettings[$config_name] = $output_settings;
      $mention_type = $settings->get('mention_type');
      $mention = $this->mentionsManager->createInstance($mention_type);

      if ($mention instanceof MentionsPluginInterface && !empty($text)) {

        $pattern = '/(?:' . preg_quote($input_settings['prefix']) . ')([\w]+[( )[\w]+]? [(][\d]+[)])' . preg_quote($input_settings['suffix']) . '/';
        preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
          // Get the user name based on id.
          $match = $this->getUpdatedMatchArray($match);
          $target = $mention->targetCallback($match[1], $input_settings);
          if ($target !== FALSE) {
            $mentions[$match[0]] = [
              'type' => $mention_type,
              'source' => [
                'string' => $match[0],
                'match' => $match[1],
              ],
              'target' => $target,
              'config_name' => $config_name,
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
  public function filterMentions($text): string {
    $mentions = $this->getMentions($text);
    foreach ($mentions as $match) {
      $mention = $this->mentionsManager->createInstance($match['type']);

      if ($mention instanceof MentionsPluginInterface) {
        $output_settings = $this->outputSettings[$match['config_name']];
        $output = $mention->outputCallback($match, $output_settings);
        $build = [
          '#theme' => 'mention_link',
          '#mention_id' => $match['target']['entity_id'],
          '#link' => $output['link'],
          '#render_link' => $output_settings['renderlink'],
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
  public function setTextFormat($text_format) {
    $this->textFormat = $text_format;
  }

  /**
   * Helper function that loads the user and sets the username.
   *
   * @param array $match
   *   The match array from preg_replace.
   *
   * @return array
   *   The same array with the matched value replaced.
   */
  protected function getUpdatedMatchArray(array $match) {
    // Get the uid from inside the parantheses.
    $paranthesesArray = explode('(', $match[1]);
    if ($paranthesesArray[1] && !empty($paranthesesArray[1])) {
      $uidArray = explode(')', $paranthesesArray[1]);
      if ($uidArray[0] && is_numeric($uidArray[0])) {
        // Load the user and get the username.
        $user = $this->entityTypeManager->getStorage('user')->load($uidArray[0]);
        if ($user) {
          $userName = $user->getAccountName();
          // Replace the old match with the username
          // so it can trigger the mention.
          if (!empty($userName)) {
            $match[1] = $userName;
          }
        }
      }
    }
    return $match;
  }

}
