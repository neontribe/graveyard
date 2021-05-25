<?php

namespace Drupal\nt8property\Batch;

/**
 * Neontabs Property Batch Load Class.
 */
class NT8PropertyBatch {

  /**
   * Instantiates and handles a Drupal batch property load.
   *
   * @param int $batch_size
   *   The number of properties to request & process per search.
   *   The batch size goes up in a 2 ^ n scale.
   * @param int $modify_replace
   *   Modify the existing nodes or create new ones (deleting the current).
   *   0 => Modify, 1 => Replace.
   */
  public static function propertyBatchLoad(int $batch_size = 8, int $modify_replace = 0) {
    $batchSizeList = [
      1 => 1,
      2 => 2,
      3 => 4,
      4 => 8,
      5 => 16,
      6 => 32,
      7 => 64,
      8 => 128,
      9 => 256,
    ];

    // Get list of properties to reload.
    $per_page = $batchSizeList[$batch_size] ?? 128;

    // Get page count.
    $first_page = \Drupal::service('nt8tabsio.tabs_service')->get(
      "property",
      [
        "page" => 1,
        "pageSize" => $per_page
      ]
    );

    $first_page = json_decode($first_page);

    $search_instance_id = $first_page->searchId;
    $total_results = $first_page->totalResults;

    $batch = [
      'title' => t('Loading all properties from API.'),
      'operations' => [],
      'progress_message' => t('Processed @current out of @total.'),
      'finished' => '\Drupal\nt8property\Batch\NT8PropertyBatch::propertyBatchLoadFinishedCallback',
    ];

    $pages = ceil($total_results / $per_page);
    $last_page = $total_results - ($per_page * ($pages - 1));

    for ($page_counter = 0; $page_counter < $pages; $page_counter++) {
      $batch["operations"][] = [
        '\Drupal\nt8property\Batch\NT8PropertyBatch::propertyBatchLoadCallback',
        [
          $page_counter,
          [
            'per_page' => $per_page,
            'last_page' => $last_page,
            'pages' => $pages,
          ],
          $search_instance_id,
          $modify_replace,
        ],
      ];
    }

    batch_set($batch);
  }


  /**
   * Callback function for drupal property batch load progress.
   *
   * @param $page_counter
   * @param $per_page
   * @param $search_instance_id
   * @param $modify_replace
   * @param $context
   */
  public static function propertyBatchLoadCallback($page_counter, $per_page, $search_instance_id, $modify_replace, &$context) {
    $nt8restService = \Drupal::service('nt8tabsio.tabs_service');
    $nt8PropertyMethods = \Drupal::service('nt8property.property_methods');

    $pages = $per_page['pages'];
    $pageSize = $per_page['per_page'];

    $data = json_decode(
      $nt8restService->get("property",
        [
          "page" => $page_counter + 1,
          "pageSize" => $pageSize,
          "searchId" => $search_instance_id,
        ]
      )
    );

    $results = $data->results;

    if (!isset($context['results']['count_processed'])) {
      $context['results']['count_processed'] = 0;
    }

    if (!isset($context['results']['nodes_updated'])) {
      $context['results']['nodes_updated'] = [];
    }

    foreach ($results as $result) {
      $context['results']['count_processed']++;

      switch ($modify_replace) {
        case 0:
          $nodes_updated = $nt8PropertyMethods->updateNodeInstancesFromData($result);
          $context['results']['nodes_updated'] = array_merge($context['results']['nodes_updated'], $nodes_updated);
          break;

        default:
          $nt8PropertyMethods->createNodeInstanceFromData($result, TRUE);
      }

    }
  }

  /**
   * Callback which is fired once the Drupal batch job has finished.
   *
   * @param $success
   * @param $results
   * @param $operations
   */
  public static function propertyBatchLoadFinishedCallback($success, $results, $operations) {
    $updated_node_ids = implode(', ', $results['nodes_updated']);

    $processed = $results['count_processed'];

    drupal_set_message("Processed $processed nodes. And updated these: [$updated_node_ids] nodes.");
  }

}
