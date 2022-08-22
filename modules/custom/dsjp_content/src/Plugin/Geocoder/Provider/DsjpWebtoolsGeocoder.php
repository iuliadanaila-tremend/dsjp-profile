<?php

namespace Drupal\dsjp_content\Plugin\Geocoder\Provider;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\geocoder\ProviderUsingHandlerWithAdapterBase;
use Drupal\geofield\GeoPHP\GeoPHPInterface;
use Http\Client\HttpClient;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Ec Webtools Geocoder provider plugin.
 *
 * @GeocoderProvider(
 *   id = "DsjpWebtoolsGeocoder",
 *   name = "DsjpWebtoolsGeocoder",
 *   handler = "\OpenEuropa\Provider\WebtoolsGeocoding\WebtoolsGeocoding"
 * )
 */
class DsjpWebtoolsGeocoder extends ProviderUsingHandlerWithAdapterBase {

  /**
   * Geophp wrapper.
   *
   * @var \Drupal\geofield\GeoPHP\GeoPHPInterface
   */
  private $geophpWrapper;

  /**
   * Constructs a geocoder provider plugin object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend used to cache geocoding data.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The Drupal language manager service.
   * @param \Http\Client\HttpClient $http_adapter
   *   The HTTP adapter.
   * @param \Drupal\geofield\GeoPHP\GeoPHPInterface $geophpWrapper
   *   Geophp wrapper.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              ConfigFactoryInterface $config_factory,
                              CacheBackendInterface $cache_backend,
                              LanguageManagerInterface $language_manager,
                              HttpClient $http_adapter,
                              GeoPHPInterface $geophpWrapper
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $config_factory, $cache_backend, $language_manager, $http_adapter);

    $this->geophpWrapper = $geophpWrapper;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('cache.geocoder'),
      $container->get('language_manager'),
      $container->get('geocoder.http_adapter'),
      $container->get('geofield.geophp')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function doGeocode($source) {

    try {
      $chars = [" ", ","];
      $query = trim(str_replace($chars, '+', $source));
      $url = "https://europa.eu/webtools/rest/geocoding/?address=" . $query;
      $request = \Drupal::httpClient()
        ->get($url, ['headers' => ['Accept' => 'application/json']]);

      $response = json_decode($request->getBody());
      $data = $response->geocodingRequestsCollection[0];

      if ($data->responseCode !== 200) {
        $args = [
          '@code' => $response->responseCode,
          '@error' => $response->responseMessage,
        ];
        $message = $this->t('HTTP request to Webtools Geocoder API failed.\nCode: @code\nError: @error', $args);
        \Drupal::logger('ec_geocoder')->error($message);
      }

      if ($response->addressesCount == 0) {
        $args = ['@status' => $data->status, '@address' => $source];
        $message = $this->t('Webtools Geocoder API returned zero results on @address status.\nStatus: @status', $args);
        \Drupal::logger('ec_geocoder')->notice($message);
      }
      elseif (isset($data->responseMessage) && $data->responseMessage != 'OK') {
        $args = ['@status' => $data->responseMessage];
        $message = $this->t('Webtools Geocoder API returned bad status.\nStatus: @status', $args);
        \Drupal::logger('ec_geocoder')->warning($message);
      }

      $geometries = [];

      if (isset($data->result->features[0]->geometry)) {
        $item = json_encode($data->result->features[0]->geometry);
        $geometries[] = $this->geophpWrapper->load($item, 'json');
      }

      if (empty($geometries)) {
        return;
      }
      $geometry = array_shift($geometries);

      return $geometry;
    }
    catch (\Exception $e) {
      // Just rethrow the exception, the geocoder widget handles it.
      throw $e;
    }
  }

}
