<?php

namespace Drupal\nt2_search\uk\co\neontabs;

/**
 * @file
 * Code for rendering and handling search forms, as well as displaying results.
 */

/**
 * Code for rendering and handling search forms, as well as displaying results.
 */
class SearchUI {
  /**
   * The different types of searches.
   *
   * These are represented in human readable form. They should be regarded as
   * case-insensitive, as code may change their case for compliance with Drupal
   * naming standards.
   *
   * @todo Having the unique value the human readable value is not sensible.
   *
   * @var string[]
   */
  const SEARCH_TYPES = ['Quick', 'Advanced'];

  /**
   * Generates and returns a search form.
   *
   * @param string $searchType
   *   The type of search, as a string.
   *
   * @return array
   *   Return a drupal form represented as an associative array.
   */
  public static function form($searchType) {
    $form = array();

    // Inject input elements from all enabled search terms for this search type.
    $searchTerms = SearchTabs::getSearchTerms();
    foreach ($searchTerms as &$searchTerm) {
      if (!$searchTerm->isVisible($searchType)) {
        continue;
      }
      $searchTerm->injectInputs($form);
    }

    // @todo Primitive check for name clashes.
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Search'),
    );

    return $form;
  }

  /**
   * Builds a search query from the returned form and passes it on.
   *
   * @todo Is validation necessary?
   *
   * @param string $searchType
   *   The type of search.
   * @param array $form
   *   The form that was just submitted.
   * @param array $formState
   *   The submitted parameters, name and value.
   */
  public static function submit($searchType, $form, &$formState) {
    form_state_values_clean($formState);
    $values = $formState['values'];

    // Construct a search API query based on the form response.
    $query = array();
    $searchTerms = SearchTabs::getSearchTerms();

    // Extract responses to input elements injected by enabled search terms.
    foreach ($searchTerms as &$searchTerm) {
      // If the search term is not visible on any search form...
      if (!$searchTerm->isVisible($searchType)) {
        continue;
      }

      // Inject queries from the form response.
      $searchTerm->injectParams($query, $formState['values']);
    }

    // We will pass this query as parameters to the search performing page.
    $options = array(
      'query' => $query,
    );

    // Pass query to the search page.
    drupal_goto('nt2_search', $options);
  }

  /**
   * Make a search request and render the results.
   *
   * No parameters, as they are taken from the GET parameters.
   *
   * @return array
   *   A render array representing the search results.
   */
  public static function page() {
    // Build the finished search query to pass to SearchTabs.
    $params = array();

    // The user's untrusted input, as GET parameters.
    $query = drupal_get_query_parameters();

    /*
     * Keep only recognised search codes. This will, for example, prevent users
     * from injecting "pageSize" into a search API request. Everything else is
     * assumed to be defended against in Tabs API.
     *
     * While this serves a basic defense against some forms of injection,
     * ideally NeontabsIO would allow forsearch filters and unrelated API
     * parameters to be passed in seperate arrays.
     *
     * @todo Is this a possibility?
     */

    // Build a list of codes we recognise as valid search filters.
    $codes = array();
    $searchTerms = SearchTabs::getSearchTerms();
    foreach ($searchTerms as $searchTerm) {
      $codes = array_merge($codes, $searchTerm->getCodes());
    }

    // Keep only GET parameters that represent recognised API codes.
    foreach ($query as $code => $value) {
      if (!in_array($code, $codes)) {
        // Evil unrecognised code, ignore.
        continue;
      }

      // We semi-trust this code. Inject it into our API request.
      $params[$code] = $value;
    }

    // Access the Tabs API to find the properties based on our query.
    $results = SearchTabs::findProperties($params);

    // Render each result.
    $renderArray = array();
    foreach ($results as $resultNode) {
      $view = node_view($resultNode, 'teaser');
      // @todo This seems like a horrific way to access anything.
      $propRef = $resultNode->cottage_reference['und'][0]['value'];
      $renderArray[$propRef] = $view;
    }

    return $renderArray;
  }

}
