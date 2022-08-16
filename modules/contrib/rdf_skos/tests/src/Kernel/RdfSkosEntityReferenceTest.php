<?php

declare(strict_types = 1);

namespace Drupal\Tests\rdf_skos\Kernel;

use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\rdf_skos\Plugin\Field\SkosConceptReferenceFieldItemList;
use Drupal\Tests\rdf_skos\Traits\SkosEntityReferenceTrait;

/**
 * Tests the SKOS entity reference field.
 */
class RdfSkosEntityReferenceTest extends RdfSkosKernelTestBase {

  use SkosEntityReferenceTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_test',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('user');
    // Enable both graphs.
    $this->enableGraph('fruit');
    $this->enableGraph('vegetables');
  }

  /**
   * Tests the SKOS Concept reference fields.
   */
  public function testReferenceFields(): void {
    // Create a reference field to Fruit.
    $this->createSkosConceptReferenceField(
      'entity_test',
      'entity_test',
      ['http://example.com/fruit'],
      'field_fruit',
      'Fruit'
    );

    // Create a reference field to Fruit and Vegetables.
    $this->createSkosConceptReferenceField(
      'entity_test',
      'entity_test',
      [
        'http://example.com/fruit',
        'http://example.com/vegetables',
      ],
      'field_fruits_veggies',
      'Fruits and Vegetables'
    );

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');

    /** @var \Drupal\entity_test\Entity\EntityTest $entity */
    $entity = $entity_type_manager->getStorage('entity_test')
      ->create(['type' => 'entity_test']);

    // The fruit field should only reference fruit concepts.
    $entity->set('field_fruit', 'http://example.com/fruit/apple');
    $violations = $entity->field_fruit->validate();
    $this->assertCount(0, $violations);
    $entity->set('field_fruit', 'http://example.com/vegetables/potato');
    $violations = $entity->field_fruit->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals(t('This entity (%type: %id) cannot be referenced.', [
      '%type' => 'skos_concept',
      '%id' => 'http://example.com/vegetables/potato',
    ]), $violations[0]->getMessage());

    // The fruits_veggies field should be able to reference both.
    $entity->set('field_fruits_veggies', 'http://example.com/fruit/apple');
    $violations = $entity->field_fruits_veggies->validate();
    $this->assertEquals(0, $violations->count());
    $entity->set('field_fruits_veggies', 'http://example.com/vegetables/potato');
    $violations = $entity->field_fruits_veggies->validate();
    $this->assertCount(0, $violations);

    // Top concepts that don't define their scheme should also be referencable.
    $entity->set('field_fruit', 'http://example.com/fruit/exotic-fruit');
    $violations = $entity->field_fruit->validate();
    $this->assertCount(0, $violations);

    // Configure the field to have a default value.
    $field = FieldConfig::loadByName('entity_test', 'entity_test', 'field_fruit');
    $field->setDefaultValue([
      'target_id' => 'http://example.com/fruit/pear',
    ]);
    $field->save();

    /** @var \Drupal\entity_test\Entity\EntityTest $entity */
    $entity = $entity_type_manager->getStorage('entity_test')
      ->create(['type' => 'entity_test']);
    $entity->save();
    $entity_type_manager->getStorage('entity_test')->resetCache();
    $entity_type_manager->getStorage('entity_test')->load($entity->id());
    $this->assertCount(1, $entity->get('field_fruit')->referencedEntities());
    $this->assertEquals('http://example.com/fruit/pear', $entity->get('field_fruit')->entity->id());
  }

  /**
   * Test multiple references.
   */
  public function testMultipleReferences(): void {
    $this->createSkosConceptReferenceField(
      'entity_test',
      'entity_test',
      ['http://example.com/fruit'],
      'field_fruit',
      'Fruit'
    );

    // Increase the field cardinality.
    $field_storage = FieldStorageConfig::loadByName('entity_test', 'field_fruit');
    $field_storage->setCardinality(-1);
    $field_storage->save();

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');

    /** @var \Drupal\entity_test\Entity\EntityTest $entity */
    $entity = $entity_type_manager->getStorage('entity_test')
      ->create(['type' => 'entity_test']);

    $entity->set('field_fruit', [
      'http://example.com/fruit/apple',
      'http://example.com/fruit/lemon',
    ]);

    $entity->save();
    $entity_type_manager->getStorage('entity_test')->resetCache();
    $entity_type_manager->getStorage('entity_test')->load($entity->id());
    $this->assertInstanceOf(EntityReferenceFieldItemListInterface::class, $entity->get('field_fruit'));
    $this->assertInstanceOf(SkosConceptReferenceFieldItemList::class, $entity->get('field_fruit'));
    $referenced_entities = $entity->get('field_fruit')->referencedEntities();
    $this->assertCount(2, $referenced_entities);
    $this->assertEquals('http://example.com/fruit/apple', $referenced_entities[0]->id());
    $this->assertEquals('http://example.com/fruit/lemon', $referenced_entities[1]->id());
  }

  /**
   * Test reference field query alter.
   *
   * Tests that fields can be configured in a way that their selection plugin
   * is alterable.
   */
  public function testReferenceFieldQueryAlter() {
    // Create a reference field to Fruit.
    $this->createSkosConceptReferenceField(
      'entity_test',
      'entity_test',
      ['http://example.com/fruit'],
      'field_fruit',
      'Fruit'
    );

    // Alter the query.
    // @see rdf_skos_test_query_skos_concept_field_selection_plugin_alter()
    \Drupal::state()->set('rdf_skos_test_query_skos_concept_field_selection_plugin_alter', [
      'field' => 'related',
      'value' => 'http://example.com/fruit/apple',
    ]);
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');

    /** @var \Drupal\entity_test\Entity\EntityTest $entity */
    $entity = $entity_type_manager->getStorage('entity_test')
      ->create(['type' => 'entity_test']);

    // The fruit field should only reference the "pear" fruit as it's the only
    // one that is related to "apple".
    $entity->set('field_fruit', 'http://example.com/fruit/apple');
    $violations = $entity->field_fruit->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals(t('This entity (%type: %id) cannot be referenced.', [
      '%type' => 'skos_concept',
      '%id' => 'http://example.com/fruit/apple',
    ]), $violations[0]->getMessage());

    $entity->set('field_fruit', 'http://example.com/fruit/pear');
    $violations = $entity->field_fruit->validate();
    $this->assertCount(0, $violations);
  }

  /**
   * Tests that concept subsets can be used to alter the queries.
   */
  public function testConceptSubsetQueryAlter(): void {
    // Create a reference field to Fruit, using the fruit_alter subset.
    $this->createSkosConceptReferenceField(
      'entity_test',
      'entity_test',
      ['http://example.com/fruit'],
      'field_fruit',
      'Fruit',
      NULL,
      'fruit_alter'
    );

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');

    /** @var \Drupal\entity_test\Entity\EntityTest $entity */
    $entity = $entity_type_manager->getStorage('entity_test')
      ->create(['type' => 'entity_test']);

    // Since we are using the subset, only one single concept can be referenced.
    $entity->set('field_fruit', 'http://example.com/fruit/citrus-fruit');
    $violations = $entity->field_fruit->validate();
    $this->assertCount(0, $violations);
    $fruits = [
      'http://example.com/fruit/apple',
      'http://example.com/fruit/exotic-fruit',
      'http://example.com/fruit/lemon',
    ];

    foreach ($fruits as $fruit) {
      $entity->set('field_fruit', $fruit);
      $violations = $entity->field_fruit->validate();
      $this->assertCount(1, $violations);
      $this->assertEquals(t('This entity (%type: %id) cannot be referenced.', [
        '%type' => 'skos_concept',
        '%id' => $fruit,
      ]), $violations[0]->getMessage());
    }
  }

  /**
   * Tests the applicable plugin definitions by concept schemes.
   */
  public function testPluginsApplicability(): void {
    /** @var \Drupal\rdf_skos\ConceptSubsetPluginManagerInterface $manaer */
    $manager = $this->container->get('plugin.manager.concept_subset');
    $definitions = array_keys($manager->getApplicableDefinitions(['http://example.com/fruit']));
    $expected = [
      'any_alter',
      'fruit_alter',
      'multi_alter',
      'predicate_mapping',
    ];

    sort($definitions);
    $this->assertEquals($expected, $definitions);

    $definitions = array_keys($manager->getApplicableDefinitions([
      'http://example.com/fruit',
      'http://example.com/vegetables',
    ]));
    $expected = [
      'any_alter',
      'multi_alter',
      'predicate_mapping',
    ];

    sort($definitions);
    $this->assertEquals($expected, $definitions);
  }

  /**
   * Tests that concept subsets can map new predicates to custom fields.
   */
  public function testConceptSubsetPredicateMapping(): void {
    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager */
    $entity_field_manager = $this->container->get('entity_field.manager');
    $fields = $entity_field_manager->getBaseFieldDefinitions('skos_concept');
    $this->assertContains('dummy_title', array_keys($fields));
    /** @var \Drupal\Core\Field\BaseFieldDefinition $field */
    $field = $fields['dummy_title'];
    $this->assertEquals('A dummy title', $field->getLabel());

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    $concept = $entity_type_manager->getStorage('skos_concept')->load('http://example.com/fruit/citrus-fruit');
    $this->assertEquals('A dummy value that is not skos.', $concept->get('dummy_title')->value);
  }

  /**
   * Tests that we can map multiple fields to a single predicate.
   */
  public function testMultipleFieldMappings(): void {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    /** @var \Drupal\rdf_skos\Entity\ConceptInterface $concept */
    $concept = $entity_type_manager->getStorage('skos_concept')->load('http://example.com/fruit/citrus-fruit');
    foreach (['dummy_field_one', 'dummy_field_two'] as $name) {
      $this->assertTrue($concept->hasField($name));
    }
    $this->assertEquals('A dummy value that is not skos.', $concept->get('dummy_field_one')->value);
    $this->assertEquals('A dummy value that is not skos.', $concept->get('dummy_field_two')->value);
  }

}
