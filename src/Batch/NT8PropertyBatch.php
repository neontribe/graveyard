<?php

namespace Drupal\nt8property\Batch;

/**
 * Neontabs Property Batch Load Class.
 */
class NT8PropertyBatch {

  /**
   * Callback function for drupal property batch load progress.
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
   */
  public static function propertyBatchLoadFinishedCallback($success, $results, $operations) {
    $updated_node_ids = implode(', ', $results['nodes_updated']);

    $processed = $results['count_processed'];

    drupal_set_message("Processed $processed nodes. And updated these: [$updated_node_ids] nodes.");
  }

}
