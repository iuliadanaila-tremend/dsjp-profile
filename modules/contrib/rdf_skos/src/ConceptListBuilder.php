<?php

declare(strict_types = 1);

namespace Drupal\rdf_skos;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of SKOS Concept entities.
 */
class ConceptListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('SKOS Concept ID');
    $header['prefLabel'] = $this->t('Preferred Label');
    $header['inScheme'] = $this->t('Concept Scheme');
    $header['topConceptOf'] = $this->t('Top Concept of');

    // No operations for the moment.
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\rdf_skos\Entity\ConceptInterface $entity */
    $row['id'] = $entity->id();
    $row['prefLabel'] = Link::createFromRoute(
      $entity->getPreferredLabel(),
      'entity.skos_concept.canonical',
      ['skos_concept' => $entity->id()]
    );
    $schemes = $entity->getConceptSchemes();
    $row['inScheme'] = $this->multipleLinksInColumn($schemes);
    $top_concept = $entity->topConceptOf();
    $row['topConceptOf'] = $this->multipleLinksInColumn($top_concept);

    return $row + parent::buildRow($entity);
  }

  /**
   * Builds links to multiple entities in a single column.
   *
   * @param array $entities
   *   The entities.
   *
   * @return array
   *   The links.
   */
  protected function multipleLinksInColumn(array $entities): array {
    $links = [];
    foreach ($entities as $entity) {
      $links[] = $entity->toLink()->toString();
    }

    return [
      'data' => [
        '#markup' => implode(', ', $links),
      ],
    ];
  }

}
