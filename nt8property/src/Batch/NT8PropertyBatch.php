<?php

namespace Drupal\nt8property\Batch;

class NT8PropertyBatch {
  public static function propertyBatchLoadCallback($page_counter, $per_page, $search_instance_id, $modify_replace, &$context) {
    $nt8restService = \Drupal::service('nt8tabsio.tabs_service');
    $nt8PropertyMethods = \Drupal::service('nt8property.property_methods');

    $pages = $per_page['pages'];
    $pageSize = $per_page['per_page'];

    // If we're on the last page then change the pageSize limit, default back to per_page.
//    if(($pages-1) == $page_counter) {
//      $pageSize = $per_page['last_page'] ?: $per_page['per_page'];
//    }

    $data = $nt8restService->get("property",
      array(
        "page" => $page_counter + 1,
        "pageSize" => $pageSize,
        "searchId" => $search_instance_id,
      )
    );

    $data = json_decode($data);
    $results = $data->results;

    if(!isset($context['results']['count_processed'])) {
      $context['results']['count_processed'] = 0;
    }

    if(!isset($context['results']['nodes_updated'])) {
      $context['results']['nodes_updated'] = [];
    }

    foreach ($results as $result) {
      $context['results']['count_processed']++;

      switch($modify_replace) {
        case 0:
          $nodes_updated = $nt8PropertyMethods->updateNodeInstancesFromData($result);
          $context['results']['nodes_updated'] = array_merge($context['results']['nodes_updated'], $nodes_updated);
          break;
        default:
          $nt8PropertyMethods->createNodeInstanceFromData($result, TRUE);
      }

    }
  }

  public static function propertyBatchLoadFinishedCallback($success, $results, $operations) {
    $updated_node_ids = implode(', ', $results['nodes_updated']);

    $processed = $results['count_processed'];

    drupal_set_message("Processed $processed nodes. And updated these: [$updated_node_ids] nodes.");
  }
}
