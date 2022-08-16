<?php

namespace Drupal\dsjp_coalition\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\migrate\process\SubProcess;
use Drupal\migrate\Row;

/**
 * Runs an array of arrays through its own process pipeline.
 *
 * @MigrateProcessPlugin(
 *   id = "dsj_sub_process",
 *   handle_multiples = TRUE
 * )
 */
class CustomSubProcess extends SubProcess {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    foreach ($value as &$new_value) {
      if (empty($new_value)) {
        $new_value = [
          'value' => '',
          'label' => 'Non-EU',
        ];
      }
    }
    return parent::transform($value, $migrate_executable, $row, $destination_property);
  }

}
