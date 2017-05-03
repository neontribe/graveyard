<?php

namespace Drupal\nt8propertyshortlist\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\nt8property\Service\NT8PropertyService;
use Drupal\nt8tabsio\Service\NT8TabsRestService;

/**
 * Class NT8PropertyShortlistController.
 *
 * @package Drupal\nt8propertyshortlist\Controller
 */
class NT8PropertyShortlistController extends ControllerBase {

  /**
   * Drupal\nt8property\Service\NT8PropertyService definition.
   *
   * @var \Drupal\nt8property\Service\NT8PropertyService
   */
  protected $nt8propertyPropertyMethods;
  /**
   * Drupal\nt8tabsio\Service\NT8TabsRestService definition.
   *
   * @var \Drupal\nt8tabsio\Service\NT8TabsRestService
   */
  protected $nt8tabsioTabsService;

  /**
   * Constructs a new NT8PropertyShortlistController object.
   */
  public function __construct(NT8PropertyService $nt8property_property_methods, NT8TabsRestService $nt8tabsio_tabs_service) {
    $this->nt8propertyPropertyMethods = $nt8property_property_methods;
    $this->nt8tabsioTabsService = $nt8tabsio_tabs_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('nt8property.property_methods'),
      $container->get('nt8tabsio.tabs_service')
    );
  }

  /**
   * Shortlist.
   *
   * @return string
   *   Return Hello string.
   */
  public function shortlist() {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: shortlist')
    ];
  }

}
