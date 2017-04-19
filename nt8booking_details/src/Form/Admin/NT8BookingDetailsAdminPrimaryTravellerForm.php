<?php

namespace Drupal\nt8booking_details\Form\Admin;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\nt8tabsio\Service\NT8TabsRestService;
use Drupal\nt8booking_enquiry\Service\NT8BookingService;
use Drupal\nt8booking_enquiry\Event\NT8BookingEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The booking path details form.
 */
class NT8BookingDetailsAdminPrimaryTravellerForm extends FormBase {

  public function buildForm(array $form, FormStateInterface $form_state) {
    $titles = \Drupal::config('nt8booking_details.settings')->get('titles_list');
    $other_code = \Drupal::config('nt8booking_details.settings')->get('other_code');
    $age_list = \Drupal::config('nt8booking_details.settings')->get('age_list');
    $default_age = \Drupal::config('nt8booking_details.settings')->get('default_age');

    $form['titles_list'] = array(
      '#type' => 'textarea',
      '#title' => t('Traveller titles'),
      //'#default_value' => variable_get('nt2_booking_details_titles_list', ''),
      '#rows' => 8,
      '#description' => t('List the titles to appear in the drop down on the traveller details section.  One pair per line, separated by the | (pipe) character, e.g. Mr|Mister .'),
    );

    $form['other_code'] = array(
      '#type' => 'textfield',
      '#title' => t('Other Code'),
      //'#default_value' => variable_get('nt2_booking_details_other_code', 'other'),
      '#description' => t('The text to code to use the booking as Other, so we can skip this stage right now and complete it latter in the process.'),
    );

    $form['age_list'] = array(
      '#type' => 'select',
      '#title' => t('Default traveller age'),
      //'#options' => NT2BookingDetails::splitVar(variable_get('nt2_booking_details_age_adult', array())),
      //'#default_value' => variable_get('nt2_booking_details_age_default', ''),
      '#description' => t('Select the default age bracket for on the party details screen.'),
    );

    $form['default_age'] = array(
      '#type' => 'select',
      '#title' => t('Default traveller age'),
      //'#options' => NT2BookingDetails::splitVar(variable_get('nt2_booking_details_age_adult', array())),
      //'#default_value' => variable_get('nt2_booking_details_age_default', ''),
      '#description' => t('Select the default age bracket for on the party details screen.'),
    );

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('NT8BookingDetailsAdminPrimaryTravellerForm'),
      '#name' => 'book_now',
      '#id' => 'nt2-booking-book-now-btn',
      '#button_type' => 'primary',
      '#attributes' => ['disabled' => 'disabled'],
    ];

    $form['#cache'] = ['max-age' => 0];

    return $form;
  }

  public function getFormId() {
    return __CLASS__;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
