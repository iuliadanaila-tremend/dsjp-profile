<?php

namespace Drupal\dsjp_community\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides specific access control for the user entity type.
 *
 * @EntityReferenceSelection(
 *   id = "default:user_fullname",
 *   label = @Translation("User fullname selection"),
 *   entity_types = {"user"},
 *   group = "default",
 *   weight = 1
 * )
 */
class UserByFullnameSelection extends DefaultSelection {

  /**
   * The database service property.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Drupal renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('current_user'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity.repository'),
      $container->get('database')
    );

    $instance->database = $container->get('database');
    $instance->renderer = $container->get('renderer');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $query = $this->database->select('users_field_data', 'u')
      ->fields('u', ['uid'])
      ->where("CONCAT_WS(' ', u.field_dsj_firstname, ' ', u.field_dsj_lastname) LIKE :input", [
        ':input' => '%' . $this->database->escapeLike($match) . '%',
      ])
      ->condition('u.uid', $this->currentUser->id(), '!=')
      ->range(0, 10)
      ->execute();
    $result = $query->fetchCol();
    if (empty($result)) {
      return [];
    }

    $options = [];
    $entities = $this->entityTypeManager->getStorage('user')->loadMultiple($result);
    foreach ($entities as $entity_id => $entity) {
      $firstname = $entity->get('field_dsj_firstname')->getString();
      $lastname = $entity->get('field_dsj_lastname')->getString();
      $authorInitials = mb_strtoupper(mb_substr($firstname, 0, 1)) . mb_strtoupper(mb_substr($lastname, 0, 1));
      if (!$entity->get('field_dsj_picture')->isEmpty()) {
        $imageArray = [
          '#theme' => 'image_style',
          '#style_name' => 'thumbnail',
          '#uri' => $entity->get('field_dsj_picture')->entity->getFileUri(),
        ];
        $image = $this->renderer->render($imageArray);
      }
      else {
        $image = '<p data-letters="' . $authorInitials . '"></p>';
      }

      $bundle = $entity->bundle();

      $options[$bundle][$entity_id] = '<div class="user-picture">' . $image . '</div>' . $firstname . ' ' . $lastname;
    }

    return $options;
  }

}
