<?php

namespace Drupal\dsjp_pledge\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A widget for dsj_initiative_beneficiary.
 *
 * @FieldWidget(
 *   id = "dsj_initiative_beneficiary_widget",
 *   label = @Translation("Initiative Beneficiary widget"),
 *   field_types = {
 *     "dsj_initiative_beneficiary",
 *   }
 * )
 */
class InitiativBeneficiaryWidget extends WidgetBase {

  /**
   * The request stack service property.
   *
   * @var \Drupal\Core\Http\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->requestStack = $container->get('request_stack');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $node = $form_state->getFormObject()->getEntity();
    $variables = $this->getVariables($form_state, $node);
    $values = $this->getFieldValues();
    // Switch the cardinality based on query parameter or action field value.
    $cardinality = 5;
    if (!in_array($variables['action_type'], [
      'collaboration',
      'honorary_pledge',
    ]) && isset($values[$variables['type']])) {
      $cardinality = count($values[$variables['type']]);
    }
    $max = $cardinality - 1;
    $is_multiple = ($cardinality > 1);

    $title = $this->fieldDefinition->getLabel();
    $description = $this->getFilteredDescription();

    $elements = [];

    for ($delta = 0; $delta <= $max; $delta++) {
      // Add a new empty item if it doesn't exist yet at this delta.
      if (!isset($items[$delta])) {
        $items->appendItem();
      }

      // For multiple fields, title and description are handled by the wrapping
      // table.
      $element = [
        '#title' => $title,
        '#title_display' => 'before',
        '#description' => $description,
      ];

      $element = $this->formSingleElement($items, $delta, $element, $form, $form_state);

      if ($element) {
        // Input field for the delta (drag-n-drop reordering).
        if ($is_multiple) {
          // We name the element '_weight' to avoid clashing with elements
          // defined by widget.
          $element['_weight'] = [
            '#type' => 'weight',
            '#title' => $this->t('Weight for row @number', ['@number' => $delta + 1]),
            '#title_display' => 'invisible',
            // Note: this 'delta' is the FAPI #type 'weight' element's property.
            '#delta' => $max,
            '#default_value' => $items[$delta]->_weight ?: $delta,
            '#weight' => 100,
          ];
        }

        $elements[$delta] = $element;
      }
    }

    if ($elements) {
      $elements += [
        '#theme' => 'field_multiple_value_form',
        '#field_name' => $field_name,
        '#cardinality' => $cardinality,
        '#cardinality_multiple' => $this->fieldDefinition->getFieldStorageDefinition()->isMultiple(),
        '#required' => $this->fieldDefinition->isRequired(),
        '#title' => $this->t('Target Number'),
        '#description' => $description,
        '#max_delta' => $max,
      ];
    }

    return $elements;
  }

  /**
   * Sets default variables for cardinality calculation.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param mixed $node
   *   The node object.
   */
  public function getVariables(FormStateInterface $form_state, $node) {
    $actionType = $form_state->getValue('field_dsj_action_type') ? $form_state->getValue('field_dsj_action_type')[0]['value'] : '';
    if ($node instanceof NodeInterface && !$node->isNew()) {
      $type = $node->get('field_dsj_initiative_type')->value;
      if (!$actionType) {
        $actionType = $node->get('field_dsj_action_type')->value;
      }
    }
    else {
      $type = $this->requestStack->getCurrentRequest()->query->get('type');
    }

    return [
      'type' => $type,
      'action_type' => $actionType,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $node = $form_state->getFormObject()->getEntity();
    $variables = $node instanceof NodeInterface ? $this->getVariables($form_state, $node) : [
      'action_type' => [],
      'type' => '',
    ];
    $values = $this->getFieldValues();
    $keepDisabled = 1;
    // Switch the default values based on query parameter or action type field.
    if (!empty($variables['action_type']) && in_array($variables['action_type'], [
      'collaboration',
      'honorary_pledge',
    ])) {
      $defaultValue = NULL;
      $keepDisabled = 0;
    }
    if (isset($values[$variables['type']]) && !in_array($variables['action_type'], [
      'collaboration',
      'honorary_pledge',
    ])) {
      $defaultValue = $values[$variables['type']][$delta];
    }
    elseif (isset($items[$delta]->value)) {
      $defaultValue = $items[$delta]->value;
    }
    else {
      $defaultValue = NULL;
      $keepDisabled = 0;
    }
    $element['status'] = [
      '#type' => 'checkbox',
      '#attributes' => [
        'data-keep-disabled' => $keepDisabled,
        'data-delta' => $delta,
      ],
      '#default_value' => $items[$delta]->status ?? 0,
    ];
    $element['value'] = [
      '#type' => 'textfield',
      '#default_value' => $defaultValue,
    ];
    if ($defaultValue != NULL) {
      $element['value']['#value'] = $defaultValue;
    }
    $element['number'] = [
      '#type' => 'number',
      '#default_value' => $items[$delta]->number ?? 0,
      '#states' => [
        'disabled' => [
          ':input[name="field_dsj_initiative_beneficiary[' . $delta . '][status]"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $element['#type'] = 'fieldset';
    $element['#attached']['library'][] = 'dsjp_pledge/pledge';

    return $element;
  }

  /**
   * Gets the field values available for the beneficiaries.
   *
   * @return array
   *   Returns the array with the default values.
   */
  public function getFieldValues() {
    return [
      'ict_professionals' => [
        $this->t('Entry level'),
        $this->t('Experienced'),
        $this->t('Manager'),
        $this->t('Director'),
        $this->t('All ICT Professionals'),
      ],
      'education' => [
        $this->t('Early Years'),
        $this->t('Primary'),
        $this->t('Secondary'),
        $this->t('Tertiary'),
        $this->t('Vet'),
        $this->t('Parents'),
        $this->t('Teacher'),
        $this->t('All Education Sector Participants'),
      ],
      'labour_force' => [
        $this->t('Employed'),
        $this->t('Unemployed'),
        $this->t('Retired'),
        $this->t('Internal Employees'),
        $this->t('Refugees'),
        $this->t('All Labour Force Sector Participants'),
      ],
      'other' => [
        $this->t('All Citizen'),
      ],
    ];
  }

}
