<?php

namespace Drupal\nt8propertyshortlist\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\HtmlResponse;
use Drupal\nt8propertyshortlist\Service\NT8PropertyShortlistService;
use Drupal\nt8search\Service\NT8SearchService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\nt8property\Service\NT8PropertyService;
use Drupal\nt8tabsio\Service\NT8TabsRestService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

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
   * Drupal\nt8search\Service\NT8SearchService definition.
   *
   * @var \Drupal\nt8search\Service\NT8SearchService
   */
  protected $nt8searchMethods;


  /**
   * Constructs a new NT8PropertyShortlistController object.
   */
  public function __construct(
    NT8PropertyService $nt8property_property_methods,
    NT8TabsRestService $nt8tabsio_tabs_service,
    NT8PropertyShortlistService $nt8propertyshortlist_service,
    NT8SearchService $nt8search_methods
  ) {
    $this->nt8propertyPropertyMethods = $nt8property_property_methods;
    $this->nt8tabsioTabsService = $nt8tabsio_tabs_service;
    $this->nt8propertyshortlist = $nt8propertyshortlist_service;
    $this->nt8searchMethods = $nt8search_methods;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('nt8property.property_methods'),
      $container->get('nt8tabsio.tabs_service'),
      $container->get('nt8propertyshortlist.service'),
      $container->get('nt8search.methods')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function shortlist() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function shortlist_list() {
    $currentShortlist = $this->nt8propertyshortlist->getStore();

    return new JsonResponse($currentShortlist);
  }

  /**
   * @param string $propRef
   */
  public function shortlist_toggle(string $propRef) {
    $splittedPropref = $this->nt8tabsioTabsService->splitPropref($propRef)[0] ?: $propRef;
    $this->nt8propertyshortlist->toggleEntry($splittedPropref);
    $previousUrl= \Drupal::request()->server->get('HTTP_REFERER');

    return new RedirectResponse($previousUrl);
  }

}
