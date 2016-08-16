<?php

/**
 * @file
 * Code for rendering and handling search forms, as well as displaying results.
 */

use Drupal\nt2_io\uk\co\neontabs\NeontabsIO;

/**
 * Code for rendering and handling search forms, as well as displaying results.
 *
 * @todo Consistent capitalisation.
 * @todo Make t() usage consistent.
 */
class NT2Search {

  /**
   * Generates and returns a quick search form.
   *
   * @return array
   *   Return a drupal form represented as an associative array.
   */
  public static function quickSearchForm() {
    $form = array();

    // Inject input elements from all enabled search terms.
    $searchTerms = NT2Search::getSearchTerms(TRUE);
    foreach ($searchTerms as &$searchTerm) {
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
   * @param array $form
   *   The form that was just submitted.
   * @param array $formState
   *   The submitted parameters, name and value.
   */
  public static function quickSearchFormSubmit($form, &$formState) {
    $values = $formState['values'];

    // Drupal should check that only form values are present here.
    // @todo Test that this is the case.
    $options = array(
      'query' => $values,
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

    $fields = ['propertyRef'];
    $params['fields'] = implode(':', $fields);

    // Extract search queries with all enabled search terms.
    $searchTerms = NT2Search::getSearchTerms(TRUE);
    foreach ($searchTerms as &$searchTerm) {
      $searchTerm->injectParams($params);
    }

    $api = NeontabsIO::getInstance();
    $json = $api->get('property', $params);

    $renderArray = array();

    // @todo $json['results'] will not exist in the event of the API returning
    // an error; at the moment this is not accounted for.

    // @todo Actually render results.

    return $renderArray;
  }

  /**
   * Get the search terms, as deduced from the API.
   *
   * @todo The form type should probably need to be specified to check whether
   * each search term is enabled.
   *
   * @param bool $onlyEnabled
   *   Whether only enabled search terms should be returned. If FALSE, all
   *   search terms are returned.
   */
  public static function getSearchTerms($onlyEnabled = FALSE) {
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
