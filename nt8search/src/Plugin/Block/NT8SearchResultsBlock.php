<?php

namespace Drupal\nt8search\Plugin\Block;

use Drupal\node\Entity\Node;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\nt8property\Service\NT8PropertyService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\nt8search\Service\NT8SearchService;

/**
 * Provides a 'NT8SearchResultsBlock' block.
 *
 * @Block(
 *  id = "nt8search_results_block",
 *  admin_label = @Translation("Neontabs 8 Search Results Block"),
 * )
 */
class NT8SearchResultsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\nt8search\Service\NT8SearchService definition.
   *
   * @var \Drupal\nt8search\Service\NT8SearchService
   */
  protected $nt8searchMethods;

  /**
   * Drupal\nt8property\Service\NT8PropertyMethods definition.
   *
   * @var \Drupal\nt8property\Service\NT8PropertyService
   */
  protected $nt8propertyMethods;

  /**
   * Construct.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\nt8search\Service\NT8SearchService $nt8search_methods
   *   Instance of NT8SearchService.
   * @param \Drupal\nt8property\Service\NT8PropertyService $nt8property_methods
   *   Instance of NT8PropertyService.
   */
  public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        NT8SearchService $nt8search_methods,
        NT8PropertyService $nt8property_methods
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->nt8searchMethods = $nt8search_methods;
    $this->nt8propertyMethods = $nt8property_methods;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('nt8search.methods'),
      $container->get('nt8property.property_methods')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $renderOutput = [
      '#cache' => [
        'contexts' => [
          'url.path',
          'url.query_args',
        ],
      ],
    ];

    // @TODO: Make this a config option.
    $displayPagination = TRUE;

    $search_results = NT8SearchService::getSearchState();

    // Map the API search result into a simple array of Proprefs.
    $mappedResults = array_map(function ($property) {
      return $property->propertyRef;
    }, $search_results->results);

    $loadedResultsAsNodes = $this->nt8propertyMethods->loadNodesFromProprefs($mappedResults);

    if (isset($search_results, $loadedResultsAsNodes)) {
      $totalResults = $search_results->totalResults;
      $pageSize = $search_results->pageSize;

      $page = pager_default_initialize($totalResults, $pageSize);

      if ($displayPagination) {
        $renderOutput['result_container'] = [
          'pager' => [
            '#type' => 'pager',
            '#weight' => 10,
          ],
        ];
      }

      foreach ($loadedResultsAsNodes as $search_result_key => $search_result) {
        $first_of_type = $this->nt8searchMethods->issetGet($search_result, 0);

        if ($first_of_type instanceof Node) {
          $renderOutput['result_container']['results'][] = \Drupal::entityTypeManager()->getViewBuilder('node')->view($first_of_type, 'teaser');
        }
        else {
          \Drupal::logger('nt8searchcontroller.search')->notice("Unable to load property: $search_result_key.");
        }
      }
    }

    return $renderOutput;
  }

}
