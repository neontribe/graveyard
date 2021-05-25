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
class NT8BookingDetailsAdminPartyDetailsForm extends FormBase {

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('nt8booking_details.settings');

    $adult_ages = $config->get('adult_ages');
    $child_ages = $config->get('child_ages');
    $infant_ages = $config->get('infant_ages');

    $form['adult_ages'] = array(
      '#type' => 'textarea',
      '#title' => t('Adult age bands'),
      '#default_value' => json_encode(json_decode($adult_ages), JSON_PRETTY_PRINT),
      '#rows' => 8,
      '#description' => t('JSON encoded array of age bands for adults.'),
    );

    $form['child_ages'] = array(
      '#type' => 'textarea',
      '#title' => t('Child age bands'),
      '#default_value' => json_encode(json_decode($child_ages), JSON_PRETTY_PRINT),
      '#rows' => 8,
      '#description' => t('JSON encoded array of age bands for adults.'),
    );

    $form['infant_ages'] = array(
      '#type' => 'textarea',
      '#title' => t('Infant age bands'),
      '#default_value' => json_encode(json_decode($infant_ages), JSON_PRETTY_PRINT),
      '#rows' => 8,
      '#description' => t('JSON encoded array of age bands for adults.'),
    );


    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Settings'),
      '#button_type' => 'primary',
    ];

    $form['#cache'] = ['max-age' => 0];

    return $form;
  }

  public function getFormId() {
    return __CLASS__;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $age_list = $form_state->getValue('adult_ages');
    $data = json_decode($age_list, TRUE);
    if (!$data) {
      $form_state->setErrorByName('adult_ages', t('Invalid JSON'));
    }

    $age_list = $form_state->getValue('child_ages');
    $data = json_decode($age_list, TRUE);
    if (!$data) {
      $form_state->setErrorByName('child_ages', t('Invalid JSON'));
    }

    $age_list = $form_state->getValue('infant_ages');
    $data = json_decode($age_list, TRUE);
    if (!$data) {
      $form_state->setErrorByName('infant_ages', t('Invalid JSON'));
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $adult_ages = $form_state->getValue('adult_ages');
    \Drupal::configFactory()->getEditable('nt8booking_details.settings')
      ->set('adult_ages', $adult_ages)
      ->save();

    $child_ages = $form_state->getValue('child_ages');
    \Drupal::configFactory()->getEditable('nt8booking_details.settings')
      ->set('child_ages', $child_ages)
      ->save();

    $infant_ages = $form_state->getValue('infant_ages');
    \Drupal::configFactory()->getEditable('nt8booking_details.settings')
      ->set('infant_ages', $infant_ages)
      ->save();
  }

}
