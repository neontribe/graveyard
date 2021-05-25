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
    $config = \Drupal::config('nt8booking_details.settings');

    $titles_list = $config->get('titles_list');
    $other_code = $config->get('other_code');
    $default_age = $config->get('default_age');

    $form['titles_list'] = array(
      '#type' => 'textarea',
      '#title' => t('Traveller titles'),
      '#default_value' => json_encode(json_decode($titles_list), JSON_PRETTY_PRINT),
      '#rows' => 8,
      '#description' => t('List the titles to appear in the drop down on the traveller details section.  Json encoded please.'),
    );

    $form['other_code'] = array(
      '#type' => 'textfield',
      '#title' => t('Other Code'),
      '#default_value' => $other_code,
      '#description' => t('The text to code to use the booking as Other, so we can skip this stage right now and complete it latter in the process.'),
    );

    $adult_ages = json_decode($config->get('adult_ages'), TRUE);
    $child_ages = json_decode($config->get('child_ages'), TRUE);
    $infant_ages = json_decode($config->get('infant_ages'), TRUE);
    $ages = array_merge($adult_ages, $child_ages, $infant_ages);
    $form['default_age'] = array(
      '#type' => 'select',
      '#title' => t('Default traveller age'),
      '#options' => $ages,
      '#default_value' => $default_age,
      '#description' => t('Select the default age bracket for on the party details screen.'),
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
    $titles_list = $form_state->getValue('titles_list');

    $data = json_decode($titles_list, TRUE);
    if (!$data) {
      $form_state->setErrorByName('titles_list', t('Invalid JSON'));
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $titles_list = $form_state->getValue('titles_list');
    \Drupal::configFactory()->getEditable('nt8booking_details.settings')
      ->set('titles_list', $titles_list)
      ->save();

    $other_code = $form_state->getValue('other_code');
    \Drupal::configFactory()->getEditable('nt8booking_details.settings')
      ->set('other_code', $other_code)
      ->save();

    $default_age = $form_state->getValue('default_age');
    \Drupal::configFactory()->getEditable('nt8booking_details.settings')
      ->set('default_age', $default_age)
      ->save();
  }

}
