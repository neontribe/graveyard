<?php

namespace Drupal\nt8map\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
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
   * Construct.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        NT8TabsRestService $nt8tabsio_tabs_service,
        NT8MapService $nt8map_service
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->nt8tabsioTabsService = $nt8tabsio_tabs_service;
    $this->nt8mapService = $nt8map_service;
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
      $container->get('nt8map.methods')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $config = $this->getConfiguration();
    $properties = isset($config['properties']) ? $config['properties'] : [];

    $mapData = $this->nt8mapService->initMap($properties);

    $build['#attached'] = array(
      'library' => array(
        'nt8map/nt8map_lib',
      ),
    );

    $build['map'] = [
      '#theme' => 'nt8map',
      '#mapdata' => $mapData,
      '#height' => '300px',
    ];

    return $build;
  }

}
