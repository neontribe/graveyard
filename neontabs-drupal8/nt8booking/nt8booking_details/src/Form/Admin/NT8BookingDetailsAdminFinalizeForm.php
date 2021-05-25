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
class NT8BookingDetailsAdminFinalizeForm extends FormBase {

  public function buildForm(array $form, FormStateInterface $form_state) {
        $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('NT8BookingDetailsAdminFinalizeForm'),
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
