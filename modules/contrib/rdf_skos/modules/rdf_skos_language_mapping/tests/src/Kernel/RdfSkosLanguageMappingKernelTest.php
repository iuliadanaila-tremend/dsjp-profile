<?php

declare(strict_types = 1);

namespace Drupal\Tests\rdf_skos_language_mapping\Kernel;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\rdf_skos\Kernel\RdfSkosKernelTestBase;

/**
 * Tests the mapping between Drupal langcodes and the ones in Skos entities.
 */
class RdfSkosLanguageMappingKernelTest extends RdfSkosKernelTestBase {

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
    $this->installConfig([
      'language',
      'content_translation',
    ]);
    ConfigurableLanguage::createFromLangcode('fr')->save();
    ConfigurableLanguage::createFromLangcode('it')->save();
    ConfigurableLanguage::createFromLangcode('pt-pt')->save();

    $this->config('rdf_skos_language_mapping.settings')
      ->set('language_mapping', [
        'en' => 'en',
        'fr' => 'fr',
        'pt-pt' => 'pt',
      ])
      ->save();
  }

  /**
   * Tests SKOS entity translations with applied language mapping.
   */
  public function testSkosEntityTranslationsWithLanguageMapping(): void {
    $this->enableGraph('fruit');

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    /** @var \Drupal\rdf_skos\Entity\ConceptSchemeInterface $scheme */
    $scheme = $entity_type_manager->getStorage('skos_concept_scheme')->load('http://example.com/fruit');
    $this->assertTrue($scheme->hasTranslation('it'));
    $this->assertTrue($scheme->hasTranslation('pt-pt'));
    $this->assertFalse($scheme->hasTranslation('fr'));
    $this->assertEquals('Frutta', $scheme->getTranslation('it')->label());
    $this->assertEquals('Fruta', $scheme->getTranslation('pt-pt')->label());

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
    $this->assertEquals(['en', 'fr', 'it', 'pt-pt'], $languages);
    $this->assertEquals('Agrumi', $citrus->getTranslation('it')->label());
    $this->assertEquals('Cítrico', $citrus->getTranslation('pt-pt')->label());
    // No label in FR so it should show the original one.
    $this->assertEquals('Citrus fruit', $citrus->getTranslation('fr')->label());
    $this->assertEquals('Agrumes ALT', $citrus->getTranslation('fr')->get('alt_label')->value);
  }

}
