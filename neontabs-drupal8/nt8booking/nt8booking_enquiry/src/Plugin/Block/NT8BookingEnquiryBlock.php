<?php

namespace Drupal\nt8booking_enquiry\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\nt8tabsio\Service\NT8TabsRestService;
use Drupal\nt8booking_enquiry\Service\NT8BookingService;

/**
 * Provides a 'NT8BookingEnquiryBlock' block.
 *
 * @Block(
 *  id = "nt8booking_enquiry_block",
 *  admin_label = @Translation("NT8 Enquiry Block"),
 * )
 */
class NT8BookingEnquiryBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\nt8tabsio\Service\NT8TabsRestService definition.
   *
   * @var \Drupal\nt8tabsio\Service\NT8TabsRestService
   */
  protected $nt8tabsioTabsService;

  /**
   * Drupal\nt8map\Service\NT8MapService definition.
   *
   * @var \Drupal\nt8map\Service\NT8MapService
   */
  protected $nt8bookingService;

  /**
   * NT8MapBlock constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\nt8tabsio\Service\NT8TabsRestService $nt8tabsio_tabs_service
   *   Instance of NT8TabsRestService.
   * @param \Drupal\nt8booking_enquiry\Service\NT8BookingService $nt8bookingService
   *   Instance of NT8BookingService.
   */
  public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        NT8TabsRestService $nt8tabsio_tabs_service,
        NT8BookingService $nt8bookingService
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->nt8tabsioTabsService = $nt8tabsio_tabs_service;
    $this->nt8bookingService = $nt8bookingService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition, $container->get('nt8tabsio.tabs_service'), $container->get('nt8booking.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\nt8booking_enquiry\Form\NT8BookingEnquiryForm');
    return $form;
  }

}
