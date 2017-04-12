<?php

namespace Drupal\nt8search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\nt8tabsio\Service\NT8TabsRestService;
use Drupal\nt8search\Service\NT8SearchService;

/**
 * Description of NT8SearchController.
 *
 * @author oliver@neontribe.co.uk
 */
class NT8SearchController extends ControllerBase {

  /**
   * Drupal\nt8tabsio\Service\NT8TabsRestService definition.
   *
   * @var \Drupal\nt8tabsio\Service\NT8TabsRestService
   */
  protected $nt8TabsRestService;

  /**
   * Drupal\nt8search\Service\NT8SearchService definition.
   *
   * @var \Drupal\nt8search\Service\NT8SearchService
   */
  protected $nt8searchMethods;

  /**
   * {@inheritdoc}
   */
  public function __construct(NT8TabsRestService $nt8TabsRestService,
                              NT8SearchService $nt8search_methods_service) {
    $this->nt8TabsRestService = $nt8TabsRestService;
    $this->nt8searchMethods = $nt8search_methods_service;
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

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return 'Cottage Search';
  }

  /**
   * Search page controller.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request as passed by Symfony.
   *
   * @return array
   *   Render array describing the search page.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function search(Request $request) {
    $posted_values = $request->query->all();

    $renderOutput = [];

    // Execute the search
    {
      $loadedResultsAsNodes = [];
      $search_results = $this->nt8searchMethods->performSearchFromParams($posted_values, $loadedResultsAsNodes);

      $search_error = $this->nt8searchMethods->issetGet($loadedResultsAsNodes, 'error') ?: NULL;

      if (isset($search_error)) {
        // TODO: Make this output optional/configurable.
        $renderOutput['error'] = [
          '#prefix' => '<h2>',
          '#suffix' => '</h2>',
          '#markup' => $this->t('Error performing search. Error code: @errorCode', ['@errorCode' => $search_error]),
        ];

        return $renderOutput;
      }
    }

    // Setup the blocks.
    {
      $mapBlock = \Drupal::service('plugin.manager.block')->createInstance(
        'nt8map_block',
        [
          'properties' => $loadedResultsAsNodes
        ]
      );

      $searchResultsBlock = \Drupal::service('plugin.manager.block')->createInstance(
        'nt8search_results_block',
        [
          'properties' => $loadedResultsAsNodes,
          'search_results' => $search_results
        ]
      );
    }

    $renderOutput['results_map'] = $mapBlock->build();
    $renderOutput['results_search'] = $searchResultsBlock->build();

    return $renderOutput;
  }

}
