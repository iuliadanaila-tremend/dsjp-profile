<?php

declare(strict_types = 1);

namespace Drupal\rdf_skos;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the SKOS Concept Scheme entity.
 *
 * @see \Drupal\rdf_skos\Entity\ConceptScheme.
 */
class ConceptSchemeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    // For the moment, we only allow read.
    return $operation === 'view' ? AccessResult::allowedIfHasPermissions($account, [
      'view published skos concept scheme entities',
      'administer skos concept scheme entities',
    ], 'OR'
    ) : AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResultInterface {
    // For the moment, we only allow read.
    return AccessResult::forbidden();
  }

}
