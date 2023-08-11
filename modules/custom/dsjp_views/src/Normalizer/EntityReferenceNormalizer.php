<?php

namespace Drupal\dsjp_views\Normalizer;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\file\FileInterface;
use Drupal\node\NodeInterface;
use Drupal\serialization\Normalizer\ComplexDataNormalizer;
use Drupal\taxonomy\TermInterface;

/**
 * Provides a custom normalizer for entity reference fields.
 */
class EntityReferenceNormalizer extends ComplexDataNormalizer {

  /**
   * The entity type manager property.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * EntityReferenceNormalizer constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager interface.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = ComplexDataInterface::class;

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    $return = parent::normalize($object, $format, $context);
    // Show the term code in the field value array.
    if ($object instanceof EntityReferenceItem) {
      if ($object->getDataDefinition()->getSetting('target_type') == 'taxonomy_term') {
        $entity = $this->entityTypeManager->getStorage('taxonomy_term')->load($object->getValue()['target_id']);
        if ($entity instanceof TermInterface) {
          $code = $entity->get('field_' . $entity->bundle() . '_code')->getValue();
          $return['code'] = $this->getCodeValue($code);
        }
      }
      // Show the file url.
      elseif ($object->getDataDefinition()->getSetting('target_type') == 'file') {
        $entity = $this->entityTypeManager->getStorage('file')->load($object->getValue()['target_id']);
        if ($entity instanceof FileInterface) {
          $return['url'] = $entity->createFileUrl(FALSE);
        }
      }
      // Show the node name.
      elseif ($object->getDataDefinition()->getSetting('target_type') == 'node') {
        $entity = $this->entityTypeManager->getStorage('node')->load($object->getValue()['target_id']);
        if ($entity instanceof NodeInterface) {
          $return['name'] = $entity->label();
        }
      }
    }

    return $return;
  }

  /**
   * Gets the code value from code field.
   *
   * @param array $code
   *   The code field array value.
   *
   * @return mixed|string
   *   Returns the code id, empty string otherwise.
   */
  protected function getCodeValue(array $code) {
    if (!empty($code[0]['target_id'])) {
      $return = $code[0]['target_id'];
    }
    elseif (!empty($code[0]['value'])) {
      $return = $code[0]['value'];
    }
    else {
      $return = '';
    }

    return $return;
  }

}
