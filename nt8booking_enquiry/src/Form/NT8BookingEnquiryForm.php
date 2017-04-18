<?php

namespace Drupal\nt8booking_enquiry\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\nt8tabsio\Service\NT8TabsRestService;
use Drupal\nt8booking_enquiry\Service\NT8BookingService;

/**
 * The booking path Enquiry form.
 */
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
        'property', ['pageSize' => 9999, 'fields' => 'propertyRef:name']
      );
      $data = json_decode($rawdata, TRUE);
      $proprefs = [];
      foreach ($data['results'] as $value) {
        $proprefs[$value['propertyRef']] = $value['name'];
      }
      asort($proprefs);
      $form['propref'] = [
        '#type' => 'select',
        '#title' => 'Choose property',
        '#options' => $proprefs,
      ];
    }
    else {
      $form['propref'] = [
        '#type' => 'hidden',
        '#value' => $propref,
      ];
    }

    $form['from'] = [
      '#type' => 'date',
      '#required' => TRUE,
      '#default_value' => DrupalDateTime::createFromTimestamp(strtotime('+2 weeks')),
      '#date_date_format' => 'd-m-Y',
    ];

    $duration = $form_state->getValue('duration');
    $form['duration'] = [
      '#type' => 'select',
      '#title' => t('Length of stay'),
      '#options' => [
        '3' => t('3 nights'),
        '7' => t('7 nights'),
        '14' => t('14 nights'),
      ],
      '#default_value' => $duration ?:'7',
    ];

    $form['adults'] = [
      '#title' => t('Adults'),
      '#required' => TRUE,
      '#type' => 'textfield',
      '#size' => 10,
      '#default_value' => '1',
    ];

    $form['children'] = [
      '#title' => t('Children'),
      '#type' => 'textfield',
      '#size' => 10,
      '#default_value' => '0',
    ];

    $form['infants'] = [
      '#title' => t('Infants'),
      '#type' => 'textfield',
      '#size' => 10,
      '#default_value' => '0',
    ];

    // TODO: Check if property takes pets.
    $form['pets'] = [
      '#title' => t('Pets'),
      '#type' => 'textfield',
      '#size' => 10,
      '#default_value' => '0',
    ];

    $form['enquire'] = [
      '#type' => 'button',
      '#value' => 'Enquire',
      '#ajax' => [
        'callback' => 'Drupal\nt8booking_enquiry\Form\NT8BookingEnquiryForm::ajaxEnquire',
        'wrapper' => 'nt2-booking-enquiry-results',
        'method' => 'html',
        'effect' => 'fade',
        // 'event' => 'keyup',.
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
      ],
    ];

    $form['results'] = [
      '#type' => 'container',
      '#id' => 'nt2-booking-enquiry-results',
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Book Now'),
      '#name' => 'book_now',
      '#id' => 'nt2-booking-book-now-btn',
      '#button_type' => 'primary',
      '#attributes' => ['disabled' => 'disabled'],
    ];

    $form['#cache'] = ['max-age' => 0];

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

  /**
   * Ajax call back to make the actual enquiry.
   */
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
        new InvokeCommand('#nt2-booking-book-now-btn', 'attr', ['disabled', TRUE])
      );
    }
    else {
      $text = '&pound;' . $data['price']['totalPrice'];
      $color = 'green';
      // Enable the book button.
      $ajax_response->addCommand(
        new InvokeCommand('#nt2-booking-book-now-btn', 'attr', ['disabled', FALSE])
      );
    }

    $ajax_response->addAttachments(['data' => $data]);
    $ajax_response->addCommand(new HtmlCommand('#nt2-booking-enquiry-results', $text));
    $ajax_response->addCommand(new InvokeCommand('#nt2-booking-enquiry-results', 'css', ['color', $color]));

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
