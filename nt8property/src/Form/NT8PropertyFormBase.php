<?php

namespace Drupal\nt8property\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements an example form.
 */
class NT8PropertyFormBase extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nt8property_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['path_to_fixture'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Path to Property Fixture'),
      '#default_value' => 'H610_ZZ'
    );
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Load'),
      '#button_type' => 'primary',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $propref = $form['path_to_fixture']['#value'];

    $req_path = Url::fromRoute('property.getFixture', ['propRef' => $propref], ['absolute' => TRUE])->toString();

    $response = \Drupal::httpClient()->get($req_path, []);
    $data = json_decode($response->getBody());

    //TODO: This needs to be shipped out into a service and not left in the submit handler.

    $brandcode = $data->brandCode;
    $brandcode_info = $data->brands->{$brandcode};

    $address = $data->address;

    $pricing = json_encode(
      $brandcode_info->pricing
    );

    // Use the entity manager.
    $node = \Drupal::entityTypeManager()->getStorage('node')->create(
      array(
        'type' => 'property',
        'title' => "$data->name",
        'field_cottage_name' => $data->name,
        'field_cottage_brandcode' => $brandcode,
        'field_cottage_slug' => $data->slug,
        'field_cottage_ownercode' => $data->ownerCode,
        'field_cottage_url' => $data->url,
        'field_cottage_teaser_description' => $brandcode_info->teaser,
        'field_cottage_reference_code' => $data->propertyRef,
        'field_cottage_booking' => $data->booking,
        'field_cottage_accommodates' => $data->accommodates,
        'field_cottage_pets' => $data->pets,
        'field_cottage_bedrooms' => $data->bedrooms,
        'field_cottage_promote' => $data->promote,
        'field_cottage_rating' => $data->rating,
        'field_cottage_changeover_day' => $data->changeOverDay,
        'field_cottage_pricing' => $pricing,
        'field_cottage_coordinates' => [
          $data->coordinates->latitude,
          $data->coordinates->longitude,
        ],
        'field_cottage_address' => [
          'address_line1' => $address->addr1,
          'address_line2' => $address->addr2,
          'locality' => $address->town,
          'administrative_area' => $address->county,
          'postal_code' => $address->postcode,
          'country_code' => $address->country,
        ]
      )
    );
    $node->enforceIsNew();
    $node->save();
  }

}