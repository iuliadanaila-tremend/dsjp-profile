<?php

declare(strict_types = 1);

namespace Drupal\rdf_skos\Plugin\Field;

use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldItemList;

/**
 * Field item list class for the SKOS concept reference field item.
 */
class SkosConceptReferenceFieldItemList extends FieldItemList implements EntityReferenceFieldItemListInterface {

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();
    $constraint_manager = $this->getTypedDataManager()->getValidationConstraintManager();
    $constraints[] = $constraint_manager->create('ValidReference', []);
    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function referencedEntities() {
    if ($this->isEmpty()) {
      return [];
    }

    $target_entities = $ids = [];
    foreach ($this->list as $delta => $item) {
      if ($item->target_id !== NULL) {
        $ids[$delta] = $item->target_id;
      }
    }

    if ($ids) {
      $entities = \Drupal::entityTypeManager()->getStorage('skos_concept')->loadMultiple($ids);
      foreach ($ids as $delta => $target_id) {
        if (isset($entities[$target_id])) {
          $target_entities[$delta] = $entities[$target_id];
        }
      }

      ksort($target_entities);
    }

    return $target_entities;
  }

}
