<?php

namespace Drupal\dsjp_coalition\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Transform a non-associative array into a associative one.
 *
 * @MigrateProcessPlugin(
 *   id = "deepen",
 *   handle_multiples = TRUE
 * )
 *
 * @code
 *   field_entity_reference:
 *     plugin: deepen
 *     keyname: value
 *     source: tags
 * @endcode
 */
class Deepen extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $keyname = $this->configuration['keyname'] ?? 'value';
    if (is_array($value) || $value instanceof \Traversable) {
      $result = [];
      foreach ($value as $sub_value) {
        $result[] = [$keyname => $sub_value];
      }
      return $result;
    }
    elseif (!empty($value)) {
      return [
        [
          $keyname => $value,
        ],
      ];
    }

    return [];
  }

}
