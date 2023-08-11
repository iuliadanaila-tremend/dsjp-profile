<?php

namespace Drupal\dsjp_map\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a controller for assessment result callback.
 */
class EuropeanMapController extends ControllerBase {

  /**
   * The entity type manager property.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * EuropeanMapController constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager property.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Load country listing.
   */
  public function listing(string $country_name) {
    // Create a route for the country term listing just because we cannot create
    // alias for a view with contextual filter or put the contextual filter as
    // term name.
    $content = [];
    $country_name = str_replace('-', ' ', $country_name);
    $properties = [
      'name' => $country_name,
      'vid' => 'dsj_country',
    ];
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties($properties);
    $term = reset($terms);
    $args = [$term->id()];
    $view = Views::getView('dsj_regionally_related_content');
    if (is_object($view)) {
      $view->setArguments($args);
      $view->setDisplay('page_1');
      $view->preExecute();
      $view->execute();
      $content = $view->buildRenderable('page_1', $args);
    }
    if (!$content) {
      throw new NotFoundHttpException();
    }

    return $content;
  }

}
