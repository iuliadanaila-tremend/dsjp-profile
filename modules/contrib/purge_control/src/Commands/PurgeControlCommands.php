<?php

namespace Drupal\purge_control\Commands;

use Drush\Commands\DrushCommands;
use Drupal\purge_control\Services\PurgeControl;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class PurgeControlCommands extends DrushCommands {

  /**
   * PurgeControl service.
   *
   * @var Drupal\purge_control\Services\PurgeControl
   */
  protected $purgeControl;

  /**
   * Constricts PurgeControlCommands object.
   */
  public function __construct(PurgeControl $purge_control) {
    parent::__construct();
    $this->purgeControl = $purge_control;
  }

  /**
   * Controls the enabling and disabling of purge on the site.
   *
   * @param string $op
   *   Operation to perform, possible values are enable-purge(enp),
   *   disable-purge(disp), enable-automation(ena), disable-automation(disa).
   *
   * @usage purge-control enable-purge
   *   Enables purging.
   * @usage purge-control disable-purge
   *   Disables purging.
   * @usage purge-control enable-automation
   *   Enables automated purge control.
   * @usage purge-control disable-automation
   *   Disables automated purge control.
   *
   * @command purge-control
   * @aliases pc
   */
  public function purgeControl(string $op) {
    if ($op == 'enable-purge' || $op == 'enp') {
      $this->purgeControl->enablePurge();
      $this->logger()->success(dt('Purging is enabled.'));
    }
    elseif ($op == 'disable-purge' || $op == 'disp') {
      $this->purgeControl->disablePurge();
      $this->logger()->success(dt('Purging is disabled.'));
    }
    elseif ($op == 'enable-automation' || $op == 'ena') {
      $this->purgeControl->setAutomation(TRUE);
      $this->logger()->success(dt('Automated purge control is enabled.'));
    }
    elseif ($op == 'disable-automation' || $op == 'disa') {
      $this->purgeControl->setAutomation(FALSE);
      $this->logger()->success(dt('Automated purge control is disabled.'));
    }
    else {
      $this->logger()->error(dt('Invalid operation.'));
    }
  }

}
