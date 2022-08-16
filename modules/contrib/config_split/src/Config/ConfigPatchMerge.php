<?php

declare(strict_types=1);

namespace Drupal\config_split\Config;

/**
 * The patch merging service.
 *
 * @internal This is not an API, anything here might change without notice. Use config_merge 2.x instead.
 */
class ConfigPatchMerge {

  /**
   * The sorter service.
   *
   * @var \Drupal\config_split\Config\ConfigSorter
   */
  protected $configSorter;

  /**
   * The service constructor.
   *
   * @param \Drupal\config_split\Config\ConfigSorter $configSorter
   *   The sorter.
   */
  public function __construct(ConfigSorter $configSorter) {
    $this->configSorter = $configSorter;
  }

  /**
   * Create a patch object given two arrays.
   *
   * @param array $original
   *   The original data.
   * @param array $new
   *   The new data.
   *
   * @return \Drupal\config_split\Config\ConfigPatch
   *   The patch object.
   */
  public function createPatch(array $original, array $new): ConfigPatch {
    return ConfigPatch::fromArray([
      'added' => self::diffArrayRecursive($new, $original),
      'removed' => self::diffArrayRecursive($original, $new),
    ]);
  }

  /**
   * Apply a patch to a config array.
   *
   * @param array $config
   *   The config data.
   * @param \Drupal\config_split\Config\ConfigPatch $patch
   *   The patch object.
   * @param string $name
   *   The config name to sort it correctly.
   *
   * @return array
   *   The changed config data.
   */
  public function mergePatch(array $config, ConfigPatch $patch, string $name): array {
    if ($patch->isEmpty()) {
      return $config;
    }

    $changed = self::diffArrayRecursive($config, $patch->getRemoved());
    $changed = self::mergeDeepArray([$changed, $patch->getAdded()]);

    // Make sure not to remove the dependencies key from config entities.
    if (isset($config['dependencies']) && !isset($changed['dependencies'])) {
      $changed['dependencies'] = [];
    }

    // Use the sorter to make sure the patch is applied correctly.
    $changed = $this->configSorter->sort($name, $changed);

    return $changed;
  }

  /**
   * Recursively computes the difference of arrays.
   *
   * This is an alternate version of DiffArray::diffAssocRecursive that ignores
   * the position of numeric keys when constructing a diff between two arrays.
   *
   * @param array $array1
   *   The array to compare from.
   * @param array $array2
   *   The array to compare to.
   *
   * @return array
   *   Returns an array containing all the values from array1 that are not
   *   present in array2.
   *
   * @see DiffArray::diffAssocRecursive()
   */
  private static function diffArrayRecursive(array $array1, array $array2) {
    $difference = [];
    $is_sequence = self::isSequence($array1) && self::isSequence($array2);

    foreach ($array1 as $key => $value) {
      if (is_array($value)) {
        // If this is a sequence, then we are searching for the value in $array2
        // and using the returned key instead.
        if ($is_sequence) {
          $key = array_search($value, $array2);
        }
        // If the key doesn't exist in $array2, add it to the diff and skip the
        // rest of the processing.
        if ($key === FALSE) {
          $difference[] = $value;
          continue;
        }
        if (!array_key_exists($key, $array2) || !is_array($array2[$key])) {
          // Don't preserve the existing key if this is a sequence.
          if ($is_sequence) {
            $difference[] = $value;
          }
          else {
            $difference[$key] = $value;
          }
        }
        else {
          $new_diff = self::diffArrayRecursive($value, $array2[$key]);
          if (!empty($new_diff)) {
            $difference[$key] = $new_diff;
          }
        }
      }
      else {
        // If this is an object, cast it to an array. This should never happen.
        if (is_object($value)) {
          if (method_exists($value, 'toArray')) {
            $value = $value->toArray();
          }
          else {
            $value = (array) $value;
          }
        }
        // If this is a sequence, don't preserve the key.
        if ($is_sequence) {
          if (!in_array($value, $array2, TRUE)) {
            $difference[] = $value;
          }
        }
        elseif (!array_key_exists($key, $array2) || $array2[$key] !== $value) {
          $difference[$key] = $value;
        }
      }
    }

    return $difference;
  }

  /**
   * Merges multiple arrays, recursively, and returns the merged array.
   *
   * This function is similar to NestedArray::mergeDeepArray(), with the
   * exception that it remove duplicate elements.
   *
   * @param array $arrays
   *   An arrays of arrays to merge.
   *
   * @return array
   *   The merged array.
   *
   * @see NestedArray::mergeDeepArray()
   */
  private static function mergeDeepArray(array $arrays) {
    $result = [];

    foreach ($arrays as $array) {
      $is_sequence = self::isSequence($array);
      foreach ($array as $key => $value) {
        // If this is a sequence, we are not preserving the key.
        if ($is_sequence) {
          // Don't add duplicate values.
          if (!in_array($value, $result)) {
            $result[] = $value;
          }
        }
        // Recurse when both values are arrays.
        elseif (isset($result[$key]) && is_array($result[$key]) && is_array($value)) {
          $result[$key] = self::mergeDeepArray([$result[$key], $value]);
        }
        // Otherwise, use the latter value, overriding any previous value.
        else {
          $result[$key] = $value;
        }
      }
    }

    return $result;
  }

  /**
   * Check if given array is a normal indexed array or not.
   *
   * @param array|\ArrayObject|object $value
   *   The PHP array or array-like object to check.
   *
   * @return bool
   *   True if value is a sequence array, false otherwise.
   */
  private static function isSequence($value): bool {
    // If the $value is empty, we'll err on the side of it not being a sequence.
    if (empty($value)) {
      return FALSE;
    }

    $expected_key = 0;

    foreach ($value as $key => $val) {
      if ($key !== $expected_key++) {
        return FALSE;
      }
    }

    return TRUE;
  }

}
