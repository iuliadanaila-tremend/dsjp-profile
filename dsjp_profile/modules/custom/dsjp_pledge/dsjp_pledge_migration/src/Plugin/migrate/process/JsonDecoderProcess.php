<?php

namespace Drupal\dsjp_pledge_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\migrate\process\Get;
use Drupal\migrate\Row;

/**
 * Decodes a json string.
 *
 * @MigrateProcessPlugin(
 *   id = "dsj_json_decoder_process"
 * )
 *
 * @code
 *   field_name:
 *     plugin: dsj_json_decoder_process
 *     key: value
 *     source: json_string
 * @endcode
 */
class JsonDecoderProcess extends Get {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!empty($value)) {
      $key = $this->configuration['key'];
      $array = json_decode($value, TRUE);
      return $array[$key] ?: '';
    }

    return '';
  }

}
