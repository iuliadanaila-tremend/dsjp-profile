<?php

namespace Drupal\dsjp_content\Breadcrumb;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\system\PathBasedBreadcrumbBuilder;
use Drupal\taxonomy\TermInterface;

/**
 * Provides a custom breadcrumb builder for vocabulary Article type.
 */
class ArticleBreadcrumbBuilder implements BreadcrumbBuilderInterface {
  use StringTranslationTrait;

  /**
   * The entity repository manager.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The taxonomy storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * Path based breadcrumb builder.
   *
   * @var \Drupal\system\PathBasedBreadcrumbBuilder
   */
  private $defaultBreadcrumbBuilder;

  /**
   * Constructs the TermBreadcrumbBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\system\PathBasedBreadcrumbBuilder $defaultBreadcrumbBuilder
   *   Default Breadcrumb builder.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface|null $entity_repository
   *   The entity repository.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, PathBasedBreadcrumbBuilder $defaultBreadcrumbBuilder, EntityRepositoryInterface $entity_repository = NULL) {
    $this->entityTypeManager = $entity_type_manager;
    $this->termStorage = $entity_type_manager->getStorage('taxonomy_term');
    $this->entityRepository = $entity_repository;
    $this->defaultBreadcrumbBuilder = $defaultBreadcrumbBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    if ($route_match->getRouteName() == 'entity.taxonomy_term.canonical'
      && $route_match->getParameter('taxonomy_term')
      instanceof TermInterface) {
      $parameters = $route_match->getParameters()->all();

      if (isset($parameters['taxonomy_term'])) {
        $term = $parameters['taxonomy_term'];

        if ($term->bundle() == 'dsj_type_of_article') {
          return TRUE;
        }
      }
      return FALSE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    return $this->defaultBreadcrumbBuilder->build($route_match);
  }

}
