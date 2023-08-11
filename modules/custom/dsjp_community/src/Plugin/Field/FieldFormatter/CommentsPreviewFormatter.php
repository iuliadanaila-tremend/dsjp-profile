<?php

namespace Drupal\dsjp_community\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a custom preview comment formatter.
 *
 * @FieldFormatter(
 *   id = "dsj_comment_preview",
 *   label = @Translation("Comment preview list"),
 *   field_types = {
 *     "comment"
 *   },
 *   quickedit = {
 *     "editor" = "disabled"
 *   }
 * )
 */
class CommentsPreviewFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager property.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new CommentsPreviewFormatter.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager interface.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'number_of_comments' => 2,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $cids = $this->getLatestComments($this->getSetting('number_of_comments'), $items->getEntity()->id());
    if (!empty($cids)) {
      $comments = $this->entityTypeManager->getStorage('comment')->loadMultiple($cids);
      $elements[] = [
        'comments' => $this->entityTypeManager->getViewBuilder('comment')->viewMultiple($comments, 'dsj_listing_preview'),
      ];
    }

    return $elements;
  }

  /**
   * Gets the latest parent comments.
   *
   * @param int $numberOfComments
   *   The number of comments to fetch.
   * @param int $eid
   *   The entity id the comment was posted on.
   *
   * @return array
   *   Returns the comments ids.
   */
  protected function getLatestComments($numberOfComments, $eid) {
    $query = $this->entityTypeManager->getStorage('comment')->getQuery();
    $orCondition = $query->orConditionGroup();
    $orCondition->notExists('pid');
    $orCondition->condition('pid', 0);
    $query->condition($orCondition);
    $query->condition('entity_id', $eid);
    $query->sort('cid', 'DESC');
    $query->range(0, $numberOfComments);
    $query->accessCheck(TRUE);

    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['number_of_comments'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of comments'),
      '#default_value' => $this->getSetting('number_of_comments'),
      '#min' => 1,
    ];

    return $form;
  }

}
