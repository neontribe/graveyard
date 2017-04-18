<?php

/**
 * @file
 * Contains \Drupal\resume\Form\WorkForm.
 */

namespace Drupal\nt8booking_enquiry\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ChangedCommand;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\nt8tabsio\Service\NT8TabsRestService;
use Drupal\nt8booking_enquiry\Service\NT8BookingService;

class NT8BookingEnquiryForm extends FormBase {

  /**
   * Instance of NT8TabsRestService.
   *
   * @var \Drupal\nttabsio\Service\NTTabsRestService
   */
  protected $nt8TabsRestService;

  /**
   * Instance of NT8BookingService.
   *
   * @var \Drupal\nt8booking_enquiry\Service\NT8BookingService
   */
  protected $nt8bookingService;

  /**
   * {@inheritdoc}
   */
  public function __construct(NT8TabsRestService $nt8TabsRestService, NT8BookingService $nt8bookingService) {
    $this->nt8TabsRestService = $nt8TabsRestService;
    $this->nt8bookingService = $nt8bookingService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      // Load the service required to construct this class.
      $container->get('nt8tabsio.tabs_service'), $container->get('nt8booking.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nt8booking_enquiry_form';
    }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $propref = FALSE;

    $node = \Drupal::routeMatch()->getParameter('node');
    if ($node) {
      if ($node->getType() == 'property') {
        $propref = self::getNodeFieldValue($node, 'field_cottage_reference_code', 0);
      }
    }

    if (!$propref) {
      $rawdata = $this->nt8TabsRestService->get(
        'property', array('pageSize' => 9999, 'fields' => 'propertyRef:name')
      );
      $data = json_decode($rawdata, TRUE);
      $proprefs = array();
      foreach ($data['results'] as $value) {
        $proprefs[$value['propertyRef']] = $value['name'];
      }
      asort($proprefs);
      $form['propref'] = array(
        '#type' => 'select',
        '#title' => 'Choose property',
        '#options' => $proprefs,
      );
    }
    else {
      $form['propref'] = array(
        '#type' => 'hidden',
        '#value' => $propref,
      );
    }

    $form['from'] = array(
      '#type' => 'date',
      '#required' => TRUE,
      '#default_value' => DrupalDateTime::createFromTimestamp(strtotime('+2 weeks')),
      '#date_date_format' => 'd-m-Y',
      // '#pre_render' => array('nt2_booking_enquiry_date_prerender'),
    );

    $duration = $form_state->getValue('duration');
    $form['duration'] = array(
      '#type' => 'select',
      '#title' => t('Length of stay'),
      '#options' => array(
        '3' => t('3 nights'),
        '7' => t('7 nights'),
        '14' => t('14 nights'),
      ),
      '#default_value' => $duration ? : '7',
    );

    $form['adults'] = array(
      '#title' => t('Adults'),
      '#required' => TRUE,
      '#type' => 'textfield',
      '#size' => 10,
      '#default_value' => '1',
    );

    $form['children'] = array(
      '#title' => t('Children'),
      '#type' => 'textfield',
      '#size' => 10,
      '#default_value' => '0',
    );

    $form['infants'] = array(
      '#title' => t('Infants'),
      '#type' => 'textfield',
      '#size' => 10,
      '#default_value' => '0',
    );

    // TODO: Check if property takes pets
    $form['pets'] = array(
      '#title' => t('Pets'),
      '#type' => 'textfield',
      '#size' => 10,
      '#default_value' => '0',
    );

    $form['enquire'] = array(
      '#type' => 'button',
      '#value' => 'Enquire',
      '#ajax' => array(
        'callback' => 'Drupal\nt8booking_enquiry\Form\NT8BookingEnquiryForm::ajaxEnquire',
        'wrapper' => 'nt2-booking-enquiry-results',
        'method' => 'html',
        'effect' => 'fade',
        // 'event' => 'keyup',
        'progress' => array(
          'type' => 'throbber',
          'message' => NULL,
        ),
      ),
    );

    $form['results'] = array(
      '#type' => 'container',
      '#id' => 'nt2-booking-enquiry-results',
    );

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Book Now'),
      '#name' => 'book_now',
      '#id' => 'nt2-booking-book-now-btn',
      '#button_type' => 'primary',
      '#attributes' => array('disabled' => 'disabled'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $propref = $form_state->getValue('propref');
    $from_date = $form_state->getValue('from');
    $nights = $form_state->getValue('nights');
    $to_date = $form_state->getValue('to');

    if (!$nights) {
      $nights = 7;
    }

    if (!$to_date) {
      $to_date = date('Y-m-d', strtotime('+' . $nights . ' days', strtotime($from_date)));
    }

    $adults = $form_state->getValue('adults');
    $children = $form_state->getValue('children');
    $infants = $form_state->getValue('infants');
    $pets = $form_state->getValue('pets');

    $data = $this->nt8bookingService->booking($propref, $from_date, $to_date, $adults, $children, $infants, $pets);
    \Drupal::logger(__METHOD__)->info($data);
    // NOTIFY HERE!
  }

  public static function ajaxEnquire(array &$form, FormStateInterface $form_state) {
    // Instantiate an AjaxResponse Object to return.
    $ajax_response = new AjaxResponse();

    $propref = $form_state->getValue('propref');
    $from_date = $form_state->getValue('from');
    $nights = $form_state->getValue('nights');
    $to_date = $form_state->getValue('to');

    if (!$nights) {
      $nights = 7;
    }

    if (!$to_date) {
      $to_date = date('Y-m-d', strtotime('+' . $nights . ' days', strtotime($from_date)));
    }

    $adults = $form_state->getValue('adults');
    $children = $form_state->getValue('children');
    $infants = $form_state->getValue('infants');
    $party_size = $adults + $children + $infants;

    $pets = $form_state->getValue('pets');

    // This is a static method so access the service statically.
    $enquiryService = \Drupal::service('nt8booking.service');
    $data = $enquiryService->enquire($propref, $from_date, $to_date, $party_size, $pets);

    if (isset($data['errorCode'])) {
      $errorCode = $data['errorCode'];
      $errorMesg = '';
      $color = 'red';
      $text = $data['errorDescription'];
      // Enable the book button.
      $ajax_response->addCommand(
        new InvokeCommand('#nt2-booking-book-now-btn', 'attr', array('disabled', TRUE))
      );
    }
    else {
      $text = '&pound;' . $data['price']['totalPrice'];
      $color = 'green';
      // Enable the book button.
      $ajax_response->addCommand(
        new InvokeCommand('#nt2-booking-book-now-btn', 'attr', array('disabled', FALSE))
      );
    }

    $ajax_response->addAttachments(array('data' => $data));

    // Add a command to execute on form, jQuery .html() replaces content between tags.
    // In this case, we replace the desription with wheter the username was found or not.
    $ajax_response->addCommand(new HtmlCommand('#nt2-booking-enquiry-results', $text));

    // CssCommand did not work.
    // $ajax_response->addCommand(new CssCommand('#edit-user-name--description', array('color', $color)));
    // Add a command, InvokeCommand, which allows for custom jQuery commands.
    // In this case, we alter the color of the description.
    $ajax_response->addCommand(new InvokeCommand('#nt2-booking-enquiry-results', 'css', array('color', $color)));

    // Return the AjaxResponse Object.
    return $ajax_response;
  }

  /**
   * Returns the value of a specified field on a node.
   */
  public static function getNodeFieldValue($node, $fieldName, $index = -1, $keyname = 'value') {
    // TODO Make this a globally available function.
    $field_instance = $node->get($fieldName)->getValue();
    $field_value = $field_instance;

    if ($index > -1) {
      $field_value = $field_instance[$index][$keyname];
    }

    return $field_value;
  }

}
