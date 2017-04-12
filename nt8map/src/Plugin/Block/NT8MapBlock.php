<?php

namespace Drupal\nt8map\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\nt8property\Service\NT8PropertyService;
use Drupal\nt8search\Service\NT8SearchService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\nt8tabsio\Service\NT8TabsRestService;
use Drupal\nt8map\Service\NT8MapService;

/**
 * Provides a 'NT8MapBlock' block.
 *
 * @Block(
 *  id = "nt8map_block",
 *  admin_label = @Translation("NT8 Map"),
 * )
 */
class NT8MapBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
  protected $nt8mapService;

  /**
   *
   */
  protected $nt8searchMethods;

  /**
   *
   */
  protected $nt8propertyMethods;

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
   * @param \Drupal\nt8map\Service\NT8MapService $nt8map_service
   *   Instance of NT8MapService.
   */
  public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        NT8TabsRestService $nt8tabsio_tabs_service,
        NT8MapService $nt8map_service,
        NT8SearchService $nt8search_methods,
        NT8PropertyService $nt8property_methods
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->nt8tabsioTabsService = $nt8tabsio_tabs_service;
    $this->nt8mapService = $nt8map_service;
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
      $container->get('nt8tabsio.tabs_service'),
      $container->get('nt8map.methods'),
      $container->get('nt8search.methods'),
      $container->get('nt8property.property_methods')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [
      '#cache' => [
        'contexts' => [
          'url.path',
          'url.query_args',
        ],
      ],
    ];

    $search_results = NT8SearchService::getSearchState();

    // Map the API search result into a simple array of Proprefs.
    $mappedResults = array_map(function ($property) {
      return $property->propertyRef;
    }, $search_results->results);

    $loadedResultsAsNodes = $this->nt8propertyMethods->loadNodesFromProprefs($mappedResults);


    $mapData = $this->nt8mapService->initMap($loadedResultsAsNodes);

    $build['#attached'] = [
      'library' => [
        'nt8map/nt8map_lib',
      ],
    ];

    $build['map'] = [
      '#theme' => 'nt8map',
      '#mapdata' => $mapData,
      '#height' => '300px',
    ];

    return $build;
  }

}
