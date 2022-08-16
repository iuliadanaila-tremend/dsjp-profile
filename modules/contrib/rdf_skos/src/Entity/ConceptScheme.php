<?php

declare(strict_types = 1);

namespace Drupal\rdf_skos\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the SKOS Concept Scheme entity.
 *
 * @ContentEntityType(
 *   id = "skos_concept_scheme",
 *   label = @Translation("SKOS Concept Scheme"),
 *   handlers = {
 *     "storage" = "Drupal\rdf_skos\SkosEntityStorage",
 *     "view_builder" = "Drupal\rdf_skos\ConceptSchemeViewBuilder",
 *     "list_builder" = "Drupal\rdf_skos\ConceptSchemeListBuilder",
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler",
 *     "access" = "Drupal\rdf_skos\ConceptSchemeAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\rdf_skos\ConceptSchemeHtmlRouteProvider",
 *     },
 *   },
 *   base_table = null,
 *   translatable = TRUE,
 *   admin_permission = "administer skos concept scheme entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "id",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/skos_concept_scheme/{skos_concept_scheme}",
 *     "collection" = "/admin/structure/skos_concept_scheme",
 *   }
 * )
 */
class ConceptScheme extends ContentEntityBase implements ConceptSchemeInterface {

  /**
   * {@inheritdoc}
   */
  public function getTitle(): string {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle(string $title): ConceptSchemeInterface {
    $this->set('title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTopConcepts(): array {
    return $this->get('has_top_concept')->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Change the ID field from integer to string.
    $fields['id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ID'))
      ->setDescription(t('The SKOS Concept ID.'))
      ->setReadOnly(TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The title of the Concept Scheme.'));

    $fields['has_top_concept'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Has Top Concept'))
      ->setDescription(t('The Concepts that are top level in this scheme.'))
      ->setSetting('target_type', 'skos_concept')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('handler', 'default');

    return $fields;
  }

}
