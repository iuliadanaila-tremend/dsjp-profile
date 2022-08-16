<?php

declare(strict_types = 1);

namespace Drupal\Tests\rdf_skos\Behat;

use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Drupal\Core\Database\Database;
use Drupal\DrupalExtension\Context\ConfigContext;
use Drupal\Tests\rdf_skos\Traits\SkosImportTrait;

/**
 * Defines step definitions that are generally useful in this project.
 *
 * We are extending ConfigContext to override the setConfig() method until
 * issue https://github.com/jhedstrom/drupalextension/issues/498 is fixed.
 *
 * @todo Extend DrupalRawContext and gather the config context when the above
 * issue is fixed.
 */
class FeatureContext extends ConfigContext {

  use SkosImportTrait;

  /**
   * Imports the SKOS test graphs and configures it to be used.
   *
   * @param \Behat\Behat\Hook\Scope\BeforeScenarioScope $beforeScenarioScope
   *   The scope.
   *
   * @BeforeScenario @skos
   */
  public function importSkosContent(BeforeScenarioScope $beforeScenarioScope): void {
    $sparql = Database::getConnection('default', 'sparql_default');
    $base_url = $this->getMinkParameter('base_url');
    $this->import($base_url, $sparql, 'behat');
    $graphs = $this->getTestGraphInfo($base_url, 'behat');
    $config = [];
    foreach ($graphs as $name => $graph) {
      $config['skos_concept_scheme'][] = [
        'name' => $name,
        'uri' => $graph['uri'],
      ];

      $config['skos_concept'][] = [
        'name' => $name,
        'uri' => $graph['uri'],
      ];
    }

    $this->setConfig('rdf_skos.graphs', 'entity_types', $config);
  }

  /**
   * Deletes the SKOS test graphs.
   *
   * @param \Behat\Behat\Hook\Scope\AfterScenarioScope $afterScenarioScope
   *   The scope.
   *
   * @AfterScenario @skos
   */
  public function deleteSkosContent(AfterScenarioScope $afterScenarioScope): void {
    $sparql = Database::getConnection('default', 'sparql_default');
    $base_url = $this->getMinkParameter('base_url');
    $this->clear($base_url, $sparql, 'behat');
  }

  /**
   * {@inheritdoc}
   *
   * @todo Remove when https://github.com/jhedstrom/drupalextension/issues/498
   * gets fixed.
   */
  public function setConfig($name, $key, $value): void {
    $backup = $this->getDriver()->configGet($name, $key);
    $this->getDriver()->configSet($name, $key, $value);
    if (!array_key_exists($name, $this->config)) {
      $this->config[$name][$key] = $backup;

      return;
    }

    if (!array_key_exists($key, $this->config[$name])) {
      $this->config[$name][$key] = $backup;
    }
  }

}
