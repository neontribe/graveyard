<?php

/**
 * @file
 * Contains the NT2SearchTabs class.
 */

use Drupal\nt2_io\uk\co\neontabs\NeontabsIO;

/**
 * Methods for interfacing with Tabs API for search-related functions.
 */
class NT2SearchTabs {
  /**
   * The number of properties to load from Tabs API at a time for searches.
   *
   * The value below is `(2^32)-1`. This is the maximum possible value without
   * causing an overflow, upon which Tabs returns no properties.
   *
   * @var int
   */
  const TABS_PAGE_SIZE = 2147483647;

  /**
   * Finds property references - based on a query - with Tabs API.
   *
   * @param array $query
   *   An associative array of the filters to use when searching for properties.
   *
   * @return string[]
   *   A list of unprocessed property references from Tabs API.
   *
   * @see findPropertiesReferences
   */
  public static function findPropertiesReferences($query) {
    // We just want property references back from Tabs API.
    $fields = ['propertyRef'];
    $query['fields'] = implode(':', $fields);

    // Load ALL the properties! We'll sort and paginate as we please.
    $query['pageSize'] = NT2SearchTabs::TABS_PAGE_SIZE;

    // Query Tabs API with parameters.
    $json = NeontabsIO::getInstance()->get('property', $query);

    // @todo $json['results'] will not exist in the event of the API returning
    // an error; at the moment this is not accounted for.

    // Extract property references.
    $propertyReferences = array();
    foreach ($json['results'] as $result) {
      $propertyReferences[] = $result['propertyRef'];
    }

    return $propertyReferences;
  }

  /**
   * Finds properties - based on a query - with Tabs API.
   *
   * This differs to `findPropertiesReferences`, as it finds a cottage node
   * object for each property returned.
   *
   * @param array $query
   *   An associative array of the filters to use when searching for properties.
   *
   * @return array
   *   Return an array of cottage nodes.
   *
   * @see findPropertiesReferences
   */
  public static function findProperties($query) {
    // Perform a search for the property references.
    $propertyReferences = self::findPropertiesReferences($query);

    // Get a cottage node for each property.
    $propertyNodes = array();
    foreach ($propertyReferences as $propertyReference) {
      // @todo Check for errors here.
      $propertyNodes[] = CottageNodeManager::loadNode($propertyReference);
    }

    return $propertyNodes;
  }

  /**
   * Gets potential search terms from the API.
   *
   * This function will return any search terms that can potentially be added to
   * a form based on what search terms the API returns. Different
   * implementations may be returned for the same API codes, so one should note
   * to check that a search term has been made visible before using it.
   *
   * @return NT2SearchTerm[]
   *   A list of potential search terms allowed by the API.
   */
  public static function getSearchTerms() {
    $searchTerms = array();

    // @todo Caching, is hitting the API everytime necessary?
    $json = NeontabsIO::getInstance()->get('/');

    // Keep only searchTerms; everything else is irrelevant going forwards.
    $json = $json['constants']['searchTerms'];

    // Create search terms from the 'core' and 'attributes' sections.
    $searchTerms = array_merge($searchTerms, self::extractCoreTerms($json['core']));
    $searchTerms = array_merge($searchTerms, self::extractAttributesTerms($json['attributes']));

    return $searchTerms;
  }

  /**
   * Generate search terms from the 'core' section of the API.
   *
   * This is where the majority of hardcoding should be for certain codes,
   * hopefully keeping the rest of the module hardcoding-free.
   *
   * @param array $flatJson
   *   The 'core' section of the API.
   *
   * @return NT2SearchTerm[]
   *   The implementations we were able to find given the API response.
   */
  protected static function extractCoreTerms($flatJson) {
    // @todo Core coverage is incomplete. Complete it.
    $searchTerms = array();

    // Convert flat array to associative array, $arrayElement['code']=>$arrayElement.
    $json = array();
    foreach ($flatJson as $coreJsonTerm) {
      $json[$coreJsonTerm['code']] = $coreJsonTerm;
    }

    if (array_key_exists('accommodates', $json) && $json['accommodates']['type'] === 'integer') {
      $defaults = array(
        'unspecified' => 'Any',
        'minimum' => 1,
        'maximum' => 10,
        'unlimited' => TRUE,
        'singularNoun' => 'person',
        'pluralNoun' => 'people',
      );
      $searchTerms[] = new NT2SelectRangeSearchTerm('accommodates', $json['accommodates']['label'], $defaults);
    }

    if (array_key_exists('bedrooms', $json) && $json['bedrooms']['type'] === 'integer') {
      $defaults = array(
        'unspecified' => 'Any',
        'minimum' => 1,
        'maximum' => 10,
        'unlimited' => FALSE,
        'singularNoun' => 'bedroom',
        'pluralNoun' => 'bedrooms',
      );
      $searchTerms[] = new NT2SelectRangeSearchTerm('bedrooms', $json['bedrooms']['label'], $defaults);
    }

    // @todo Explain this better than just the summary in NT2GroupSearchTerm.
    if (array_key_exists('fromDate', $json) && $json['fromDate']['type'] == 'string') {
      // @todo Work out the date SearchTerm.

      if (array_key_exists('nights', $json) && $json['nights']['type'] === 'integer') {
        $nightsDefaults = array(
          'unspecified' => 'Any',
          'minimum' => 1,
          'maximum' => 28,
          'unlimited' => FALSE,
          'singularNoun' => 'night',
          'pluralNoun' => 'nights',
        );
        $nightsSearchTerm = new NT2SelectRangeSearchTerm('nights', $json['nights']['label'], $defaults);

        // @todo Join and add as a GroupSearchTerm.
      }
      // @todo Add it on its own as well.
    }

    return $searchTerms;
  }

  /**
   * Generate search terms from the 'attributes' section of the API.
   *
   * This is where the majority of hardcoding should be for certain codes,
   * hopefully keeping the rest of the module hardcoding-free, although for
   * 'attributes' the majority of these will be auto-generated.
   *
   * @param array $json
   *   The 'attributes' section of the API.
   *
   * @return NT2SearchTerm[]
   *   The implementations we were able to find given the API response.
   */
  protected static function extractAttributesTerms($json) {
    $searchTerms = array();

    // Make a basic simple input for every attribute.
    foreach ($json as $attributeJson) {
      $code = $attributeJson['code'];
      $type = $attributeJson['type'];
      $label = $attributeJson['label'];
      // @todo Do we want /all/ of the attributes?
      switch (strtolower($type)) {
        case 'boolean':
          $searchTerms[] = new NT2CheckboxSearchTerm($code, $label);
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
