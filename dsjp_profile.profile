<?php

/**
 * @file
 * Profile file.
 */

declare(strict_types = 1);

/**
 * Implements hook_install_tasks_alter().
 */
function dsjp_profile_install_tasks_alter(&$tasks, $install_state): void {
  // Do not download UI and config translations at installation time.
  unset($tasks['install_config_download_translations']);
  unset($tasks['install_import_translations']);
  unset($tasks['install_finish_translations']);
  unset($tasks['install_configure_form']);
}
