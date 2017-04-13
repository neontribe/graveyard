<?php

/**
 * @file
 * Contains \Drupal\resume\Form\WorkForm.
 */

namespace Drupal\nt8booking_enquiry\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\nt8tabsio\Service\NT8TabsRestService;
use Drupal\nt8booking_enquiry\Service\NT8BookingEnquiryService;

class NT8BookingEnquiryForm extends FormBase {

  /**
   * Instance of NT8TabsRestService.
   *
   * @var \Drupal\nttabsio\Service\NTTabsRestService
   */
  protected $nt8TabsRestService;

  /**
   * Instance of NT8BookingEnquiryService.
   *
   * @var \Drupal\nt8booking_enquiry\Service\NT8BookingEnquiryService
   */
  protected $nt8bookingEnquityService;

  /**
   * {@inheritdoc}
   */
  public function __construct(NT8TabsRestService $nt8TabsRestService, NT8BookingEnquiryService $nt8bookingEnquityService) {
    $this->nt8TabsRestService = $nt8TabsRestService;
    $this->nt8bookingEnquityService = $nt8bookingEnquityService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      // Load the service required to construct this class.
      $container->get('nt8tabsio.tabs_service'), $container->get('nt8booking_enquiry.service')
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

    $propref = $form_state->getValue('propref');
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
        '#value' => isset($propref) ? : $propref,
      );
    }

    $form['from'] = array(
      '#type' => 'date',
      '#required' => TRUE,
      '#date_format' => 'd-m-Y',
      // '#pre_render' => array('nt2_booking_enquiry_date_prerender'),
    );

    $date = $form_state->getValue('duration');
    $form['duration'] = array(
      '#type' => 'select',
      '#title' => t('Length of stay'),
      '#options' => array(
        '3' => t('3 nights'),
        '7' => t('7 nights'),
        '14' => t('14 nights'),
      ),
      '#default_value' => isset($date)? : $date,
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

    $form['book'] = array(
      '#type' => 'submit',
      '#value' => t('Book Now'),
      '#name' => 'book_now',
      '#id' => 'nt2-booking-book-now-btn',
      '#attributes' => array('disabled' => 'disabled'),
    );

    $form['enquire'] = array(
      '#type' => 'button',
      '#value' => 'Enquire',
      '#ajax' => array(
        'wrapper' => 'nt2-booking-enquiry-results',
        'method' => 'html',
        'effect' => 'fade',
        'callback' => 'Drupal\nt8booking_enquiry\Form\NT8BookingEnquiryForm::ajaxEnquire',
        'event' => 'keyup',
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
      '#value' => $this->t('Register'),
      '#button_type' => 'primary',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message($this->t('@emp_name ,Your application is being submitted!', array('@emp_name' => $form_state->getValue('employee_name'))));
  }

  public function ajaxEnquire(array &$form, FormStateInterface $form_state) {
    // Instantiate an AjaxResponse Object to return.
    $ajax_response = new AjaxResponse();

    $propref = $form_state->getValue('user_name');
    $from_date = $form_state->getValue('user_name');
    $to_date = $form_state->getValue('user_name');
    $party_size = $form_state->getValue('user_name');
    $pets = $form_state->getValue('user_name');


    // Check if Username exists and is not Anonymous User ('').
    if (TRUE) {
      $text = 'User Found';
      $color = 'green';
    }
    else {
      $text = 'No User Found';
      $color = 'red';
    }

    // Add a command to execute on form, jQuery .html() replaces content between tags.
    // In this case, we replace the desription with wheter the username was found or not.
    $ajax_response->addCommand(new HtmlCommand('#edit-user-name--description', $text));

    // CssCommand did not work.
    //$ajax_response->addCommand(new CssCommand('#edit-user-name--description', array('color', $color)));
    // Add a command, InvokeCommand, which allows for custom jQuery commands.
    // In this case, we alter the color of the description.
    $ajax_response->addCommand(new InvokeCommand('#edit-user-name--description', 'css', array('color', $color)));

    // Return the AjaxResponse Object.
    return $ajax_response;
  }

}
