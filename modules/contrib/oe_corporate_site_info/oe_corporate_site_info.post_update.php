<?php

/**
 * @file
 * OpenEuropa Corporate Site Information post updates.
 */

declare(strict_types = 1);

/**
 * Install Multi-value form element contrib module.
 */
function oe_corporate_site_info_post_update_00001(): void {
  \Drupal::service('module_installer')->install(['multivalue_form_element']);
}

/**
 * Fix corporate body graph key.
 */
function oe_corporate_site_info_post_update_00002(): void {
  $graph = [
    'name' => 'oe_corporate_site_info_corporate_body',
    'uri' => 'http://publications.europa.eu/resource/authority/corporate-body',
  ];
  $changed = FALSE;
  $config = \Drupal::configFactory()->getEditable('rdf_skos.graphs');
  $entity_types = $config->get('entity_types');
  foreach (['skos_concept_scheme', 'skos_concept'] as $type) {
    // Find if the graph is already configured and remove it.
    $key = array_search($graph, $entity_types[$type]);
    if ($key !== FALSE) {
      unset($entity_types[$type][$key]);
      $changed = TRUE;
    }
  }

  // Save the configuration only if graph was removed.
  if ($changed) {
    $config->set('entity_types', $entity_types)->save();
    \Drupal::configFactory()->clearStaticCache();
  }

  // Add the correct graph.
  \Drupal::service('rdf_skos.skos_graph_configurator')->addGraphs([
    'corporate_body' => 'http://publications.europa.eu/resource/authority/corporate-body',
  ]);
}
