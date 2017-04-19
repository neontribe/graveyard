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
use Drupal\nt8booking_enquiry\Event\NT8BookingEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The booking path details form.
 */
class NT8BookingEnquiryAdminForm extends FormBase {

  public function buildForm(array $form, FormStateInterface $form_state) {
    $error_codes = \Drupal::config('nt8booking_enquiry.settings')->get('error_codes');

    $form['error_codes'] = array(
      '#type' => 'textarea',
      '#title' => t('TABS Error Codes'),
      '#rows' => 25,
      '#description' => t('This text area translates the TABS Error code to readable text.'),
      '#default_value' => json_encode(json_decode($error_codes), JSON_PRETTY_PRINT),
    );

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Settimgs'),
      '#button_type' => 'primary',
    ];

    $form['#cache'] = ['max-age' => 0];

    return $form;
  }

  public function getFormId() {
    return __CLASS__;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $error_codes = $form_state->getValue('error_codes');
    $data = json_decode($error_codes, TRUE);
    if (!$data) {
      $form_state->setErrorByName('error_codes', t('Invalid JSON'));
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $error_codes = $form_state->getValue('error_codes');
    \Drupal::configFactory()->getEditable('nt8booking_enquiry.settings')
      ->set('error_codes', $error_codes)
      ->save();
  }

}
