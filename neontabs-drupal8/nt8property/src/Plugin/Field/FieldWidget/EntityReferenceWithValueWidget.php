<?php

namespace Drupal\nt8property\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;

/**
 * Plugin implementation of the 'compound_reference_widget' widget.
 *
 * @FieldWidget(
 *   id = "entity_reference_with_value_autocomplete",
 *   module = "nt8property",
 *   label = @Translation("Modeling a reference with additional fields."),
 *   field_types = {
 *     "entity_reference_with_value"
 *   }
 * )
 */
class EntityReferenceWithValueWidget extends EntityReferenceAutocompleteWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['value'] = [
      '#type' => 'textfield',
      '#title' => t('Value'),
      '#weight' => 100,
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#description' => t('Set the value tied to this entity reference.'),
    ];

    return $element;
  }

}
