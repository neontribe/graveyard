<?php

namespace Drupal\nt8booking_details\Form;

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
class NT8BookingDetailsForm extends FormBase {


  const BOOKING_COOKIE = 'NT2_Booking_Cookie';

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

  protected $eventDispatcher;

  /**
   * {@inheritdoc}
   */
  public function __construct(NT8TabsRestService $nt8TabsRestService, NT8BookingService $nt8bookingService, EventDispatcherInterface $event_dispatcher) {
    $this->nt8TabsRestService = $nt8TabsRestService;
    $this->nt8bookingService = $nt8bookingService;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      // Load the service required to construct this class.
      $container->get('nt8tabsio.tabs_service'), $container->get('nt8booking.service'), $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nt8booking_details_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $primary_traveller = NT2BookingDetailsPrimaryTraveller::buildForm($form, $form_state);
//    $party_details = NT2BookingDetailsPartyDetails::buildForm($form, $form_state);
//    $extras = NT2BookingDetailsExtras::form(buildForm, $form_state);
//    $finalize = NT2BookingDetailsFinalize::buildForm($form, $form_state);
//    $buttons = array();
//
//    $buttons['update_all'] = array(
//      '#type' => 'button',
//      '#value' => t('Update Booking'),
//      '#submit' => array('nt2_booking_details_form_submit'),
//      '#name' => 'update_all',
//      // '#limit_validation_errors' => nt2_booking_details_limit_validation_list($form),
//      '#executes_submit_callback' => TRUE,
//      '#weight' => 1000,
//    );
//
//    $buttons['proceed'] = array(
//      '#type' => 'submit',
//      '#name' => 'proceed',
//      '#value' => t('Proceed to payment'),
//      '#weight' => 1010,
//    );
//
//    $form = array_merge($primary_traveller, $party_details, $extras, $finalize, $buttons);
//    // Check if booking is confirmed, if so make form read-only.
//    $booking = NT2BookingDetails::getBooking($form_state);
//    $booking_id = $booking->getBookingId();
//    if ($booking->getConfirmation()['status']) {
//      foreach ($form as $fkey => $fieldset) {
//        foreach ($fieldset as $ekey => $element) {
//          if (is_array($element)) {
//            $form[$fkey][$ekey]['#disabled'] = TRUE;
//          }
//        }
//      }
//      unset($form['finalize']['pay_deposit']);
//      unset($form['finalize']['tnc']);
//    }
//
//    // Helper css, you can unset this using hook_css_alter.
//    $form['#attached'] = array(
//      'css' => array(
//        array(
//          'data' => '#edit-primary-traveller .form-item { padding-right: 20px; display: inline; float: left; }',
//          'type' => 'inline',
//        ),
//        array(
//          'data' => '#edit-travellers .form-item { padding-right: 20px; display: inline; float: left; }',
//          'type' => 'inline',
//        ),
//        array(
//          'data' => '#edit-extras .form-item { padding-right: 20px; display: inline; float: left; }',
//          'type' => 'inline',
//        ),
//      ),
//    );

    $form['#cache'] = ['max-age' => 0];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
//    NT2BookingDetails::save($form_state);
//    NT2BookingDetailsPrimaryTraveller::validate($form, $form_state);
//    NT2BookingDetailsPartyDetails::validate($form, $form_state);
    // NT2BookingDetailsExtras::validate($form, $form_state); No validation needed.
//    NT2BookingDetailsFinalize::validate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
//    NT2BookingDetailsPrimaryTraveller::submit($form, $form_state);
//    NT2BookingDetailsPartyDetails::submit($form, $form_state);
//    NT2BookingDetailsExtras::submit($form, $form_state);
//    NT2BookingDetailsFinalize::submit($form, $form_state);
//
//    $booking = NT2BookingDetails::getBooking($form_state);
//    $booking_id = $booking->getBookingId();
//
//    $details = $booking->getPartyDetails();
//    $customer = $booking->getCustomer();
//    if (!$details || empty($details)) {
//      $party = array(
//        'party' => array(
//          array(
//            'firstName' => $customer['name']['firstName'],
//            'surname' => $customer['name']['surname'],
//            'age' => 'Unkown',
//            'type' => 'adult',
//            'title' => $customer['name']['title'],
//          ),
//        ),
//      );
//      NT2BookingDetails::updateParty($booking, $party);
//    }
//
//    $booking = NTBooking::getBooking($booking_id);
//    $clicked_button = $form_state['clicked_button']['#name'];
//
//    if ($clicked_button == 'proceed') {
//      if ($form_state['values']['tnc']) {
//        $path = 'nt2_booking_payment/' . $booking_id;
//        $form_state['redirect'] = array(
//          $path,
//          array(
//            'query' => array(
//              'deposit_only' => isset($form_state['values']['pay_deposit']),
//              'amount' => isset($form_state['values']['pay_deposit']),
//            ),
//          ),
//        );
//        $form_state['rebuild'] = FALSE;
//      }
//      else {
//        form_set_error('tnc', t('You must accept the terms and conditions'));
//      }
//    }
  }

  /*
   * Utility function inherrited by the sub forms.
   */

  public function getBookingValue($name, $booking, $values, $default) {
    // Search the form values trump others.
    $value = $this->ternary($values, $name, FALSE);
    if ($value) {
      return $value;
    }

    // Search the booking for the element.
    $booking_val = $this->ternary($booking, $name, FALSE);
    if ($booking_val) {
      return $booking_val;
    }

    $cookie = $this->ternary($_SESSION, self::BOOKING_COOKIE, FALSE);
    if ($cookie && isset($cookie[$name])) {
      return $cookie[$name];
    }

    // Try and find a variable that matches or return the default.
    $var = variable_get('nt2_booking_details_default_value_' . $name, $default);
    return $var;
  }

  /**
   * Take a double delimited list and burst it into a name => value array.
   *
   * TODO: Abstract this into a help class/module.
   *
   * @param string $var
   *   The source data.
   * @param string $row_delimiter
   *   The delimiter for the row.
   * @param string $delimiter
   *   The delimiter for the name/value pair.
   *
   * @return array
   *   The associative array od the variable.
   */
  public function splitVar($var, $row_delimiter = "\n", $delimiter = '|') {
    $data = array();
    $rows = explode($row_delimiter, $var);
    foreach ($rows as $row) {
      list($key, $value) = explode('|', $row);
      $data[$key] = $value;
    }
    return $data;
  }

  public function buildTextField($title, $size, $default_value, $required = TRUE) {
    $field = array(
      '#title' => $title,
      '#required' => $required,
      '#type' => 'textfield',
      '#size' => $size,
      '#default_value' => $default_value,
    );
    return $field;
  }

  public function save($form_state) {
    $values = $form_state['values'];
    $_SESSION[self::BOOKING_COOKIE] = $values;
  }

  /**
   * Get the current booking firsat from the form state, else the drupal path.
   *
   * @param array $form_state
   *   The current form state.
   *
   * @return NTBooking
   *   The booking object.
   *
   * @throws RuntimeException
   */
  public function getBooking(&$form_state) {
    if (isset($form_state['booking_id'])) {
      // The booking ID is set in the form state.
      $booking_id = $form_state['booking_id'];
    }
    else {
      // Is it passed as a $_GET param?
      $booking_id = filter_input(INPUT_GET, 'booking_id');

      if (!$booking_id) {
        // It's not in the form state or the get params, try and get it from the URL path.
        $request_uri = request_uri();
        if (strpos($request_uri, '?') !== FALSE) {
          $path = substr($request_uri, 0, strpos($request_uri, '?'));
        }
        else {
          $path = $request_uri;
        }
        $url_comp = explode('/', $path);
        $booking_id = FALSE;
        foreach ($url_comp as $value) {
          if (strlen($value) == 32) {
            if (preg_match('/^[a-f0-9]{32}$/i', $value)) {
              $booking_id = $value;
              break;
            }
          }
        }
      }
    }

    $booking = FALSE;
    if ($booking_id) {
      $form_state['booking_id'] = $booking_id;
      $booking = $this->getCache($booking_id);
      if (!$booking) {
        $booking = NTBooking::getBooking($booking_id);
        $this->setCache($booking_id, $booking);
      }
    }

    return $booking;
  }

  public function setCache($id, $data) {
    cache_set($id, $data, 'cache', REQUEST_TIME + 60);
  }

  public function getCache($id) {
    $booking = &drupal_static(__FUNCTION__);

    if (!isset($booking)) {
      $cache = $cache = cache_get($id);
      if ($cache && !empty($cache->data) && REQUEST_TIME < $cache->expire) {
        $booking = $cache->data;
      }
    }
    return $booking;
  }

  public function shardPartyDetails($partyDetails) {
    $_details = [
      'adult' => [],
      'child' => [],
      'infant' => [],
    ];

    foreach ($partyDetails as $detail) {
      $_details[$detail['type']][] = $detail;
    }

    return $_details;
  }

  /**
   * Make a customer array ready to send to TABS.
   *
   * @param array $values
   *   The form values.
   *
   * @return array
   *   The customer object.
   */
  public function makeCustomer($values, &$errors = array()) {
    $customer = array(
      'name' => array(
        'title' => $this->ternary($values, 'title', 'Mr'),
        'firstName' => $this->ternary($values, 'firstName', FALSE),
        'surname' => $this->ternary($values, 'surname', FALSE),
      ),
      'address' => array(
        'addr1' => $this->ternary($values, 'addr1', ''),
        'addr2' => $this->ternary($values, 'addr2', ''),
        'town' => $this->ternary($values, 'city', ''),
        'county' => $this->ternary($values, 'county', ''),
        'postcode' => $this->ternary($values, 'postcode', ''),
        'country' => $this->ternary($values, 'country', ''),
      ),
      'daytimePhone' => $this->ternary($values, 'daytime_phone', ''),
      'eveningPhone' => $this->ternary($values, 'evening_phone', ''),
      'mobilePhone' => $this->ternary($values, 'mobile_phone', ''),
      'email' => $this->ternary($values, 'email1', ''),
      'emailOptIn' => $this->ternary($values, 'optin', FALSE)?TRUE:FALSE,
      'source' => $this->ternary($values, 'source', ''),
      'which' => $this->ternary($values, 'source_other', ''),
    );

    if (!$customer['name']['firstName']) {
      $errors['First Name'] = t('Name cannot be null');
    }
    if (!$customer['name']['surname']) {
      $errors['surname'] = t('Surname cannot be null');
    }

    return $customer;
  }

  public function ternary($var, $idx, $def) {
    if (is_object($var)) {
      watchdog(__METHOD__, t('Object passed.'));
      return 0;
    }
    return isset($var[$idx])?$var[$idx]:$def;
  }

  public function updateCustomer($booking, $customer) {
    return $this->put($booking, $customer, 'customer');
  }

  public function updateParty($booking, $party) {
    return $this->put($booking, $party, 'party');
  }

  protected function put($booking, $payload, $method) {
    $booking_id = $booking->getBookingId();
    $path = '/booking/' . $booking_id . '/' . $method;
    $data = $booking->put($path, $payload);

    $code = $booking->getLastCode();
    module_invoke_all('nt2_booking_details_put', $booking, $payload, $method, $data, $code);

    if ($code !== 204) {
      $error = $booking->getLastError();
      watchdog(__METHOD__, json_encode($error), array(), WATCHDOG_ERROR);
      // TODO Better handling...
      drupal_set_message('API Error: ' . json_encode($error), 'error');

      return FALSE;
    }
    NT2BookingDetails::setCache($booking_id, FALSE);

    return TRUE;
  }

  /**
   * Get the list of ages for the drop down.
   *
   * @return array
   *   The array of aggregated ages.
   */
  public function getAgesList($age = 'all') {
    $infants = variable_get('nt2_booking_details_age_infant', "0-2|2 and under");
    $children = variable_get('nt2_booking_details_age_child', "3-8|3 to 8 Years\n9-12|9 to 12 years\n13-17|13 to 17 Years");
    $adults = variable_get('nt2_booking_details_age_adult', "18-29|18 to 29 Years\n9-12|30 to 44 years\n45-59|45 to 59 Years\n60+|60+ Years");

    $_infants = $this->splitVar($infants);
    $_children = $this->splitVar($children);
    $_adults = $this->splitVar($adults);

    switch ($age) {
      case 'adults':
      case 'adult':
        $ages = $_adults;
        break;

      case 'children':
      case 'child':
        $ages = $_children;
        break;

      case 'infants':
      case 'infant':
        $ages = $_infants;
        break;

      case 'all':
      default:
        $ages = $_infants + $_children + $_adults;
        break;
    }

    return $ages;
  }

  /**
   * Re-map and index array to an associative on keyd by code.
   *
   * Many TABS data elements are returned as number index arrays but have a UID
   * code in each data element.  This returns an associative array of the data
   * keyed by the code.
   *
   * @param array $raw
   *   An array of data, with at least one tier two key of code.
   *
   * @return array
   *   An array of data now keyed by the code field.
   */
  public function mapCodedArray($raw) {
    $mapped = array();

    foreach ($raw as $ele) {
      $mapped[$ele['code']] = $ele;
    }

    return $mapped;
  }

  /**
   * Get a list of 'where did you hear about us' codes ready for a select list.
   *
   * @return array
   *   The list.
   */
  public function getSourceCodes() {
    $raw_sources = Utility::getSourceCodes();
    $sources = array();
    foreach ($raw_sources as $value) {
      $sources[$value['code']] = $value['description'];
    }
    $other = variable_get('nt2_booking_details_source_other', '');
    if (!empty($other)) {
      $sources['other'] = $other;
    }
    return $sources;
  }

}
