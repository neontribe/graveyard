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
class NT8BookingDetailsAdminExtrasForm extends FormBase {
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
    return new static(
      // Load the service required to construct this class.
      $container->get('nt8tabsio.tabs_service'), $container->get('nt8booking.service'), $container->get('event_dispatcher')
    );
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    // Get all attributes as $options.
    $this->nt8TabsRestService->setSuperDebug(TRUE);
    $api_info = $this->nt8TabsRestService->get('/');
    $all_atts = json_decode($api_info['constants']['attributes']);
    $attr_ops = [];
    foreach ($all_atts as $value) {
      $attr_ops[$value['code']] = t(
        '[!cod] !lab', array('!cod' => $value['code'], '!lab' => $value['label'])
      );
    }

    return $form;

    // Which of the extras are web bookable.
//    $css = drupal_get_path('module', 'nt2_booking_details') . '/css/admin.css';
//    drupal_add_css($css);
    $all_extras = $api_info['constants']['extras'];
    $cb_webextras = [];
    foreach ($all_extras as $extra) {
      $code = $extra['code'];
      $label = $extra['label'];
      $cb_webextras[$code] = format_string(
        '@label (@code)', ['@label' => $label, '@code' => $code]
      );
    }

    $form['pets'] = [
      '#type' => 'fieldset',
      '#title' => t('Pets Overrides'),
    ];

    $form['pets']['nt2_booking_details_admin_attr_pets'] = [
      '#type' => 'select',
      '#options' => $attr_ops,
      '#description' => t('Which attribute indicates the max number of pets on a property.'),
      // '#default_value' => variable_get('nt2_booking_details_admin_attr_pets', ''),
    ];

    $form['pets']['nt2_booking_details_admin_extras_pets'] = [
      '#type' => 'select',
      '#options' => $cb_webextras,
      '#description' => t('Which extra is the pet extra.'),
      // '#default_value' => variable_get('nt2_booking_details_admin_extras_pets', ''),
    ];

    $form['extras'] = [
      '#type' => 'fieldset',
      '#title' => t('Which extras should be web bookable'),
    ];

    $form['extras']['nt2_booking_details_admin_extras'] = [
      '#type' => 'checkboxes',
      '#options' => $cb_webextras,
      '#description' => t('Select which extras should appear on the booking form.'),
      //'#default_value' => variable_get('nt2_booking_details_admin_extras', array()),
    ];

    $form['extras']['apply'] = [
      '#type' => 'submit',
      '#default_value' => t('Apply Changes'),
    ];

    $form['extras']['desc'] = [
      '#type' => 'item',
      '#markup' => t('<strong>You MUST apply changes here before the extras will appear in the field set below.</strong>'),
    ];

    // Which extras are overriden by attributes on a per property basis.
    $form['attr'] = [
      '#type' => 'fieldset',
      '#title' => 'Per property overrides',
      '#description' => t('If an extra is overriden by an attribute (e.g. to set the maximum value or a custom price) the associate the extra with the correct attribute here.'),
    ];

    $attributes = $api_info['constants']['attributes'];
    array_multisort($attributes);

    // $sel = variable_get('nt2_booking_details_admin_extras', array());
    foreach ($cb_webextras as $code => $label) {
      if (isset($sel[$code]) && $sel[$code]) {
        // We need to build a custom key for each of the extras.
        $attrs = array('false' => '-- use global --');
        foreach ($attributes as $attr) {
          $attrs[$attr->getCode()] = format_string(
            '!label (@code)', ['!label' => $attr->getLabel(), '@code' => $attr->getCode()]
          );
        }

        $varname = 'nt2_booking_details_admin_extras_override_' . $code;
        $default = variable_get($varname, '');
        $form['attr'][$varname] = [
          '#type' => 'select',
          '#title' => $label,
          '#options' => $attrs,
          '#default_value' => $default,
        ];
        variable_set($varname, $default);
      }
    }

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

  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
