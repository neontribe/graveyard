<?php

namespace Drupal\nt8search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
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

  public function getTitle() {
    return 'Cottage Search';
  }

  public function search(Request $request) {
    $posted_values = $request->query->all();

    $renderOutput = [];

    $loadedResultsAsNodes = [];
    $search_results = $this->nt8searchMethodsService->performSearchFromParams($posted_values, $loadedResultsAsNodes);

    $search_error = $this->nt8searchMethodsService->iak($loadedResultsAsNodes, 'error') ?: NULL;

    if(isset($search_error)) {
      //TODO: Make this output optional/configurable.
      $renderOutput['error'] = [
        '#prefix' => '<h2>',
        '#suffix' => '</h2>',
        '#markup' => $this->t('Error performing search. Error code: @errorCode', ['@errorCode' => $search_error]),
      ];

      return $renderOutput;
    }

    if(isset($search_results) && isset($loadedResultsAsNodes)) {
      $totalResults = $search_results->totalResults;
      $pageSize = $search_results->pageSize;


      $page = pager_default_initialize($totalResults, $pageSize);

      $renderOutput['result_container'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => 'container',
        ],
        'results' => [
          'title' => [
            '#prefix' => '<h3>',
            '#suffix' => '</h3>',
            '#markup' => $this->t('Search Results (@count)', ['@count' => $totalResults]),
          ],
          'pager_top' => [
            '#type' => 'pager',
          ],
        ],
      ];

      foreach($loadedResultsAsNodes as $search_result_key => $search_result) {
        $first_of_type = $this->nt8searchMethodsService->iak($search_result, 0);

        $error_msg = NULL;
        if(is_array($first_of_type)) {
          $error_msg = $this->nt8searchMethodsService->iak($first_of_type, 'error');
        }

        // TODO: Create config option which allows us to toggle error messages like this.
        if($error_msg) {
          $renderOutput['result_container']['results'][] = [
            '#prefix' => '<h4>',
            '#suffix' => '</h4>',
            '#markup' => $this->t('Error loading property: @error [@key].', ['@error' => $error_msg, '@key' => $search_result_key]),
            'loadit' => [
              '#markup' => 'Would you like to try and load it? ',
              '#prefix' => '<p>',
              '#suffix' => '</p>',
              'submit' => [
                '#title' => $this->t('Load Property'),
                '#type' => 'link',
                '#url' => \Drupal\Core\Url::fromRoute('property.generate_single', ['propRef' => $search_result_key . '_' . \Drupal::config('nt8tabsio.settings')->get('id')])
              ]
            ]
          ];
        } else if($first_of_type instanceof Node) {
          $renderOutput['result_container']['results'][] = \Drupal::entityTypeManager()->getViewBuilder('node')->view($first_of_type, 'teaser');
        }
      }

      $renderOutput['result_container']['pager_bottom'] = [
        '#type' => 'pager',
      ];
    }

    return $renderOutput;
  }

}
