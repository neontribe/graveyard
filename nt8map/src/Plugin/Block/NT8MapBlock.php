<?php

namespace Drupal\nt8map\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\nt8tabsio\Service\NT8TabsRestService;

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
        NT8TabsRestService $nt8tabsio_tabs_service
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->nt8tabsioTabsService = $nt8tabsio_tabs_service;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('nt8tabsio.tabs_service')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $build['map'] = [
      '#theme' => 'nt8map',
    ];

    return $build;
  }

}
