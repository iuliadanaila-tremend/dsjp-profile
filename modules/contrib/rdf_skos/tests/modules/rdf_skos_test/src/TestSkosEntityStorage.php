<?php

declare(strict_types = 1);

namespace Drupal\rdf_skos_test;

use Drupal\rdf_skos\SkosEntityStorage;

/**
 * Test storage class for RDF Skos.
 */
class TestSkosEntityStorage extends SkosEntityStorage {

  /**
   * Keeps track of the IDs being loaded from storage.
   *
   * @var array
   */
  protected $storageLoads = [];

  /**
   * Keeps track of the IDs being loaded via loadMultiple.
   *
   * @var array
   */
  protected $loads = [];

  /**
   * Whether we enable static caching in the test.
   *
   * @var bool
   */
  protected $staticCacheEnabled = TRUE;

  /**
   * Returns the IDs used in the storage load calls.
   *
   * @return array
   *   The storage load calls.
   */
  public function getStorageLoadCalls(): array {
    return $this->storageLoads;
  }

  /**
   * Returns the IDs used in the loadMultiple calls.
   *
   * @return array
   *   The loadMultiple calls.
   */
  public function getLoadCalls(): array {
    return $this->loads;
  }

  /**
   * Disables the static cache.
   */
  public function disableStaticCache(): void {
    $this->staticCacheEnabled = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function doLoadMultiple(array $ids = NULL, array $graph_ids = []) {
    $this->loads[] = $ids;
    return parent::doLoadMultiple($ids, $graph_ids);
  }

  /**
   * {@inheritdoc}
   */
  protected function getFromStorage(array $ids = NULL, array $graph_ids = []): array {
    $this->storageLoads[] = $ids;
    return parent::getFromStorage($ids, $graph_ids);
  }

  /**
   * {@inheritdoc}
   */
  protected function getFromStaticCache(array $ids, array $graph_ids = []) {
    if (!$this->staticCacheEnabled) {
      return [];
    }

    return parent::getFromStaticCache($ids, $graph_ids);
  }

}
