<?php

namespace Drupal\nt8propertyshortlist\Service;
use Drupal\nt8property\Service\NT8PropertyService;
use Drupal\nt8tabsio\Service\NT8TabsRestService;

/**
 * Class NT8PropertyShortlistService.
 *
 * @package Drupal\nt8propertyshortlist\Service
 */
class NT8PropertyShortlistService {

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
   * Constructs a new NT8PropertyShortlistService object.
   */
  public function __construct(NT8PropertyService $nt8property_property_methods, NT8TabsRestService $nt8tabsio_tabs_service) {
    $this->nt8propertyPropertyMethods = $nt8property_property_methods;
    $this->nt8tabsioTabsService = $nt8tabsio_tabs_service;
  }

  /**
   * Toggles an entry in the session shortlist.
   *
   * @param string $propRef
   */
  public function toggleEntry(string $propRef) {

  }

}
