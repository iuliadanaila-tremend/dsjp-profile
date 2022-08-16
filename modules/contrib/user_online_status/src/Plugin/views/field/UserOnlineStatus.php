<?php

namespace Drupal\user_online_status\Plugin\views\field;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\user\Entity\User;
use Drupal\user_online_status\Form\UserOnlineStatusSettingsForm;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to flag the node type.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("user_online_status")
 */
class UserOnlineStatus extends FieldPluginBase {

  /**
   * The config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a User online status field plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->config = $config_factory->get(UserOnlineStatusSettingsForm::USER_ONLINE_STATUS_SETTINGS);
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    if ($values->_entity instanceof User) {
      $user_online_status = [
        '#theme' => 'user_online_status',
        '#label' => t('Online Status'),
        '#label_display' => 'inline',
        '#uid' => $values->_entity->id(),
        '#view_mode' => 'views_field',
        '#view' => $this->view->id(),
        '#current_display' => $this->view->current_display,
        '#attached' => [
          'library' => 'user_online_status/user_online_status',
          'drupalSettings' => [
            'user_online_status' => [
              'classes' => [
                'online' => $this->config->get('css_classes_online'),
                'absent' => $this->config->get('css_classes_absent'),
                'offline' => $this->config->get('css_classes_offline'),
              ],
            ],
          ],
        ],
      ];
      return $this->renderer->render($user_online_status);
    }
  }

}
