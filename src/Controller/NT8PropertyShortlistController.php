<?php

namespace Drupal\nt8propertyshortlist\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\nt8propertyshortlist\Service\NT8PropertyShortlistService;
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
   * Drupal\nt8propertyshortlist\Service\NT8PropertyShortlistService definition.
   *
   * @var \Drupal\nt8propertyshortlist\Service\NT8PropertyShortlistService
   */
  protected $nt8propertyshortlist;

  /**
   * Constructs a new NT8PropertyShortlistController object.
   */
  public function __construct(
    NT8PropertyService $nt8property_property_methods,
    NT8TabsRestService $nt8tabsio_tabs_service,
    NT8PropertyShortlistService $nt8propertyshortlist_service
  ) {
    $this->nt8propertyPropertyMethods = $nt8property_property_methods;
    $this->nt8tabsioTabsService = $nt8tabsio_tabs_service;
    $this->nt8propertyshortlist = $nt8propertyshortlist_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('nt8property.property_methods'),
      $container->get('nt8tabsio.tabs_service'),
      $container->get('nt8propertyshortlist.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function shortlist() {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: shortlist')
    ];
  }

  public function shortlist_toggle($propRef) {
    $this->nt8propertyshortlist->toggleEntry($propRef);
  }

}
