<?php

namespace Drupal\nt8search\Plugin\Block;

use Drupal\node\Entity\Node;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
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
        NT8SearchService $nt8search_methods
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->nt8searchMethods = $nt8search_methods;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('nt8search.methods')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    $renderOutput = [];

    $loadedResultsAsNodes = $this->nt8searchMethods->issetGet($config, 'properties') ?: [];
    $search_results       = $this->nt8searchMethods->issetGet($config, 'search_results') ?: [];

    if (isset($search_results, $loadedResultsAsNodes)) {
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

      foreach ($loadedResultsAsNodes as $search_result_key => $search_result) {
        $first_of_type = $this->nt8searchMethods->issetGet($search_result, 0);

        if ($first_of_type instanceof Node) {
          $renderOutput['result_container']['results'][] = \Drupal::entityTypeManager()->getViewBuilder('node')->view($first_of_type, 'teaser');
        } else {
          \Drupal::logger('nt8searchcontroller.search')->notice("Unable to load property: $search_result_key.");
        }
      }

      $renderOutput['result_container']['pager_bottom'] = [
        '#type' => 'pager',
      ];
    }

    return $renderOutput;
  }

}
