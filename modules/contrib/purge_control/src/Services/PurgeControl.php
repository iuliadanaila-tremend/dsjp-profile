<?php

namespace Drupal\purge_control\Services;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\purge_control\Form\PurgeControlSettings;
use Psr\Log\LoggerInterface;

/**
 * A Class for PurgeControl.
 *
 * A service containg various useful methods for controlling purge.
 */
class PurgeControl {

  /**
   * The config factory object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a PurgeControl object.
   *
   * @param Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory object.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerInterface $logger) {
    $this->config = $config_factory->getEditable(PurgeControlSettings::SETTINGS);
    $this->logger = $logger;
  }

  /**
   * Enables purging.
   */
  public function enablePurge() {
    $this->setKillSwitch(FALSE);
    $this->logger->notice('Purging is enabled');
  }

  /**
   * Enables purging if automate flag is set.
   */
  public function autoEnablePurge() {
    if ($this->isPurgeAutomated()) {
      $this->enablePurge();
    }
  }

  /**
   * Disables purging.
   */
  public function disablePurge() {
    $this->setKillSwitch(TRUE);
    $this->logger->notice('Purging is disabled');
  }

  /**
   * Disables purging if automate flag is set.
   */
  public function autoDisablePurge() {
    if ($this->isPurgeAutomated()) {
      $this->disablePurge();
    }
  }

  /**
   * Helper function to check if purge automate switch is enabled.
   *
   * @return bool
   *   Returns True / False.
   */
  public function isPurgeAutomated() {
    return (bool) $this->config->get('purge_auto_control', FALSE);
  }

  /**
   * Helper function to set value of purge automation.
   *
   * @param bool $value
   *   Value of the kill switch.
   */
  public function setAutomation(bool $value) {
    $this->config->set('purge_auto_control', $value)->save();
  }

  /**
   * Helper function to set value of purge kill switch.
   *
   * @param bool $value
   *   Value of the kill switch.
   */
  public function setKillSwitch(bool $value) {
    $this->config->set('disable_purge', $value)->save();
  }

}
