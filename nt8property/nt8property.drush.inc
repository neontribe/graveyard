<?php

/**
 * @file
 * Drush property commands.
 */

/**
 * Implements hook_drush_command().
 */
function nt8property_drush_command() {
  $items = [];
  $items['nt8-import-all'] = [
    'description' => 'Bulk import all properties.',
    'options' => [
      'pageSize' => 'The number of properties to import (default 99999).',
      'page' => 'How many pages to skip before starting to import (default 0).',
      'search_id' => 'If you are using offset you will need to specify a search id to maintain ordering.',
    ],
    'drupal dependencies' => ['nt8property'],
    'aliases' => ['nt8:ia'],
  ];
  return $items;
}

/**
 * Call back function drush_custom_drush_command_say_hello()
 *
 * The call back function name in the  following format
 *   drush_{module_name}_{item_id_for_command}()
 */
function drush_nt8property_nt8_import_all($name = '') {
  $pageSize = drush_get_option('count', '99999');
  $page = drush_get_option('offset', '0');

  $searchId = drush_get_option('offset', FALSE);

  $searchParams = [
    'page' => $page,
    'pageSize' => $pageSize,
  ];

  if ($searchId) {
    $searchParams['searchId'] = $search_instance_id;
  }

  $container = Drupal::getContainer();
  $nt8restService = $container->get('nt8tabsio.tabs_service');
  $nt8PropertyMethods = $container->get('nt8property.property_methods');

  if (file_exists('/tmp/props.json')) {
    $response = file_get_contents('/tmp/props.json');
  }
  else {
    $response = $nt8restService->get("property", $searchParams);
    $fh = fopen('/tmp/props.json', 'w');
    fwrite($fh, $response);
    fclose($fh);
  }
  $data = json_decode($response);

  $nodes_updated = [];
  $nodes_added = [];

  $results = $data->results;
  foreach ($results as $result) {
    $propref = $result->propertyRef;
    $nids = $nt8PropertyMethods->loadNodesFromPropref($propref, FALSE);
    if ($nids) {
      $nt8PropertyMethods->updateNodeInstancesFromData($result);
      $nodes_updated[] = $propref;
      echo "^";
    }
    else {
      $nt8PropertyMethods->createNodeInstanceFromData($result, FALSE);
      $nodes_added[] = $propref;
      echo "+";
    }
  }
  echo "\n";

  \Drupal::logger('nt8property.drush')->notice(
    sprintf('Drush updated %d nodes: [%s]', count($nodes_updated), implode(', ', $nodes_updated))
  );
  \Drupal::logger('nt8property.drush')->notice(
    sprintf('Drush created %d nodes: [%s]', count($nodes_added), implode(', ', $nodes_added))
  );

  drush_print(sprintf('Created %s nodes, Updated %s nodes', count($nodes_added), count($nodes_updated)));
}
