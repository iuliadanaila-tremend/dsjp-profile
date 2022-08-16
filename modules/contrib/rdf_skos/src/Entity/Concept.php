<?php

declare(strict_types = 1);

namespace Drupal\rdf_skos\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the SKOS Concept entity.
 *
 * @ContentEntityType(
 *   id = "skos_concept",
 *   label = @Translation("SKOS Concept"),
 *   handlers = {
 *     "storage" = "Drupal\rdf_skos\SkosEntityStorage",
 *     "list_builder" = "Drupal\rdf_skos\ConceptListBuilder",
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler",
 *     "access" = "Drupal\rdf_skos\ConceptAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\rdf_skos\ConceptHtmlRouteProvider",
 *     },
 *   },
 *   base_table = null,
 *   translatable = TRUE,
 *   admin_permission = "administer skos concept entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "pref_label",
 *     "uuid" = "id",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/skos_concept/{skos_concept}",
 *     "collection" = "/admin/structure/skos_concept",
 *   }
 * )
 */
class Concept extends ContentEntityBase implements ConceptInterface {

  /**
   * {@inheritdoc}
   */
  public function getPreferredLabel(): ?string {
    return $this->get('pref_label')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPreferredLabel(string $label): ConceptInterface {
    $this->set('pref_label', $label);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAlternateLabel(): ?string {
    return $this->get('alt_label')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setAlternateLabel(string $label): ConceptInterface {
    $this->set('alt_label', $label);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHiddenLabel(): ?string {
    return $this->get('hidden_label')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setHiddenLabel(string $label): ConceptInterface {
    $this->set('hidden_label', $label);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinition(): ?string {
    return $this->get('definition')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getScopeNote(): ?string {
    return $this->get('scope_note')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getHistoryNote(): ?string {
    return $this->get('history_note')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getEditorialNote(): ?string {
    return $this->get('editorial_note')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangeNote(): ?string {
    return $this->get('change_note')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getExample(): ?string {
    return $this->get('example')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getConceptSchemes(): array {
    return $this->get('in_scheme')->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function setConceptSchemes(array $concept_schemes): ConceptInterface {
    $this->set('in_scheme', $concept_schemes);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function topConceptOf(): array {
    return $this->get('top_concept_of')->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function getBroader(): array {
    return $this->get('broader')->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function getNarrower(): array {
    return $this->get('narrower')->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function getRelated(): array {
    return $this->get('related')->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function getExactMatch(): array {
    return $this->get('exact_match')->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function getCloseMatch(): array {
    return $this->get('close_match')->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function getBroadMatch(): array {
    return $this->get('broad_match')->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function getNarrowMatch(): array {
    return $this->get('narrow_match')->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function getRelatedMatch(): array {
    return $this->get('related_match')->referencedEntities();
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

    $fields['pref_label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Preferred Label'))
      ->setDescription(t('The preferred label of the Concept.'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'sting',
      ]);

    $fields['alt_label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Alternate Label'))
      ->setDescription(t('The alternate label of the Concept.'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'sting',
      ]);

    $fields['hidden_label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Hidden Label'))
      ->setDescription(t('The hidden label of the Concept.'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'sting',
      ]);

    $fields['definition'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Definition'))
      ->setDescription(t('The definition of the Concept.'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'sting',
      ]);

    $fields['example'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Example'))
      ->setDescription(t('The example of the Concept.'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'sting',
      ]);

    $fields['scope_note'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Scope Note'))
      ->setDescription(t('The scope note of the Concept.'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'sting',
      ]);

    $fields['editorial_note'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Editorial Note'))
      ->setDescription(t('The editorial note of the Concept.'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'sting',
      ]);

    $fields['change_note'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Change Note'))
      ->setDescription(t('The change note of the Concept.'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'sting',
      ]);

    $fields['history_note'] = BaseFieldDefinition::create('string')
      ->setLabel(t('History Note'))
      ->setDescription(t('The history note of the Concept.'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'sting',
      ]);

    $fields['in_scheme'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('In Scheme'))
      ->setDescription(t('The Concept Schemes this Concept belongs to.'))
      ->setSetting('target_type', 'skos_concept_scheme')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
      ]);

    $fields['top_concept_of'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Top Concept Of'))
      ->setDescription(t('The Concept Schemes this Concept is the top concept of.'))
      ->setSetting('target_type', 'skos_concept_scheme')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
      ]);

    $fields['broader'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Broader Concepts'))
      ->setDescription(t('The broader Concepts.'))
      ->setSetting('target_type', 'skos_concept')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
      ]);

    $fields['narrower'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Narrower Concepts'))
      ->setDescription(t('The narrower Concepts.'))
      ->setSetting('target_type', 'skos_concept')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
      ]);

    $fields['related'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Related Concepts'))
      ->setDescription(t('The related Concepts.'))
      ->setSetting('target_type', 'skos_concept')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
      ]);

    $fields['exact_match'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Exact Match Concepts'))
      ->setDescription(t('The exact match Concepts from other Concept Schemes.'))
      ->setSetting('target_type', 'skos_concept')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
      ]);

    $fields['close_match'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Close Match Concepts'))
      ->setDescription(t('The close match Concepts from other Concept Schemes.'))
      ->setSetting('target_type', 'skos_concept')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
      ]);

    $fields['broad_match'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Broad Match Concepts'))
      ->setDescription(t('The broader Concepts from other Concept Schemes.'))
      ->setSetting('target_type', 'skos_concept')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
      ]);

    $fields['narrow_match'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Narrow Match Concepts'))
      ->setDescription(t('The narrower Concepts from other Concept Schemes.'))
      ->setSetting('target_type', 'skos_concept')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
      ]);

    $fields['related_match'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Related Match Concepts'))
      ->setDescription(t('The related Concepts from other Concept Schemes.'))
      ->setSetting('target_type', 'skos_concept')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
      ]);

    return $fields;
  }

}
