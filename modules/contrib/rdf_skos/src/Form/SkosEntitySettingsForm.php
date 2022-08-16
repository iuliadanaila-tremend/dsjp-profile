<?php

declare(strict_types = 1);

namespace Drupal\rdf_skos\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Based class for the SKOS entity settings forms.
 */
abstract class SkosEntitySettingsForm extends FormBase {

  /**
   * SkosEntitySettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * Returns the entity type ID.
   */
  abstract protected function getEntityTypeId(): string;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->configFactory->get('rdf_skos.graphs')->get('entity_types.' . $this->getEntityTypeId());
    $default = '';
    if ($config) {
      foreach ($config as &$graph) {
        $graph = implode('|', $graph);
      }
      $default = implode("\r\n", $config);
    }
    $form['graphs'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Graphs'),
      '#description' => $this->t('The graph machine names and URIs of where the entities can be found. One set per line, separated by a pipe. For example: "my_graph_name|http://example.com/my-graph-name".'),
      '#default_value' => $default ? implode("\r\n", $config) : '',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $value = $form_state->getValue('graphs');
    if ($value !== '') {
      $exploded = explode("\r\n", $value);
      $graphs = [];
      foreach ($exploded as $line) {
        list($name, $uri) = explode('|', $line);
        $graphs[] = [
          'name' => $name,
          'uri' => $uri,
        ];
      }
    }
    else {
      $graphs = [];
    }

    $this->configFactory->getEditable('rdf_skos.graphs')
      ->set('entity_types.' . $this->getEntityTypeId(), $graphs)
      ->save();

    $this->messenger()->addMessage($this->t('The form has been saved.'));
  }

}
