<?php

namespace Drupal\dsjp_coalition;

/**
 * Provides an interface for different sources migrations.
 */
interface MigrationServiceInterface {

  /**
   * Gets the base url of the coalition.
   *
   * @param array $data
   *   The coalition group data.
   *
   * @return string
   *   Returns the base url.
   */
  public function getBaseUrl(array $data);

  /**
   * Gets the total number of pages available.
   *
   * @param array $data
   *   The coalition group data.
   *
   * @return int
   *   Returns the number of pages available.
   */
  public function getTotalPages(array $data);

  /**
   * Fetches data from the designated endpoints.
   *
   * @param int $page
   *   Page number.
   * @param array $data
   *   The coalition group data.
   */
  public function fetchData($page, array $data);

  /**
   * Gets the base path of a url.
   *
   * @param string $url
   *   The url to split.
   *
   * @return mixed
   *   The basepath of the url.
   */
  public function getBasePath($url);

}
