<?php

namespace Drupal\nt8booking\Form;

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
class NT8BookingAdminForm extends FormBase {

  /**
   * Instance of NT8BookingService.
   *
   * @var \Drupal\nt8booking_enquiry\Service\NT8BookingService
   */
  protected $nt8bookingService;

  /**
   * {@inheritdoc}
   */
  public function __construct(NT8BookingService $nt8bookingService) {
    $this->nt8bookingService = $nt8bookingService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('nt8booking.service')
    );
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['booking_id'] = [
      '#type' => 'textfield',
      '#title' => t('Fetch booking'),
      '#description' => t('Enter the booking id and click go to load the booking.'),
      '#default_value' => '05be6bb04f433eca2d4e8475ee9a8c6b',
    ];

    $storage = $form_state->getStorage();
    $booking = $storage['booking'] ?:FALSE;
    if ($booking) {
      $form['booking'] = [
        '#type' => 'markup',
        '#prefix' => '<pre id="nt8booking-booking-json">',
        '#suffix' => '</pre>',
        '#markup' => '',
        '#attached' => array(
          'library' => array('nt8booking/nt8booking-json-prettifier'),
          'drupalSettings' => array(
            'nt8booking' => array(
              'booking' => json_decode($booking, TRUE),
            )
          ),
        ),
      ];
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Go'),
      '#button_type' => 'primary',
    ];

    $form['#cache'] = ['max-age' => 0];

    return $form;
  }

  public function getFormId() {
    return __CLASS__;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $bookingId = $form_state->getValue('booking_id', FALSE);
    if ($bookingId) {
      $data = $this->nt8bookingService->getBooking($bookingId);
      $form_state->setStorage(array('booking' => $data));
      $form_state->setRebuild();
    }
  }

}
