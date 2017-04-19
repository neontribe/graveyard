<?php

namespace Drupal\nt8booking_details\Form;

use Drupal\Core\Form\FormStateInterface;

class NT8BookingDetailsPrimaryTravellerForm extends NT8BookingDetailsForm {

  public function buildForm(array $form, FormStateInterface $form_state) {
    $booking = $this->getBooking($form_state);
    $booking_id = $booking->getBookingId();

    $form = array();
    $values = $form_state->getValues();

    // WISHLIST: Add support for accounts and remember users.
    $customer = $booking->getCustomer();
    if (is_array($customer) && !empty($customer)) {
      $name = self::ternary($customer, 'name', array());
      $addr = self::ternary($customer, 'address', array());
    }
    else {
      $customer = array();
      $name = array();
      $addr = array();
    }

    // Name.
    $form['primary_traveller'] = array(
      '#title' => t('Primary Traveller'),
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#weight' => 10,
    );
    $form['primary_traveller']['title'] = array(
      '#title' => t('Title'),
      '#required' => TRUE,
      '#type' => 'select',
      '#options' => self::splitVar(variable_get('nt2_booking_details_titles_list', '')),
      '#default_value' => self::getBookingValue('title', $name, $values, ''),
    );
    $form['primary_traveller']['firstName'] = self::buildTextField(t('Initial'), 1, self::getBookingValue('firstName', $name, $values, ''));
    $form['primary_traveller']['surname'] = self::buildTextField(t('Surname'), 30, self::getBookingValue('surname', $name, $values, ''));

    // Address.
    $form['address'] = array(
      '#title' => t('Address'),
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#weight' => 20,
    );
    $form['address']['addr1'] = self::buildTextField(t('First line of Address'), 30, self::getBookingValue('addr1', $addr, $values, ''));
    $form['address']['addr2'] = self::buildTextField(t('Address Line 2'), 30, self::getBookingValue('addr2', $addr, $values, ''), FALSE);
    $form['address']['city'] = self::buildTextField(t('Town/City'), 30, self::getBookingValue('city', $addr, $values, ''));
    $form['address']['county'] = self::buildTextField(t('County'), 30, self::getBookingValue('county', $addr, $values, ''));
    $form['address']['postcode'] = self::buildTextField(t('Postcode'), 30, self::getBookingValue('postcode', $addr, $values, ''));
    $form['address']['country'] = array(
      '#title' => t('Country'),
      '#required' => TRUE,
      '#type' => 'select',
      '#options' => nt2_booking_get_countries(),
      '#default_value' => self::getBookingValue('country', $addr, $values, 'GB'),
    );

    // Phones/Email.
    $form['phone_email'] = array(
      '#title' => t('Contact Details'),
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#weight' => 30,
    );
    $form['phone_email']['mobile_phone'] = self::buildTextField(t('Mobile phone number'), 30, self::getBookingValue('mobile_phone', $addr, $values, ''));
    $form['phone_email']['daytime_phone'] = self::buildTextField(t('Daytime phone number'), 30, self::getBookingValue('daytime_phone', $addr, $values, ''));
    $form['phone_email']['evening_phone'] = self::buildTextField(t('Evening phone number'), 30, self::getBookingValue('evening_phone', $addr, $values, ''));
    $form['phone_email']['email1'] = self::buildTextField(t('Email'), 30, self::getBookingValue('email1', $addr, $values, ''));
    $form['phone_email']['email2'] = self::buildTextField(t('Confirm Email'), 30, self::getBookingValue('email2', $addr, $values, ''));

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $email1 = $form_state['values']['email1'];
    $email2 = $form_state['values']['email2'];
    if ((!empty($email1) || !empty($email2)) && $email1 != $email2) {
      form_set_error('email1', t('Email addresses do not match.'));
      form_set_error('email1');
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $clicked_button = $form_state['clicked_button']['#name'];

    $booking = self::getBooking($form_state);
    $booking_id = $booking->getBookingId();

    $values = $form_state['values'];

    // Update booking.
    $errors = array();
    $customer = self::makeCustomer($values, $errors);

    $form_state['rebuild'] = self::updateCustomer($booking, $customer) || $form_state['rebuild'];
  }

}
