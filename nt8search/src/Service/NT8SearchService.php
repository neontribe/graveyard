<?php

namespace Drupal\nt8search\Service;

use Drupal\nt8property\Service\NT8PropertyService;
use Drupal\nt8tabsio\Service\NT8TabsRestService;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class NT8SearchService.
 *
 * @package Drupal\nt8search
 * @author oliver@neontribe.co.uk
 */
class NT8SearchService {

  /**
   * Drupal\nt8tabsio\Service\NT8TabsRestService definition.
   *
   * @var \Drupal\nt8tabsio\Service\NT8TabsRestService
   */
  protected $nt8tabsioTabsService;
  protected $entityTypeManager;
  protected $entityQuery;
  protected $nt8propertymethods;

  /**
   * Constructor.
   */
  public function __construct(NT8TabsRestService $nt8tabsio_tabs_service,
                              QueryFactory $entityQuery,
                              EntityTypeManager $entityTypeManager,
                              NT8PropertyService $nt8propertymethods) {

    $this->nt8tabsioTabsService = $nt8tabsio_tabs_service;
    $this->entityQuery = $entityQuery;
    $this->entityTypeManager = $entityTypeManager;
    $this->nt8propertymethods = $nt8propertymethods;
  }

  public function performSearchFromParams(array $param_values, $loadNodes = FALSE) {
    $queryInfo = self::extractQueryInfoFromValues($param_values);

    // Filters
    $filterInfo = $queryInfo['filters'];

    $requestData = [
      'fields' => 'propertyRef'
    ] + $filterInfo;

    $searchResult = $this->executeSearchRequest($requestData);

    if($loadNodes) {
      $searchResult->loaded_node_results = self::loadResultIntoNodes($searchResult);
    }

    return $searchResult;
  }

  /**
   * @param $searchRequest
   * @return mixed
   */
  public function executeSearchRequest($searchRequest) {
    $api_response = $this->nt8tabsioTabsService->get(
      'property', $searchRequest
    );

    $api_response = json_decode($api_response);

    return $api_response;
  }

  /**
   * @param array $form_values
   * @return array
   */
  protected static function extractQueryInfoFromValues(array $form_values) {
    $queryInfo = [
      'filters' => [],
    ];

    // Ensure that things are as TABS expects.
    $typeModifications = [
      'fromDate' => '\NT8SearchService::tabsDateFormat',
      'toDate' => '\NT8SearchService::tabsDateFormat',
    ];

    foreach ($form_values as $value_key => $value_data) {
      $name_type = explode('-', $value_key);

      $value_name = self::iak($name_type, 0);
      $value_type = self::iak($name_type, 1) ?: 'filters';

      if(isset($queryInfo[$value_type])) {
        if($value_data !== '') {

          $typeModification = self::iak($typeModifications, $value_name);
          if($typeModification) {
            $value_data_mod = call_user_func(__NAMESPACE__ . $typeModification, $value_data);

            if(isset($value_data_mod) && $value_data_mod) {
              $value_data = $value_data_mod;
            }
          }

          $queryInfo[$value_type][$value_name] = $value_data;
        }
      }
    }

    return $queryInfo;
  }

  /**
   * @param \stdClass $apiSearchResult
   * @return array
   */
  protected function loadResultIntoNodes(\stdClass $apiSearchResult) {
    $result = $apiSearchResult->results;
    $loadedNodes = [];

    if(isset($result) && is_array($result)) {
      $result = array_map(function( $property ) {
        return $property->propertyRef;
      }, $result);

      $loadedNodes = $this->nt8propertymethods->loadNodesFromProprefs($result);

    }

    return $loadedNodes;
  }

  // Converts a date into Tabs' date required date format.
  protected static function tabsDateFormat($originalDate) {
    $converted = date("d-m-Y", strtotime($originalDate) );

    return $converted;
  }

  // If a key is set in the provided array return the value or false if it isn't. (helper function).
  public static function iak($array, $key = '') {
    return isset($array[$key]) ? $array[$key] : FALSE;
  }
}
