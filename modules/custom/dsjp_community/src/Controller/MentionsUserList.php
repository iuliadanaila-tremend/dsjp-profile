<?php

namespace Drupal\dsjp_community\Controller;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns json for user names that are enabled.
 */
class MentionsUserList extends ControllerBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $config;

  /**
   * Array containing all configs for the mentions module.
   *
   * @var \Drupal\Core\Config\Config[]|\Drupal\Core\Config\ImmutableConfig[]
   */
  protected $allConfigs;

  /**
   * MentionsUserList constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   The config factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(ConfigFactory $config, EntityTypeManagerInterface $entityTypeManager) {
    $this->config = $config;
    $this->entityTypeManager = $entityTypeManager;
    $this->allConfigs = $this->config->loadMultiple($this->config->listAll('mentions.mentions_type'));
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Method that returns all users in a json format.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The user list in a json format.
   */
  public function userList(Request $request) {

    $entityFields = [];
    // Iterate over all configs.
    foreach ($this->allConfigs as $config) {
      $entityType = $config->get('input.entity_type');
      $inputValue = $config->get('input.inputvalue');
      // Build an array of fields to search for mentions.
      if (!isset($entityFields[$entityType]) || !in_array($inputValue, $entityFields[$entityType])) {
        $entityFields[$entityType][] = $inputValue;
      }
    }
    $entityList = $this->buildEntityList($entityFields);
    // Return the response in a json format.
    return new JsonResponse($entityList);
  }

  /**
   * Helper function that builds the entity list.
   *
   * @param array $entityFields
   *   The fields to search for mentions.
   *
   * @return \array[][]
   *   The list of entities mentioned.
   */
  public function buildEntityList(array $entityFields) {
    // Init the structure of the returned array.
    $entityList = [
      'data' => [
        'config' => [],
        'entityData' => [],
      ],
    ];

    foreach ($this->allConfigs as $config) {
      $entityType = $config->get('input.entity_type');
      $inputPrefix = $config->get('input.prefix');
      $inputSuffix = $config->get('input.suffix');
      $inputValue = $config->get('input.inputvalue');
      $query = $this->entityTypeManager->getStorage($entityType)->getQuery();
      // Get a list of enabled entities.
      $entityIds = $query
        ->condition('status', 1)
        ->sort('name')
        ->accessCheck(TRUE)
        ->execute();
      // Load the entities.
      $entities = $this->entityTypeManager->getStorage('user')->loadMultiple($entityIds);
      // Iterate over all entities.
      foreach ($entities as $entity) {
        $entityTypeId = $entity->getEntityTypeId();
        // Init the new entity array that will store the users.
        $newEntityArray = [
          'name' => $this->getUserFullName($entity),
          'firstname' => $entity->get('field_dsj_firstname')->value,
          'lastname' => $entity->get('field_dsj_lastname')->value,
          'uid' => $entity->id(),
        ];
        // Init an array that will store all configs for all mention types.
        $configEntity = [
          'suffix' => $inputSuffix,
          'entityTypeId' => $entityTypeId,
          'inputValue' => $inputValue,
        ];
        // Build the array of config entities.
        if (!isset($entityList['data']['config']) || !isset($entityList['data']['config'][$inputPrefix])) {
          $entityList['data']['config'][$inputPrefix] = $configEntity;
        }
        // If the entity is already added, do not add it anymore.
        if (isset($entityList['data']['entityData'][$entityTypeId]) && in_array($newEntityArray, $entityList['data']['entityData'][$entityTypeId])) {
          continue;
        }
        // Add the entity.
        $entityList['data']['entityData'][$entityTypeId][] = $newEntityArray;
      }
    }

    return $entityList;
  }

  /**
   * Gets the user first name and last name.
   *
   * @param Drupal\user\UserInterface $entity
   *   The user entity.
   *
   * @return array|string
   *   The user name.
   */
  protected function getUserFullName(UserInterface $entity) {
    $names = [];
    $firstNameValue = $entity->get('field_dsj_firstname');
    if (!$firstNameValue->isEmpty()) {
      $names[] = $firstNameValue->value;
    }

    $lastNameValue = $entity->get('field_dsj_lastname');
    if (!$lastNameValue->isEmpty()) {
      $names[] = $lastNameValue->value;
    }
    return (!empty($names) ? implode(' ', $names) : $entity->getDisplayName());
  }

}
