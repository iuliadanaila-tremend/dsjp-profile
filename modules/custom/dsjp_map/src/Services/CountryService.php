<?php

namespace Drupal\dsjp_map\Services;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;
use Drupal\views\Views;

/**
 * Provides helper methods for the map and countries.
 */
class CountryService {

  use StringTranslationTrait;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $etm;

  /**
   * DrupalMigrationService constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   The logger channel.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(LoggerChannelFactoryInterface $loggerChannelFactory, EntityTypeManagerInterface $entity_type_manager) {
    $this->logger = $loggerChannelFactory;
    $this->etm = $entity_type_manager;
  }

  /**
   * Returns the countries map.
   *
   * @return array
   *   Countries map array.
   */
  public function getCountriesMap() {
    return [
      'BEL' => 'BE',
      'BGR' => 'BG',
      'BIH' => 'BA',
      'BLR' => 'BY',
      'SRB' => 'RS',
      'ROU' => 'RO',
      'GRC' => 'EL',
      'GBR' => 'UK',
      'HRV' => 'HR',
      'HUN' => 'HU',
      'PRT' => 'PT-',
      'POL' => 'PL',
      'EST' => 'EE',
      'ITA' => 'IT',
      'ESP' => 'ES-',
      'MNE' => 'ME',
      'MDA' => 'MD',
      'MKD' => 'MK',
      'MLT' => 'MT',
      'FRA' => 'FR-',
      'FIN' => 'FI',
      'NLD' => 'NL',
      'NOR' => 'NO',
      'CHE' => 'CH',
      'CZE' => 'CZ',
      'SVK' => 'SK',
      'SVN' => 'SI',
      'SWE' => 'SE',
      'DNK' => 'DK',
      'DEU' => 'DE',
      'LVA' => 'LV',
      'LTU' => 'LT',
      'LUX' => 'LU',
      'ISL' => 'IS',
      'ALB' => 'AL',
      'AUT' => 'AT',
      'ALA' => 'AX',
      'IRL' => 'IE',
      'UKR' => 'UA',
      'CYP' => 'CY',
      'UK' => 'UK',
    ];
  }

  /**
   * Returns the progress widget colors.
   *
   * @return array
   *   Colors array.
   */
  public function getProgressColors() {
    return [
      "#4073AF",
      "#004494",
      "#003D84",
      "#003776",
    ];
  }

  /**
   * Returns the average if found set in the map paragraph.
   *
   * @return array
   *   Countries map array.
   */
  public function getMapAverage() {
    if ($map = $this->getMapParagraph()) {
      if ($map->get('field_dsj_eu_average')->value) {
        return $map->get('field_dsj_eu_average')->value;
      }
    }
    return FALSE;
  }

  /**
   * Returns all the country pages.
   *
   * @return array
   *   Array of country nodes.
   */
  public function getAllCountryPages() {
    $nids = $this->etm->getStorage('node')->getQuery()
      ->condition('type', 'dsj_country_page')
      ->accessCheck(TRUE)
      ->execute();
    return $this->etm->getStorage('node')->loadMultiple($nids);
  }

  /**
   * Returns the score intervals needed for the progress widget.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $components
   *   Entities that have score.
   * @param int $override_avg
   *   In case avg is not calculated, but stored somewhere.
   *
   * @return array
   *   Score intervals.
   */
  public function getScoresIntervals(array $components, $override_avg = NULL) {
    [
      $minScore,
      $maxScore,
      $chunkSize,
      $intervals,
      $avg,
    ] = dsjp_map_get_scores_interval($components, $override_avg);

    return [
      'minScore' => $minScore,
      'maxScore' => $maxScore,
      'chunkSize' => $chunkSize,
      'intervals' => $intervals,
      'avg' => $avg,
    ];
  }

  /**
   * Returns the map paragraph (last one).
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Paragraph of map.
   */
  public function getMapParagraph() {
    // Cross fingers to be the right one.
    $pids = $this->etm->getStorage('paragraph')->getQuery()
      ->condition('type', 'dsj_map')
      ->accessCheck(TRUE)
      ->execute();
    if (!empty($pids)) {
      $maps = $this->etm->getStorage('paragraph')->loadMultiple($pids);
      return end($maps);
    }
    return FALSE;
  }

  /**
   * Get the block of the view regionally related content.
   *
   * @param int $tid
   *   Term id of the Country.
   *
   * @return array
   *   Rendered array of view.
   */
  public function getRegionRelatedContentView($tid) {
    $view = Views::getView('dsj_regionally_related_content');
    $view->setArguments([$tid]);
    $view->setDisplay('block');
    $view->preExecute();
    $view->execute();
    if (!empty($view->result)) {
      return $view->buildRenderable('block', [$tid]);
    }
    return FALSE;
  }

  /**
   * Creates a header for the regionally related content.
   *
   * @param \Drupal\taxonomy\Term $country_term
   *   Term of the country.
   *
   * @return array
   *   Renderable array of the view header.
   */
  public function getRegionRelatedContentHeader(Term $country_term) {
    $country_name = mb_strtolower(str_replace(' ', '-', $country_term->label()));
    $listing_url = Url::fromRoute('dsj_map.country_listing', ['country_name' => $country_name]);
    return [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['regional-related-header ecl-col-m-12'],
      ],
      '#prefix' => '<div class="ecl-row">',
      '#suffix' => '</div>',
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $this->t('All content from @country', ['@country' => $country_term->label()]),
        '#attributes' => [
          'class' => ['regional-related-header-title'],
        ],
      ],
      'read_more' => [
        '#type' => 'more_link',
        '#title' => $this->t('See more'),
        '#url' => $listing_url,
      ],
    ];
  }

}
