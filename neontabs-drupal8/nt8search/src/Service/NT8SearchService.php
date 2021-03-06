<?php

namespace Drupal\nt8search\Service;

use Drupal\Core\Session\AccountInterface;
use Drupal\nt8property\Service\NT8PropertyService;
use Drupal\nt8search\NT8SearchCompleteEvent;
use Drupal\nt8tabsio\Service\NT8TabsRestService;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

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
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  private $sessionManager;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  private $currentUser;

  /**
   * @var \Drupal\user\PrivateTempStore
   */
  protected $store;

  /**
   * Constructor.
   */
  public function __construct(
    NT8TabsRestService $nt8tabsio_tabs_service,
    $entityQuery,
    $entityTypeManager,
    NT8PropertyService $nt8propertymethods,
    PrivateTempStoreFactory $temp_store_factory,
    SessionInterface $session_manager,
    AccountInterface $current_user) {

    $this->nt8tabsioTabsService = $nt8tabsio_tabs_service;
    $this->entityQuery = $entityQuery;
    $this->entityTypeManager = $entityTypeManager;
    $this->nt8propertymethods = $nt8propertymethods;
    $this->tempStoreFactory = $temp_store_factory;
    $this->sessionManager = $session_manager;
    $this->currentUser = $current_user;

    if ($this->currentUser->isAnonymous() && !isset($_SESSION['session_started'])) {
      $_SESSION['session_started'] = true;
      $this->sessionManager->start();
    }

    $this->store = $this->tempStoreFactory->get('nt8search.search_results');
  }


  /**
   * Performs a search on the TABS API with the specified paramaters.
   *
   * @param array $param_values
   *   Search definition provided as an array of parameter values.
   * @param bool $setState
   *   Should we update the `nt8search.results` state with loaded proprefs?
   * @param int $pageSize
   *   Specifies how many property IDs TABS returns per page.
   * @param array $loadNodes
   *   Array into which the result of the search should be loaded as nodes.
   *   If this isn't set no nodes are loaded.
   *
   * @return \stdClass
   *   The json decoded result of the search.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function performSearchFromParams(array $param_values, bool $setState = FALSE, int $pageSize = 12, array &$loadNodes = NULL) {
    // Get the sanitised query information.
    $queryInfo = self::extractQueryInfoFromValues($param_values);

    // Filters.
    $filterInfo = $queryInfo['filters'];

    // Guess at the page number.
    $page_number = \Drupal::request()->query->get('page') ?: NULL;

    // Build a request data array for our search request.
    $requestData = array_merge([
      'fields' => 'propertyRef',
      'pageSize' => $pageSize,
      'page' => $page_number,
    ], $filterInfo);

    // Execute the search request.
    $searchResult = $this->executeSearchRequest($requestData);

    // @TODO Never show errors to the user! Change how logging is done here.
    // If the user has passed an array into $loadNodes fill it.
    if (isset($searchResult->results)) {
      if (isset($loadNodes)) {
        $loadNodes = self::loadSearchResultIntoNodes($searchResult->results);
      }

      if ($setState) {
        // Set the search state.
        $this->store->set('nt8search.results', $searchResult);

        $searchCompleteEvent = new NT8SearchCompleteEvent($searchResult->results);

        $dispatcher = \Drupal::service('event_dispatcher');
        $dispatcher->dispatch(NT8SearchCompleteEvent::NAME, $searchCompleteEvent);
      }

      if (!isset($loadNodes)) {
        // @TODO: Replace with a drupal logger.
        // We failed to load any properties for this request.
      }
    }
    elseif (isset($searchResult->errorCode)) {
      // @TODO: Replace with a drupal logger.
      // ['@errorCode' => $searchResult->errorCode ?: 'Unknown.']
      // A fatal TABS error has occurred. Error Code: @errorCode.
    }
    else {
      // @TODO: Replace with a drupal logger.
      // 'We couldn\'t hit the TABS API.
    }

    return $searchResult;
  }

  /**
   * Returns the current search state as stored by the Session mngr.
   *
   * @return mixed
   *   The stored value, or NULL if no value exists.
   */
  public function getSearchState() {
    // Get the current search state.
    return $this->store->get('nt8search.results');
  }

  /**
   * Executes a search request based upon the provided params.
   *
   * @param array $searchRequest
   *   Array of filters used to define the search.
   *
   * @return \stdClass
   *   json_decoded TABS search result.
   */
  public function executeSearchRequest(array $searchRequest) {
    $api_response = $this->nt8tabsioTabsService->get(
      'property', $searchRequest
    );

    $decoded_api_response = json_decode($api_response);

    if (isset($decoded_api_response->errorCode)) {
      \Drupal::logger('NT8SearchService')->error(
        "Tabs property search returned an error: \r\n @tabsErrorCode: @tabsErrorMessage.",
        [
          '@tabsErrorCode' => $decoded_api_response->errorCode,
          '@tabsErrorMessage' => $decoded_api_response->errorDescription ?: 'No Description Provided',
        ]
      );
    }

    return $decoded_api_response;
  }

  /**
   * Sanitises user inputted search field values before passing to TABS.
   *
   * @param array $form_values
   *   Values passed in upon form post.
   *
   * @return array
   *   Returns an array of filtered parameters.
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

      $value_name = self::issetGet($name_type, 0);
      $value_type = self::issetGet($name_type, 1) ?: 'filters';

      if (isset($queryInfo[$value_type])) {
        if ($value_data !== '') {

          $typeModification = self::issetGet($typeModifications, $value_name);
          if ($typeModification) {
            $value_data_mod = call_user_func(__NAMESPACE__ . $typeModification, $value_data);

            if (isset($value_data_mod) && $value_data_mod) {
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
   * Loads a set of NIDs returned by a property search from the database.
   *
   * @param array $apiSearchResults
   *   Array of NIDs.
   *
   * @return array|null
   *   Array of nodes loaded from the DB.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function loadSearchResultIntoNodes(array $apiSearchResults) {
    $loadedNodes = NULL;

    if (isset($apiSearchResults)) {
      // Map the API search result (Proprefs) into a simple array of Proprefs.
      $mappedResults = array_map(function ($property) {
        return $property->propertyRef;
      }, $apiSearchResults);

      $loadedNodes = $this->nt8propertymethods->loadNodesFromProprefs($mappedResults);
    }

    return $loadedNodes;
  }

  /**
   * Converts a date into Tabs' date required date format.
   */
  protected static function tabsDateFormat($originalDate) {
    $converted = date("d-m-Y", strtotime($originalDate));

    return $converted;
  }

  /**
   * Checks if a value is set in an array and returns the value if is true.
   *
   * @param array $array
   *   Array to check.
   * @param mixed $key
   *   Key to access the passed in array.
   *
   * @return bool|mixed
   *   Returns value stored under $key in the array otherwise defaults to FALSE.
   */
  public static function issetGet(array $array, $key) {
    return isset($array[$key]) ? $array[$key] : FALSE;
  }

}
