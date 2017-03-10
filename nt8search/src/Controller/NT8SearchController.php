<?php

namespace Drupal\nt8search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\nt8tabsio\Service\NT8TabsRestService;
use Drupal\nt8search\Service\NT8SearchService;

/**
 * Description of NT8TabsIOController
 *
 * @author tobias@neontribe.co.uk
 */
class NT8SearchController extends ControllerBase {

  /**
   * @var \Drupal\nt8tabsio\Service\NT8TabsRestService
   */
  protected $nt8TabsRestService;

  /**
   * Drupal\nt8search\Service\NT8SearchService definition.
   *
   * @var \Drupal\nt8search\Service\NT8SearchService
   */
  protected $nt8searchMethodsService;

  /**
   * {@inheritdoc}
   */
  public function __construct(NT8TabsRestService $nt8TabsRestService,
                              NT8SearchService $nt8search_methods_service) {
    $this->nt8TabsRestService = $nt8TabsRestService;
    $this->nt8searchMethodsService = $nt8search_methods_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('nt8tabsio.tabs_service'),
      $container->get('nt8search.methods')
    );
  }

  public function search(Request $request) {
    $posted_values = $request->query->all();

    $search_results = $this->nt8searchMethodsService->performSearchFromParams($posted_values);

//    $build_arr = ['#markup' => 'jho'];
//
//    $search_results = $this->nt8searchMethodsService->performSearchUsingFormState($form_state);
//    $property_results = $search_results->results;
//
//    return $build_arr;
    return [
      '#markup' => print_r($search_results, TRUE )
    ];
  }

}
