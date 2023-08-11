<?php

namespace Drupal\dsjp_content\Plugin\search_api\processor;

use Drupal\Core\Entity\TypedData\EntityDataDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\Plugin\search_api\data_type\value\TextValue;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\SearchApiException;
use Drupal\search_api\Utility\Utility;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds all ancestors' IDs to a hierarchical field.
 *
 * @SearchApiProcessor(
 *   id = "reverse_hierarchy",
 *   label = @Translation("Index reverse hierarchy"),
 *   description = @Translation("Allows the reverse indexing of values along with all their childrens for hierarchical fields (like taxonomy term references)"),
 *   stages = {
 *     "preprocess_index" = -45
 *   }
 * )
 */
class AddReverseHierarchy extends ProcessorPluginBase implements PluginFormInterface {

  use PluginFormTrait;

  /**
   * Static cache for getReverseHierarchyFields() return values.
   *
   * @var string[][][]
   */
  protected static $indexHierarchyFields = [];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|null
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $processor */
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $processor->entityTypeManager = $container->get('entity_type.manager');

    return $processor;
  }

  /**
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index) {
    $processor = new static(['#index' => $index], 'reverse_hierarchy', []);
    return (bool) $processor->getReverseHierarchyFields();
  }

  /**
   * Finds all (potentially) hierarchical fields for this processor's index.
   *
   * Fields are returned if:
   * - they point to an entity type; and
   * - that entity type contains a property referencing the same type of entity
   *   (so that a hierarchy could be built from that nested property).
   *
   * @return string[][]
   *   An array containing all fields of the index for which hierarchical data
   *   might be retrievable. The keys are those field's IDs, the values are
   *   associative arrays containing the nested properties of those fields from
   *   which a hierarchy might be constructed, with the property paths as the
   *   keys and labels as the values.
   */
  protected function getReverseHierarchyFields() {
    if (!isset(static::$indexHierarchyFields[$this->index->id()])) {
      $field_options = [];

      foreach ($this->index->getFields() as $field_id => $field) {
        try {
          $definition = $field->getDataDefinition();
        }
        catch (SearchApiException $e) {
          $vars = [
            '%index' => $this->index->label(),
          ];
          watchdog_exception('search_api', $e, '%type while trying to retrieve a list of hierarchical fields on index %index: @message in %function (line %line of %file).', $vars);
          continue;
        }
        if ($definition instanceof ComplexDataDefinitionInterface) {
          $properties = $this->getFieldsHelper()
            ->getNestedProperties($definition);
          // The property might be an entity data definition itself.
          $properties[''] = $definition;
          foreach ($properties as $property) {
            $property_label = $property->getLabel();
            $property = $this->getFieldsHelper()->getInnerProperty($property);
            if ($property instanceof EntityDataDefinitionInterface) {
              $options = static::findHierarchicalProperties($property, $property_label);
              if ($options) {
                $field_options += [$field_id => []];
                $field_options[$field_id] += $options;
              }
            }
          }
        }
      }

      static::$indexHierarchyFields[$this->index->id()] = $field_options;
    }

    return static::$indexHierarchyFields[$this->index->id()];
  }

  /**
   * Finds all hierarchical properties nested on an entity-typed property.
   *
   * @param \Drupal\Core\Entity\TypedData\EntityDataDefinitionInterface $property
   *   The property to be searched for hierarchical nested properties.
   * @param string $property_label
   *   The property's label.
   *
   * @return string[]
   *   An options list of hierarchical properties, keyed by the parent
   *   property's entity type ID and the nested properties identifier,
   *   concatenated with a dash (-).
   */
  protected function findHierarchicalProperties(EntityDataDefinitionInterface $property, $property_label) {
    $entity_type_id = $property->getEntityTypeId();
    $property_label = Utility::escapeHtml($property_label);
    $options = [];

    // Check properties for potential hierarchy. Check two levels down, since
    // Core's entity references all have an additional "entity" sub-property for
    // accessing the actual entity reference, which we'd otherwise miss.
    foreach ($this->getFieldsHelper()->getNestedProperties($property) as $name_2 => $property_2) {
      $property_2_label = $property_2->getLabel();
      $property_2 = $this->getFieldsHelper()->getInnerProperty($property_2);
      $is_reference = FALSE;
      if ($property_2 instanceof EntityDataDefinitionInterface) {
        if ($property_2->getEntityTypeId() == $entity_type_id) {
          $is_reference = TRUE;
        }
      }
      elseif ($property_2 instanceof ComplexDataDefinitionInterface) {
        foreach ($property_2->getPropertyDefinitions() as $property_3) {
          $property_3 = $this->getFieldsHelper()->getInnerProperty($property_3);
          if ($property_3 instanceof EntityDataDefinitionInterface) {
            if ($property_3->getEntityTypeId() == $entity_type_id) {
              $is_reference = TRUE;
              break;
            }
          }
        }
      }
      if ($is_reference) {
        $property_2_label = Utility::escapeHtml($property_2_label);
        $options["$entity_type_id-$name_2"] = $property_label . ' » ' . $property_2_label;
      }
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'fields' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $formState) {
    $form['#description'] = $this->t('Select the fields to which hierarchical data should be added.');

    foreach ($this->getReverseHierarchyFields() as $field_id => $options) {
      $enabled = !empty($this->configuration['fields'][$field_id]);
      $form['fields'][$field_id]['status'] = [
        '#type' => 'checkbox',
        '#title' => $this->index->getField($field_id)->getLabel(),
        '#default_value' => $enabled,
      ];
      reset($options);
      $form['fields'][$field_id]['property'] = [
        '#type' => 'radios',
        '#title' => $this->t('Hierarchy property to use'),
        '#description' => $this->t("This field has several nested properties which look like they might contain hierarchy data for the field. Please pick the one that should be used."),
        '#options' => $options,
        '#default_value' => $enabled ? $this->configuration['fields'][$field_id] : key($options),
        '#access' => count($options) > 1,
        '#states' => [
          'visible' => [
            // @todo This shouldn't be dependent on the form array structure.
            //   Use the '#process' trick instead.
            ":input[name=\"processors[reverse_hierarchy][settings][fields][$field_id][status]\"]" => [
              'checked' => TRUE,
            ],
          ],
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $formState) {
    $fields = [];
    foreach ($formState->getValue('fields', []) as $field_id => $values) {
      if (!empty($values['status'])) {
        if (empty($values['property'])) {
          $formState->setError($form['fields'][$field_id]['property'], $this->t('You need to select a nested property to use for the hierarchy data.'));
        }
        else {
          $fields[$field_id] = $values['property'];
        }
      }
    }
    $formState->setValue('fields', $fields);
    if (!$fields) {
      $formState->setError($form['fields'], $this->t('You need to select at least one field for which to add hierarchy data.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessIndexItems(array $items) {
    /** @var \Drupal\search_api\Item\ItemInterface $item */
    foreach ($items as $item) {
      foreach ($this->configuration['fields'] as $field_id => $property_specifier) {
        $field = $item->getField($field_id);
        if (!$field) {
          continue;
        }
        [$entity_type_id, $property] = explode('-', $property_specifier);
        foreach ($field->getValues() as $entity_id) {
          if ($entity_id instanceof TextValue) {
            $entity_id = $entity_id->getOriginalText();
          }
          if (is_scalar($entity_id)) {
            try {
              $this->addHierarchyValues($entity_type_id, $entity_id, $property, $field);
            }
            // @todo Replace with multi-catch for
            //   InvalidPluginDefinitionException and PluginNotFoundException
            //   once we depend on PHP 7.1+.
            catch (\Exception $e) {
              $vars = [
                '%index' => $this->index->label(),
                '%field' => $field->getLabel(),
                '%field_id' => $field->getFieldIdentifier(),
              ];
              watchdog_exception('search_api', $e, '%type while trying to add hierarchy values to field %field (%field_id) on index %index: @message in %function (line %line of %file).', $vars);
              continue;
            }
          }
        }
      }
    }
  }

  /**
   * Adds all ancestors' IDs of the given entity to the given field.
   *
   * @param string $entityTypeId
   *   The entity type ID.
   * @param mixed $entityId
   *   The ID of the entity for which ancestors should be found.
   * @param string $property
   *   The name of the property on the entity type which contains the references
   *   to the parent entities.
   * @param \Drupal\search_api\Item\FieldInterface $field
   *   The field to which values should be added.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if a referenced entity type does not exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if a referenced entity's storage handler couldn't be loaded.
   */
  protected function addHierarchyValues($entityTypeId, $entityId, $property, FieldInterface $field) {
    $childrens = [];
    if ("$entityTypeId-$property" == 'taxonomy_term-parent') {
      /** @var \Drupal\taxonomy\TermStorageInterface $entity_storage */
      $entity_storage = $this->entityTypeManager
        ->getStorage('taxonomy_term');

      foreach ($entity_storage->loadChildren($entityId) as $term) {
        $childrens[] = $term->id();
      }
    }

    foreach ($childrens as $children) {
      if (!in_array($children, $field->getValues())) {
        $field->addValue($children);
        $this->addHierarchyValues($entityTypeId, $children, $property, $field);
      }
    }
  }

}
