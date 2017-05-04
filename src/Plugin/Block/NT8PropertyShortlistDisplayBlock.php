<?php

namespace Drupal\nt8propertyshortlist\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\nt8property\Service\NT8PropertyService;
use Drupal\nt8propertyshortlist\Service\NT8PropertyShortlistService;

/**
 * Provides a 'NT8PropertyShortlistDisplayBlock' block.
 *
 * @Block(
 *  id = "nt8property_shortlist_display_block",
 *  admin_label = @Translation("Nt8property shortlist display block"),
 * )
 */
class NT8PropertyShortlistDisplayBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\nt8property\Service\NT8PropertyService definition.
   *
   * @var \Drupal\nt8property\Service\NT8PropertyService
   */
  protected $nt8propertyPropertyMethods;
  /**
   * Drupal\nt8propertyshortlist\Service\NT8PropertyShortlistService definition.
   *
   * @var \Drupal\nt8propertyshortlist\Service\NT8PropertyShortlistService
   */
  protected $nt8propertyshortlistService;
  /**
   * Constructs a new NT8PropertyShortlistDisplayBlock object.
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
        NT8PropertyService $nt8property_property_methods, 
	NT8PropertyShortlistService $nt8propertyshortlist_service
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->nt8propertyPropertyMethods = $nt8property_property_methods;
    $this->nt8propertyshortlistService = $nt8propertyshortlist_service;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('nt8property.property_methods'),
      $container->get('nt8propertyshortlist.service')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function build() {
    $renderOutput = [
      '#cache' => [
        'max-age' => 0,
        'contexts' => [
          'url.path',
          'url.query_args',
        ],
      ],
      'result_container' => [],
    ];

    $currentShortlistItems = $this->nt8propertyshortlistService->getStore();
    $loadedResultsAsNodes = $this->nt8propertyPropertyMethods
      ->loadNodesFromProprefs($currentShortlistItems);

    foreach ($loadedResultsAsNodes as $search_result_key => $search_result) {
      $first_of_type = $this->nt8propertyPropertyMethods->issetGet($search_result, 0);

      if ($first_of_type instanceof Node) {
        $renderOutput['result_container']['results'][] = \Drupal::entityTypeManager()->getViewBuilder('node')->view($first_of_type, 'teaser');
      }
      else {
        \Drupal::logger('nt8propertyshortlist.displayBlock')->notice("Unable to load property: $search_result_key.");
      }
    }

    return $renderOutput;
  }

}
