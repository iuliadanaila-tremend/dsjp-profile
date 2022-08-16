<?php

namespace Drupal\dsjp_pledge\Plugin\views\filter;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filters by phase or status of project.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("dsj_pledge_initiative_beneficiary")
 */
class PledgeInitiativeBeneficiary extends InOperator {

  /**
   * The database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->database = $container->get('database');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->definition['options callback'] = [$this, 'generateOptions'];
  }

  /**
   * {@inheritdoc}
   */
  public function generateOptions() {
    $options = [];
    $query = $this->database->select('node__field_dsj_initiative_beneficiary', 'b')
      ->fields('b', ['field_dsj_initiative_beneficiary_value'])
      ->condition('b.field_dsj_initiative_beneficiary_status', 1)
      ->distinct()
      ->execute();
    $results = $query->fetchAll(\PDO::FETCH_ASSOC);

    if (!empty($results)) {
      foreach ($results as $value) {
        $options[$value['field_dsj_initiative_beneficiary_value']] = $value['field_dsj_initiative_beneficiary_value'];
      }
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function opSimple() {
    if (empty($this->value)) {
      return;
    }
    $this->ensureMyTable();

    // We use array_values() because the checkboxes keep keys and that can cause
    // array addition problems.
    $this->query->addWhere($this->options['group'], "$this->tableAlias.$this->realField", array_values($this->value), $this->operator);
    $this->query->addWhere($this->options['group'], "$this->tableAlias.field_dsj_initiative_beneficiary_status", 1);
  }

}
