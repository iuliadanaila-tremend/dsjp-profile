<?php

declare(strict_types = 1);

namespace Drupal\rdf_skos_language_mapping\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\rdf_skos\Event\SkosProcessGraphResultsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * RDF Skos subscriber to alter the graph results.
 *
 * Overriding the language codes loaded from the RDF storage
 * with the ones configured to be used instead.
 *
 * @see \Drupal\rdf_skos_language_mapping\Form\LanguageMappingForm
 */
class RdfSkosLanguageMappingSubscriber implements EventSubscriberInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a RdfSkosLanguageMappingSubscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      SkosProcessGraphResultsEvent::ALTER => ['onProcessGraphResults'],
    ];
  }

  /**
   * Event handler to alter the processed graph results.
   *
   * Overriding the language codes loaded from the RDF storage
   * with the ones configured to be used instead.
   *
   * @param \Drupal\rdf_skos\Event\SkosProcessGraphResultsEvent $event
   *   Process graph results event.
   */
  public function onProcessGraphResults(SkosProcessGraphResultsEvent $event) {
    $results = $event->getResults();
    $config = $this->configFactory->get('rdf_skos_language_mapping.settings')->get('language_mapping');

    // We don't do anything if a language mapping has not been configured.
    if (empty($config)) {
      return;
    }

    $storage = $this->entityTypeManager->getStorage($event->getEntityTypeId());
    $langcode_key = $storage->getEntityType()->getKey('langcode');

    $mapping = array_flip($config);
    foreach ($results as $id => $entity_values) {
      foreach ($entity_values as $field_name => $field_values) {
        foreach ($field_values as $langcode => $values) {
          if (array_key_exists($langcode, $mapping) && $langcode !== $mapping[$langcode]) {
            if ($field_name === $langcode_key) {
              // Replace value and key of langcode.
              $results[$id][$field_name][$langcode] = [['value' => $mapping[$langcode]]];
            }
            $results[$id][$field_name][$mapping[$langcode]] = $results[$id][$field_name][$langcode];
            unset($results[$id][$field_name][$langcode]);
          }
        }
      }
    }
    $event->setResults($results);
  }

}
