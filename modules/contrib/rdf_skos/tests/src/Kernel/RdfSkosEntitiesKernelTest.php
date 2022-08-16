<?php

declare(strict_types = 1);

namespace Drupal\Tests\rdf_skos\Kernel;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\rdf_skos\Entity\ConceptInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the RDF SKOS entities.
 */
class RdfSkosEntitiesKernelTest extends RdfSkosKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'language',
    'content_translation',
    'rdf_skos_language_mapping',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('configurable_language');
    $this->installConfig(['language', 'content_translation']);
    ConfigurableLanguage::createFromLangcode('fr')->save();
    ConfigurableLanguage::createFromLangcode('it')->save();
  }

  /**
   * Test that SKOS entities are not found without config.
   */
  public function testNoConfig(): void {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');

    $ids = $entity_type_manager->getStorage('skos_concept_scheme')->getQuery()
      ->execute();
    $this->assertEmpty($ids);

    $ids = $entity_type_manager->getStorage('skos_concept')->getQuery()
      ->execute();
    $this->assertEmpty($ids);
  }

  /**
   * Test that config exposes SKOS Concepts.
   */
  public function testConfigEnablesSkosEntities(): void {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');

    // Configure to read from the fruits graph.
    $this->enableGraph('fruit');
    $ids = $entity_type_manager->getStorage('skos_concept_scheme')->getQuery()
      ->execute();
    $this->assertCount(1, $ids);
    $id = reset($ids);
    $this->assertEquals('http://example.com/fruit', $id);

    $ids = $entity_type_manager->getStorage('skos_concept')->getQuery()
      ->execute();
    $this->assertCount(7, $ids);
  }

  /**
   * Tests SKOS entities have data.
   */
  public function testSkosEntities(): void {
    $this->enableGraph('fruit');

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    $ids = $entity_type_manager->getStorage('skos_concept_scheme')->getQuery()
      ->execute();
    $concept_schemes = $entity_type_manager->getStorage('skos_concept_scheme')->loadMultiple($ids);
    $this->assertCount(1, $concept_schemes);
    /** @var \Drupal\rdf_skos\Entity\ConceptSchemeInterface $concept_scheme */
    $concept_scheme = reset($concept_schemes);
    $this->assertEquals('Fruit', $concept_scheme->getTitle());

    $this->assertEquals('http://example.com/fruit', $concept_scheme->id());
    $this->assertEquals('http://example.com/fruit', $concept_scheme->uuid());

    // Assert top concepts.
    $top_concepts = $concept_scheme->getTopConcepts();
    $top_concept = reset($top_concepts);
    $this->assertEquals('Citrus fruit', $top_concept->getPreferredLabel());

    // Test concepts.
    $ids = $entity_type_manager->getStorage('skos_concept')->getQuery()
      ->condition('in_scheme', $concept_scheme->id())
      ->execute();
    /** @var \Drupal\rdf_skos\Entity\ConceptInterface[] $concepts */
    $concepts = $entity_type_manager->getStorage('skos_concept')->loadMultiple($ids);
    $this->assertCount(6, $concepts);

    $citrus = $concepts['http://example.com/fruit/citrus-fruit'];
    $this->assertEquals('http://example.com/fruit/citrus-fruit', $citrus->uuid());
    $this->assertEquals('Citrus fruit ALT', $citrus->getAlternateLabel());
    $this->assertEquals('Citrus fruit HIDDEN', $citrus->getHiddenLabel());
    $this->assertEquals('lemons, oranges, limes, mandarines, grapefruit, satsumas', $citrus->getExample());
    $this->assertEquals('Fruit that make you salivate sometimes', $citrus->getDefinition());

    $lemon = $concepts['http://example.com/fruit/lemon'];
    $this->assertEquals('Citrus fruit', $lemon->getBroader()[0]->getPreferredLabel());

    $pear = $concepts['http://example.com/fruit/pear'];
    $this->assertEquals('Apple', $pear->getRelated()[0]->getPreferredLabel());

    $concepts = $entity_type_manager->getStorage('skos_concept')->loadByProperties(['pref_label' => 'Exotic fruit']);
    $concept = reset($concepts);
    $this->assertEquals('Fruit', $concept->topConceptOf()[0]->getTitle());
    $this->assertEmpty($concept->getConceptSchemes());
    $this->assertEquals('Banana', $concept->getNarrower()[0]->getPreferredLabel());
  }

  /**
   * Tests SKOS entity translations.
   */
  public function testSkosEntityTranslations(): void {
    $this->enableGraph('fruit');

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    /** @var \Drupal\rdf_skos\Entity\ConceptSchemeInterface $scheme */
    $scheme = $entity_type_manager->getStorage('skos_concept_scheme')->load('http://example.com/fruit');
    $this->assertTrue($scheme->hasTranslation('it'));
    $this->assertFalse($scheme->hasTranslation('fr'));
    $this->assertEquals('Frutta', $scheme->getTranslation('it')->label());

    /** @var \Drupal\rdf_skos\Entity\ConceptInterface $pear */
    $pear = $entity_type_manager->getStorage('skos_concept')->load('http://example.com/fruit/pear');
    $this->assertTrue($pear->hasTranslation('fr'));
    $this->assertFalse($pear->hasTranslation('it'));
    $this->assertEquals('Poire', $pear->getTranslation('fr')->label());

    /** @var \Drupal\rdf_skos\Entity\ConceptInterface $citrus */
    $citrus = $entity_type_manager->getStorage('skos_concept')->load('http://example.com/fruit/citrus-fruit');
    $languages = [];
    foreach ($citrus->getTranslationLanguages() as $language) {
      $languages[] = $language->getId();
    }
    $this->assertEquals(['en', 'fr', 'it'], $languages);
    $this->assertEquals('Agrumi', $citrus->getTranslation('it')->label());
    // No label in FR so it should show the original one.
    $this->assertEquals('Citrus fruit', $citrus->getTranslation('fr')->label());
    $this->assertEquals('Agrumes ALT', $citrus->getTranslation('fr')->get('alt_label')->value);
  }

  /**
   * Tests that SKOS entities can be read from multiple graphs.
   */
  public function testMultipleGraphs(): void {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    $this->enableGraph('fruit');
    $this->enableGraph('vegetables');

    $ids = $entity_type_manager->getStorage('skos_concept_scheme')->getQuery()
      ->execute();
    $this->assertCount(2, $ids);
    $ids = $entity_type_manager->getStorage('skos_concept')->getQuery()
      ->execute();
    $this->assertCount(8, $ids);
  }

  /**
   * Tests that for SKOS entities we get the correct active graphs.
   */
  public function testActiveGraphs(): void {
    $this->enableGraph('fruit');
    $url = $this->container->get('entity_type.manager')->getStorage('skos_concept')->load('http://example.com/fruit/apple')->toUrl()->toString();
    $request = Request::create($url);
    /** @var \Drupal\Core\Routing\Router $router */
    $router = \Drupal::service('router.no_access_checks');
    $matched = $router->matchRequest($request);
    // If this matched correctly using the RDF entity param converter, we should
    // have the Concept entity in the result. Otherwise an exception is thrown
    // and the test should fail.
    $this->assertInstanceOf(ConceptInterface::class, $matched['skos_concept']);
  }

}
