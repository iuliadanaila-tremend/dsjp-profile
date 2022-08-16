<?php

declare(strict_types = 1);

namespace Drupal\rdf_skos;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Theme\Registry;
use Drupal\rdf_skos\Entity\ConceptInterface;
use Drupal\rdf_skos\Entity\ConceptSchemeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * View builder handler for the Concept Scheme entities.
 */
class ConceptSchemeViewBuilder extends EntityViewBuilder {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ConceptSchemeViewBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Theme\Registry $theme_registry
   *   The theme registry.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityRepositoryInterface $entity_repository, LanguageManagerInterface $language_manager, Registry $theme_registry, EntityDisplayRepositoryInterface $entity_display_repository, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($entity_type, $entity_repository, $language_manager, $theme_registry, $entity_display_repository);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.repository'),
      $container->get('language_manager'),
      $container->get('theme.registry'),
      $container->get('entity_display.repository'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildMultiple(array $build_list): array {
    $build_list = parent::buildMultiple($build_list);

    // Render the top level concepts.
    foreach ($build_list as &$item) {
      if ($item['#view_mode'] !== 'full') {
        continue;
      }

      /** @var \Drupal\rdf_skos\Entity\ConceptSchemeInterface $concept_scheme */
      $concept_scheme = $item['#skos_concept_scheme'];
      $top = $this->getTopConcepts($concept_scheme);
      if (empty($top)) {
        continue;
      }

      $items = [];
      foreach ($top as $concept) {
        $items[] = $concept->toLink();
      }

      $item[] = [
        '#theme' => 'item_list',
        '#items' => $items,
        '#title' => $this->t('Top level concepts'),
      ];

      $item[] = [
        '#type' => 'pager',
      ];
    }

    return $build_list;
  }

  /**
   * Returns the Concepts that are at the top level. Checking both directions.
   *
   * @param \Drupal\rdf_skos\Entity\ConceptSchemeInterface $concept_scheme
   *   The Concept Scheme.
   *
   * @return array|\Drupal\rdf_skos\Entity\ConceptInterface[]
   *   The Concept.
   */
  protected function getTopConcepts(ConceptSchemeInterface $concept_scheme): array {
    $concepts = $concept_scheme->getTopConcepts();
    $ids = $this->entityTypeManager->getStorage('skos_concept')->getQuery()
      ->condition('top_concept_of', $concept_scheme->id())
      ->pager(30)
      ->execute();

    if (!$ids) {
      return $concepts;
    }

    $concepts = array_filter($concepts, function (ConceptInterface $concept) use ($ids) {
      return !in_array($concept->id(), $ids);
    });

    $concepts = array_merge($concepts, $this->entityTypeManager->getStorage('skos_concept')->loadMultiple($ids));
    return $concepts;
  }

}
