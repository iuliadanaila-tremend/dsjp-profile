CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * MODULE FEATURES
 * Configuration
 * Maintainers

INTRODUCTION
------------

Provides configuration to enable and disable purging along with drush commands.

REQUIREMENTS
------------

* Purge module. See: https://www.drupal.org/project/purge.

INSTALLATION
------------

Refer the Purge documentation to get more details on how to setup the purging.
https://git.drupalcode.org/project/purge/-/blob/8.x-3.x/README.md

MODULE FEATURES
---------------

* Provides configuration to disable and enable purging functionality.
* Provides configuration to automatically enable purging if it is disabled.
* Provides Drush commands to control purging on the site.
* Available Drush commands:
```
drush pc —help # help around the command.

drush pc enp # Enables purging.

drush pc disp # Disables purging.

drush pc ena # Enables automation.

drush pc disa # Disables Automation.
```

CONFIGURATION
-------------

 * Go to `admin/config/development/performance/purge/purge-control`.
 * Automate control of enabling and disabling of purge: When this setting is
 enabled, then, during cron run, if the purging is disabled then it will be re-enabled.
 * Disable purging: If this setting is enabled then purging will be disabled.

USAGE EXAMPLES
--------------

* Maintaing CDN cache during production releases, so that no direct traffic is
  reaching the site. Below are the steps to perform the mentioned setup:
  - Pause the purging of the site before the release using following commands:
  ```
  drush pc disa
  drush pc disp
  ```
  - After the release, restart the purging, In case of big release everything
  invalidation can be added in the queue so that the whole site is purged in a
  single request.
  ```
  # Enable purging
  drush pc ena
  drush pc enp

  # Trigger everything invalidation
  drush pqe # Empty the queue
  drush pqa everything # Adds everything invalidation object in the queue
  drush pqw # Triggers purge process.
  ```
* For a long running drush command which causes lots of invalidation(like migration),
Use the purge control service inside pre and post commit hooks, So that before
execution the purging is stopped and after execution purging is restarted.
Code example:
Inside pre-command hook of drush add below code:
```
  /**
   * Disable purging at start of process.
   *
   * @hook pre-command <command-name>
   */
  public function preCommand(CommandData $commandData) {
    // Make sure to inject service via dependency injection here instead.
    \Drupal::service('purge_control.purge_control')->autoDisablePurge();
  }

  /**
   * Enabled purging at the end of process.
   *
   * @hook post-command <command-name>
   */
  public function postCommand($result, CommandData $commandData) {
    \Drupal::service('purge_control.purge_control')->autoEnablePurge();
  }
```
In above code snippet example we are using autoDisablePurge and autoEnablePurge
methods for triggering our process which ensures that if automation flag is set
then only this process will take place.
Learn more about drush hooks at https://github.com/consolidation/annotated-command#hooks

MAINTAINERS
-----------

Current maintainers:
 * Hemant Gupta (https://www.drupal.org/u/guptahemant)
 * Graham Arrowsmith (https://www.drupal.org/u/arrowsmith)
