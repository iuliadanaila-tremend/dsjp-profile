<?php

namespace Drupal\dsjp_sat\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'User assessment best result' Block.
 *
 * @Block(
 *   id = "dsj_assessment_best_result",
 *   admin_label = @Translation("User assessment best result"),
 * )
 */
class AssessmentBestResult extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The route match object.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    $instance->routeMatch = $container->get('current_route_match');
    $instance->entityTypeManager = $container->get('entity_type.manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $bestResults = [];
    $uid = $this->routeMatch->getParameter('user');
    $user = $this->entityTypeManager->getStorage('user')->load($uid);
    if ($user instanceof UserInterface) {
      $assessments = $user->get('field_dsj_assessment')->referencedEntities();
      $jsonData = [];
      foreach ($assessments as $assessment) {
        if (!empty($assessment->get('field_dsj_data')->getString())) {
          $jsonData[] = json_decode($assessment->get('field_dsj_data')->getString(), TRUE);
        }
      }
      if (!empty($jsonData)) {
        $bestResults = $this->getBestResults($jsonData);
        $bestResults = _dsjp_sat_map_skill_data($bestResults);
      }
    }
    if (!empty($bestResults)) {
      return [
        '#theme' => 'dsjp_assessment',
        '#skills' => $bestResults,
        '#overall' => max($bestResults),
      ];
    }

    return [];
  }

  /**
   * Gets best category results from the assessments taken by a user.
   *
   * @param array $jsonData
   *   The assessments data.
   *
   * @return array
   *   An array of best results, by category.
   */
  protected function getBestResults(array $jsonData) {
    $bestResults = [
      1 => 1,
      2 => 1,
      3 => 1,
      4 => 1,
      5 => 1,
    ];
    foreach ($jsonData as $data) {
      foreach ($data['resultPerSkill'] as $skill => $result) {
        if ($bestResults[$skill] < $result) {
          $bestResults[$skill] = $result;
        }
      }
    }

    return $bestResults;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), ['user:' . $this->routeMatch->getRawParameter('user')]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['url']);
  }

}
