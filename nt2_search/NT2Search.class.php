<?php

/**
 * @file
 * Code for rendering and handling search forms, as well as displaying results.
 */

use Drupal\nt2_io\uk\co\neontabs\NeontabsIO;

/**
 * Code for rendering and handling search forms, as well as displaying results.
 *
 * @todo Make t() usage consistent.
 * @todo Consider breaking up this god-object.
 */
class NT2Search {
  /**
   * The prefix to use when making persistant settings with variable_set().
   *
   * @var string
   */
  const CONFIGURATION_PREFIX = 'nt2_search_';

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
    $searchTerms = NT2Search::getSearchTerms();
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
   * Passes the values from the submitted quick search form to the results page.
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
    $searchTerms = NT2Search::getSearchTerms();

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
    $params = array();

    // We just want property references back from Tabs API.
    $fields = ['propertyRef'];
    $params['fields'] = implode(':', $fields);

    /*
     * Keep only recognised search codes. While this serves a basic defense
     * against some forms of injection, ideally NeontabsIO would allow for
     * search filters and unrelated API parameters to be passed in seperate
     * arrays.
     *
     * @todo Is this a possibility?
     */
    $query = drupal_get_query_parameters();
    $codes = array();
    $searchTerms = NT2Search::getSearchTerms();
    foreach ($searchTerms as $searchTerm) {
      $codes = array_merge($codes, $searchTerm->getCodes());
    }
    foreach ($query as $code => $value) {
      if (!in_array($code, $codes)) {
        // Evil unrecognised code, ignore.
        continue;
      }

      // We semi-trust this code. Inject it into our API request.
      $params[$code] = $value;
    }

    $api = NeontabsIO::getInstance();
    $json = $api->get('property', $params);

    $renderArray = array();

    // @todo $json['results'] will not exist in the event of the API returning
    // an error; at the moment this is not accounted for.

    $results = $json['results'];

    foreach ($results as $result) {
      $propRef = $result['propertyRef'];

      // Load node from database.
      $node = CottageNodeManager::loadNode($propRef);

      $view = node_view($node, 'teaser');

      $renderArray[$propRef] = $view;
    }

    return $renderArray;
  }

  /**
   * Get the search terms, as deduced from the API.
   *
   * @return NT2SearchTerm[]
   *   A list of potential search terms allowed by the API.
   */
  public static function getSearchTerms() {
    // @todo handle onlyEnabled, including dependency checks, etc.

    $searchTerms = array();

    // @todo caching, is hitting the API everytime necessary?
    $api = NeontabsIO::getInstance();
    $json = $api->get('/');

    // Keep only searchTerms; everything else is irrelevant going forwards.
    $json = $json['constants']['searchTerms'];

    // Make potentially conflicting search term objects for present core terms.
    // @todo this core coverage is incomplete.

    // Convert from flat array to key=>value, with 'code' as key.
    $coreTerms = array();
    foreach ($json['core'] as $coreTerm) {
      $coreTerms[$coreTerm['code']] = $coreTerm;
    }

    // Add potential terms from present and sane core terms.
    if (array_key_exists('accommodates', $coreTerms) && $coreTerms['accommodates']['type'] === 'integer') {
      $defaults = array(
        'unspecified' => 'Any',
        'minimum' => 1,
        'maximum' => 10,
        'unlimited' => TRUE,
        'singularNoun' => 'person',
        'pluralNoun' => 'people',
      );
      $searchTerms[] = new NT2SelectRangeSearchTerm('accommodates', $coreTerms['accommodates']['label'], $defaults);
    }

    if (array_key_exists('bedrooms', $coreTerms) && $coreTerms['bedrooms']['type'] === 'integer') {
      $defaults = array(
        'unspecified' => 'Any',
        'minimum' => 1,
        'maximum' => 10,
        'unlimited' => FALSE,
        'singularNoun' => 'bedroom',
        'pluralNoun' => 'bedrooms',
      );
      $searchTerms[] = new NT2SelectRangeSearchTerm('bedrooms', $coreTerms['bedrooms']['label'], $defaults);
    }

    // @todo Explain this better than just the summary in NT2GroupSearchTerm.
    if (array_key_exists('fromDate', $coreTerms) && $coreTerms['fromDate']['type'] == 'string') {
      // @todo Work out the date SearchTerm.

      if (array_key_exists('nights', $coreTerms) && $coreTerms['nights']['type'] === 'integer') {
        $nightsDefaults = array(
          'unspecified' => 'Any',
          'minimum' => 1,
          'maximum' => 28,
          'unlimited' => FALSE,
          'singularNoun' => 'night',
          'pluralNoun' => 'nights',
        );
        $nightsSearchTerm = new NT2SelectRangeSearchTerm('nights', $coreTerms['nights']['label'], $defaults);

        // @todo Join and add as a GroupSearchTerm.
      }
      // @todo Add it on its own as well.
    }

    // Make a basic simple input for every attribute.
    foreach ($json['attributes'] as $attribute) {
      // @todo Do we want /all/ of the attributes?
      switch (strtolower($attribute['type'])) {
        case 'boolean':
          $searchTerms[] = new NT2CheckboxSearchTerm($attribute['code'], $attribute['label']);
          break;

        case 'number':
          // @todo Handle the number use-case.
          break;

        case 'text':
        case 'long text':
          // @todo Handle the string use-case.
          break;

        default:
          // @todo Check whether there are more types.
          break;
      }
    }

    return $searchTerms;
  }

}
