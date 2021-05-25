<?php

namespace Drupal\nt8property\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implementation of 'nt8property_field_cottage_pricing_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "nt8property_field_cottage_pricing_formatter",
 *   label = @Translation("Formatter for cottage pricing JSON"),
 *   field_types = {
 *     "string_long"
 *   }
 * )
 */
class NT8PropertyFieldCottagePricingFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      // Implement default settings.
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      // Implement settings form.
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $decoded_pricing_info = json_decode($item->value);
      if (isset($decoded_pricing_info, $decoded_pricing_info->ranges)) {
        $elements[$delta] = [
          '#theme' => 'field_cottage_pricing_formatter',
          '#pricing_data' => $decoded_pricing_info->ranges,
        // @TODO: Make this a config variable.
          '#year' => date("Y"),
        ];
      }

    }

    return $elements;
  }

}
