<?php

declare(strict_types = 1);

namespace Drupal\edsjp_theme_helper\Loader;

use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerTrait;
use OpenEuropa\Twig\Loader\EuropaComponentLibraryLoader;

/**
 * Load EDSJP components Twig templates.
 */
class EdsjpComponentLibraryLoader extends EuropaComponentLibraryLoader {

  use MessengerTrait;

  /**
   * Logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct($namespaces, $root, $themes, $directory, ThemeHandlerInterface $theme_handler, LoggerChannelFactoryInterface $logger_factory) {
    // Make sure the theme exists before getting its path.
    // This is necessary when the "oe_theme_helper" module is enabled before
    // the theme is or the theme is disabled and the "oe_theme_helper" is not.
    $paths = [];
    foreach ($themes as $theme) {
      if ($theme_handler->themeExists($theme)) {
        $themePath = $theme_handler->getTheme($theme)->getPath();
        $paths[] = $themePath . DIRECTORY_SEPARATOR . $directory;
      }
    }

    $this->logger = $logger_factory->get('ecl');
    parent::__construct($namespaces, $paths, $root, 'twig-component-', 'ecl-');
  }

}
