<?php

namespace Drupal\nt8booking_details\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\nt8tabsio\Service\NT8TabsRestService;

/**
 * Description of NT8BookingDetailsController.
 *
 * @author tobias@neontribe.co.uk
 */
class NT8BookingDetailsController extends ControllerBase {

  /**
   * Instance of NT8TabsRestService.
   *
   * @var \Drupal\nttabsio\Service\NTTabsRestService
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

  /**
   * A sample status page.
   */
  public function details() {
    $form = \Drupal::formBuilder()->getForm('Drupal\nt8booking_details\Form\NT8BookingDetailsForm');
    return $form;
  }

}
