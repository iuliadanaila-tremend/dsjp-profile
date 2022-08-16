<?php

declare(strict_types = 1);

namespace Drupal\rdf_skos;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of SKOS Concept Scheme entities.
 */
class ConceptSchemeListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('SKOS Concept Scheme ID');
    $header['name'] = $this->t('Title');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\rdf_skos\Entity\ConceptScheme $entity */
    $row['id'] = $entity->id();
    $row['prefLabel'] = Link::createFromRoute(
      $entity->getTitle(),
      'entity.skos_concept_scheme.canonical',
      ['skos_concept_scheme' => $entity->id()]
    );

    return $row + parent::buildRow($entity);
  }

}
