<?php

namespace Drupal\dsjp_coalition\Plugin\migrate\process;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Perform custom value transformations.
 *
 * @MigrateProcessPlugin(
 *   id = "custom_field_entity_lookup"
 * )
 *
 * To do custom value transformations use the following:
 *
 * @code
 *   field_entity_reference:
 *     plugin: custom_field_entity_lookup
 *     source: tags
 *     value_field: field_to_search_for
 *     bundle_key: vid
 *     bundle: tags
 *     entity_type: taxonomy_term
 * @endcode
 */
class CustomFieldEntityLookup extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The migration.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition, MigrationInterface $migration = NULL) {
    $instance = new static(
      $configuration,
      $pluginId,
      $pluginDefinition
    );
    $instance->migration = $migration;
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // If the source data is an empty array, return the same.
    if (gettype($value) === 'array' && count($value) === 0) {
      return [];
    }

    return $this->query($value);
  }

  /**
   * Gets the entity type given based on the value field.
   *
   * @param string $value
   *   The value to search for.
   *
   * @return int|null
   *   Returns the entity id on success, NULL otherwise.
   */
  protected function query($value) {
    $query = $this->entityTypeManager->getStorage($this->configuration['entity_type'])->getQuery();
    $query->condition($this->configuration['bundle_key'], $this->configuration['bundle']);
    if (empty($value)) {
      $orCondition = $query->orConditionGroup();
      $orCondition->notExists($this->configuration['value_field']);
      $orCondition->condition($this->configuration['value_field'], '');
      $query->condition($orCondition);
    }
    else {
      $query->condition($this->configuration['value_field'], $value);
    }
    $result = $query->accessCheck(TRUE)->execute();

    if (!empty($result)) {
      $entity = $this->entityTypeManager->getStorage($this->configuration['entity_type'])->load(reset($result));
      if (!empty($entity) && $entity instanceof EntityInterface) {
        return $entity->id();
      }
    }

    return NULL;
  }

}
