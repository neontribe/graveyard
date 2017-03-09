<?php

namespace Drupal\nt8property\Batch;



class NT8PropertyBatch {
  public static function propertyBatchLoadCallback($page_counter, $per_page, $search_instance_id, $modify_replace, &$context) {
    $nt8restService = \Drupal::service('nt8tabsio.tabs_service');
    $nt8PropertyMethods = \Drupal::service('nt8property.property_methods');

    $data = $nt8restService->get("property",
      array(
        "page" => $page_counter,
        "pageSize" => $per_page,
        "searchId" => $search_instance_id,
      )
    );

    $data = json_decode($data);
    $results = $data->results;

    foreach ($results as $result) {
      switch($modify_replace) {
        case 0:
          $nt8PropertyMethods->updateNodeInstancesFromData($result);
          break;
        case 1:
          $nt8PropertyMethods->createNodeInstanceFromData($result, TRUE);
          break;
        default:
          $nt8PropertyMethods->createNodeInstanceFromData($result, TRUE);
      }

    }
  }

  public static function propertyBatchLoadFinishedCallback($success, $results, $operations) {
    dpm($success);
  }
}
