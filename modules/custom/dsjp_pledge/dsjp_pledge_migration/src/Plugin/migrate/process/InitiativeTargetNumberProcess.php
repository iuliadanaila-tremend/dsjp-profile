<?php

namespace Drupal\dsjp_pledge_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\migrate\process\Get;
use Drupal\migrate\Row;

/**
 * Transform target number json into multiple values.
 *
 * @MigrateProcessPlugin(
 *   id = "dsj_target_number_process",
 *   handle_multiples = TRUE
 * )
 */
class InitiativeTargetNumberProcess extends Get {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $newValues = [];
    if (!empty($value)) {
      $data = json_decode($value, TRUE);
      // @todo $pillar
      $pillar = $row->getSourceProperty('field_pillar');
      $map1 = [
        'ict_professionals' => [
          'ict_entry',
          'ict_experience',
          'ict_manager',
          'ict_director',
          'ict_all',
        ],
        'education' => [
          'edu_early_year',
          'edu_primary',
          'edu_secondary',
          'edu_tertiary',
          'edu_vet',
          'edu_parents',
          'edu_teacher',
          'edu_all',
        ],
        'labour_force' => [
          'employed',
          'unemployed',
          'retired',
          'internal_employees',
          'refugees',
          'for_all',
        ],
        'all' => [
          'p_citizen_all',
        ],
      ];
      foreach ($data as $key => $values) {
        if (in_array($key, $map1[$pillar])) {
          $newValues[] = [
            'status' => 1,
            'number' => reset($values),
          ];
        }
        else {
          $newValues[] = [
            'status' => 1,
            'value' => $key,
            'number' => $values,
          ];
        }
      }
    }
    return $newValues;
  }

}
