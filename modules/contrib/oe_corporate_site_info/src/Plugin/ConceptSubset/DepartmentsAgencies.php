<?php

declare(strict_types = 1);

namespace Drupal\oe_corporate_site_info\Plugin\ConceptSubset;

use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\rdf_skos\ConceptSubsetPluginBase;
use Drupal\rdf_skos\Plugin\PredicateMapperInterface;
use Drupal\sparql_entity_storage\SparqlEntityStorageFieldHandlerInterface;

/**
 * Creates a subset of the corporate bodies vocabulary.
 * @codingStandardsIgnoreStart
 * @ConceptSubset(
 *   id = "oe_corporate_site_info_corporate_bodies_department_executive_agencies",
 *   label = @Translation("Departments & Executive Agencies"),
 *   description = @Translation("Includes DGs, executive agencies and service departments."),
 *   predicate_mapping = TRUE,
 *   concept_schemes = {
 *     "http://publications.europa.eu/resource/authority/corporate-body"
 *   }
 * )
 * @codingStandardsIgnoreEnd
 */
class DepartmentsAgencies extends ConceptSubsetPluginBase implements PredicateMapperInterface {

  /**
   * {@inheritdoc}
   */
  public function alterQuery(QueryInterface $query, $match_operator, array $concept_schemes = [], string $match = NULL): void {
    $types = [
      // Directorate-general.
      'http://publications.europa.eu/resource/authority/corporate-body-classification/DIR_GEN',
      // Service departments.
      'http://publications.europa.eu/resource/authority/corporate-body-classification/SERV_DEP',
      // Executive agency.
      'http://publications.europa.eu/resource/authority/corporate-body-classification/AGENCY_EXEC',
    ];
    $query->condition('oe_corporate_site_info_corporate_body_classification', $types, 'IN');
  }

  /**
   * {@inheritdoc}
   */
  public function getPredicateMapping(): array {
    $mapping = [];

    $mapping['oe_corporate_site_info_corporate_body_classification'] = [
      'column' => 'target_id',
      'predicate' => ['http://purl.org/dc/terms/type'],
      'format' => SparqlEntityStorageFieldHandlerInterface::RESOURCE,
    ];

    return $mapping;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFieldDefinitions(): array {
    $fields = [];

    $fields['oe_corporate_site_info_corporate_body_classification'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Corporate Bodies Classification'))
      ->setDescription(t('The corporate body classification.'))
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
