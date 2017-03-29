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

  /**
   * @param array $param_values
   * @param array $loadNodes
   *
   * @return mixed
   */
  public function performSearchFromParams(array $param_values, array &$loadNodes) {
    // Get the sanitised query information.
    $queryInfo = self::extractQueryInfoFromValues($param_values);

    // Filters
    $filterInfo = $queryInfo['filters'];

    // Build a request data array for our search request.
    $requestData = array_merge([
      'fields' => 'propertyRef',
      'pageSize' => 12 // TODO: Default page size should be stored in the site config.
    ], $filterInfo);

    // Execute the search request.
    $searchResult = $this->executeSearchRequest($requestData);

    // If the user has passed an array into $loadNodes fill it.
    if(isset($searchResult->results)) {
      $loadNodes = self::loadResultIntoNodes($searchResult->results);

      if(!isset($loadNodes)) {
        $loadNodes['error'] = \Drupal::translation()->translate('We failed to load any properties for this request.');
      }
    } else if(isset($searchResult->errorCode)) {
      $loadNodes['error'] = \Drupal::translation()->translate('A fatal TABS error has occurred. Error Code: @errorCode.', ['@errorCode' => $searchResult->errorCode ?: 'Unknown.']);
    } else {
      $loadNodes['error'] = \Drupal::translation()->translate('We couldn\'t hit the TABS API.');
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

    $decoded_api_response = json_decode($api_response);

    if(isset($decoded_api_response->errorCode)) {
      \Drupal::logger('NT8SearchService')->error(
        "Tabs property search returned an error: \r\n @tabsErrorCode: @tabsErrorMessage.",
        [
          '@tabsErrorCode' => $decoded_api_response->errorCode,
          '@tabsErrorMessage' => $decoded_api_response->errorDescription ?: 'No Description Provided'
      ]);
    }

    return $decoded_api_response;
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
  protected function loadResultIntoNodes(array $apiSearchResults) {
    $loadedNodes = NULL;

    if(isset($apiSearchResults)) {
      // Map the API search result (Proprefs) into a simple array of Proprefs.
      $mappedResults = array_map(function( $property ) {
        return $property->propertyRef;
      }, $apiSearchResults);

      $loadedNodes = $this->nt8propertymethods->loadNodesFromProprefs($mappedResults);
    }

    return $loadedNodes;
  }

  // Converts a date into Tabs' date required date format.
  protected static function tabsDateFormat($originalDate) {
    $converted = date("d-m-Y", strtotime($originalDate) );

    return $converted;
  }

  // If a key is set in the provided array return the value or false if it isn't. (helper function).


  /**
   * @param $array
   * @param string $key
   * @return mixed
   */
  public static function iak(array $array, $key = '') {
    return isset($array[$key]) ? $array[$key] : FALSE;
  }
}
