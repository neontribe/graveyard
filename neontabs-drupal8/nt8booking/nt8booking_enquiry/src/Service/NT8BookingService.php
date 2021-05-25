<?php

namespace Drupal\nt8booking_enquiry\Service;

use Drupal\nt8tabsio\Service\NT8TabsRestService;

/**
 * Class nt8bookingService.
 *
 * @package Drupal\nt8_booking
 */
class NT8BookingService {

  /**
   * Instance of NT8TabsRestService.
   *
   * @var \Drupal\nt8tabsio\Service\NT8TabsRestService
   */
  protected $nt8TabsRestService;

  /**
   * {@inheritdoc}
   */
  public function __construct(NT8TabsRestService $nt8TabsRestService) {
    $this->nt8TabsRestService = $nt8TabsRestService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('nt8tabsio.tabs_service'));
  }

  public function getBooking($booingId) {
    $data = $this->nt8TabsRestService->get('booking/' . $booingId);
    return $data;
  }

  /**
   * Makes and availability enquiry against the API.
   *
   * Assumes that property is in the default site brancode.  If you want to
   * query anonther brandcode the append it to the propref.
   *
   * E.g. defaul site brand code is ZZ, the A123 or A123_ZZ will query the ZZ
   * api.  A123_XX will query the XX api.
   *
   * <code>
   * {
   *  "propertyRef": "mousecott",
   *  "brandCode": "SS",
   *  "fromDate": "2012-07-01",
   *  "toDate": "2012-07-08",
   *  "partySize": 5,
   *  "pets": 2
   *  }
   * </code>
   */
  public function enquire($propref, $from_date = FALSE, $to_date = FALSE, $party_size = 1, $pets = 0) {
    $params = [];

    list($_propref, $_brandcode) = $this->nt8TabsRestService->splitPropref($propref);
    $_from_date = $from_date ?:date('Y-m-d');
    $to_date = $to_date ? $to_date : mktime(0, 0, 0, date("m"), date("d") + 7, date("Y"));

    $params['propertyRef'] = $_propref;
    $params['brandCode'] = $_brandcode;
    $params['fromDate'] = $this->nt8TabsRestService->strToDate($_from_date);
    $params['toDate'] = $this->nt8TabsRestService->strToDate($to_date);
    $params['partySize'] = (int) $party_size;
    $params['pets'] = (int) $pets;

    $rawdata = $this->nt8TabsRestService->post('booking-enquiry', $params);
    $data = json_decode($rawdata, TRUE);

    if ($data) {
      $data['status'] = TRUE;
    }
    else {
      return [
        'status' => FALSE,
        'error' => $api->lastError,
      ];
    }

    return $data;
  }

  /**
   * Creates a booking.
   *
   * Assumes that property is in the default site brancode.  If you want to
   * query anonther brandcode the append it to the propref.
   *
   * E.g. defaul site brand code is ZZ, the A123 or A123_ZZ will query the ZZ
   * api.  A123_XX will query the XX api.
   *
   * <code>
   * {
   *  "propertyRef": "mousecott",
   *  "brandCode": "SS",
   *  "fromDate": "2012-07-01",
   *  "toDate": "2012-07-08",
   *  "adults": 2,
   *  "children": 1,
   *  "infants": 0,
   *  "pets": 2
   *  }
   * </code>
   */
  public function booking($propref, $from_date = FALSE, $to_date = FALSE, $adults = 1, $children = 1, $infants = 1, $pets = 0) {
    $params = [];

    list($_propref, $_brandcode) = $this->nt8TabsRestService->splitPropref($propref);
    $_from_date = $from_date ?: date('Y-m-d');
    $to_date = $to_date ? $to_date : mktime(0, 0, 0, date("m"), date("d") + 7, date("Y"));

    $params['propertyRef'] = $_propref;
    $params['brandCode'] = $_brandcode;
    $params['fromDate'] = $this->nt8TabsRestService->strToDate($_from_date);
    $params['toDate'] = $this->nt8TabsRestService->strToDate($to_date);
    $params['adults'] = (int) $adults;
    $params['children'] = (int) $children;
    $params['infants'] = (int) $infants;
    $params['pets'] = (int) $pets;

    $rawdata = $this->nt8TabsRestService->post('booking', $params);
    $data = json_decode($rawdata, TRUE);

    if ($data) {
      $data['status'] = TRUE;
    }
    else {
      return [
        'status' => FALSE,
        'error' => $api->lastError,
      ];
    }

    return $data;
  }

}
