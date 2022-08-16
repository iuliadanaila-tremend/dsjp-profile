<?php

declare(strict_types = 1);

namespace Drupal\Tests\rdf_skos\Kernel;

use Drupal\rdf_skos\Entity\ConceptInterface;
use Drupal\rdf_skos_test\TestSkosEntityStorage;

/**
 * Tests the RDF Skos storage caching mechanisms.
 */
class RdfSkosCacheTest extends RdfSkosKernelTestBase {

  /**
   * Tests that persistent caching works correctly.
   */
  public function testPersistentCache(): void {
    $this->enableGraph('fruit');

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');

    // Replace the storage class with a mock class that can trace some of the
    // calls made in the storage.
    $entity_type_manager->getDefinition('skos_concept')->setStorageClass(TestSkosEntityStorage::class);
    $this->container->set('entity_type.manager', $entity_type_manager);
    $storage = $this->container->get('entity_type.manager')->getStorage('skos_concept');
    $this->assertInstanceOf(TestSkosEntityStorage::class, $storage);
    $this->assertEmpty($storage->getStorageLoadCalls());

    // Disable static caching on the storage.
    $storage->disableStaticCache();

    // Check that we don't have anything yet in the persistent cache for our
    // Skos concept.
    $cid = "values:skos_concept:http://example.com/fruit/citrus-fruit";
    $this->assertFalse($this->container->get('cache.entity')->get($cid));

    // Load the Skos concept, triggering its caching in the persistent cache.
    $concept = $entity_type_manager->getStorage('skos_concept')->load('http://example.com/fruit/citrus-fruit');
    $this->assertInstanceOf(ConceptInterface::class, $concept);
    $this->assertNotFalse($this->container->get('cache.entity')->get($cid));

    $storage = $this->container->get('entity_type.manager')->getStorage('skos_concept');
    // When we loaded the Concept the first time, it got loaded from the
    // storage.
    $this->assertEquals([['http://example.com/fruit/citrus-fruit']], $storage->getStorageLoadCalls());

    // Load the concept again. This time, it should load it from the persistent
    // cache, and no longer hit the storage.
    $concept = $entity_type_manager->getStorage('skos_concept')->load('http://example.com/fruit/citrus-fruit');
    $this->assertInstanceOf(ConceptInterface::class, $concept);
    $this->assertEquals([['http://example.com/fruit/citrus-fruit'], []], $storage->getStorageLoadCalls());
  }

  /**
   * Tests that static caching works correctly.
   */
  public function testStaticCache(): void {
    $this->enableGraph('fruit');

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');

    // Replace the storage class with a mock class that can trace some of the
    // calls made in the storage.
    $entity_type_manager->getDefinition('skos_concept')->setStorageClass(TestSkosEntityStorage::class);
    $this->container->set('entity_type.manager', $entity_type_manager);
    $storage = $this->container->get('entity_type.manager')->getStorage('skos_concept');
    $this->assertInstanceOf(TestSkosEntityStorage::class, $storage);
    $this->assertEmpty($storage->getLoadCalls());

    // Do a first load of the Skos concept which will load it from storage.
    $concept = $entity_type_manager->getStorage('skos_concept')->load('http://example.com/fruit/citrus-fruit');
    $this->assertInstanceOf(ConceptInterface::class, $concept);
    $storage = $this->container->get('entity_type.manager')->getStorage('skos_concept');
    $this->assertEquals([['http://example.com/fruit/citrus-fruit']], $storage->getStorageLoadCalls());
    $this->assertEquals([['http://example.com/fruit/citrus-fruit']], $storage->getLoadCalls());

    // Do a second load of the Skos concept which will now load from static
    // cache.
    $concept = $entity_type_manager->getStorage('skos_concept')->load('http://example.com/fruit/citrus-fruit');
    $this->assertInstanceOf(ConceptInterface::class, $concept);
    $storage = $this->container->get('entity_type.manager')->getStorage('skos_concept');
    $this->assertEquals([['http://example.com/fruit/citrus-fruit']], $storage->getStorageLoadCalls());
    $this->assertEquals([['http://example.com/fruit/citrus-fruit']], $storage->getLoadCalls());
  }

}
