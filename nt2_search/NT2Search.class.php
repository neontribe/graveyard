<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class NT2Search {
  // TODO: consistent capitalisation pls
  // TODO make t() usage consistent
  
  public static function quickSearchForm() {
    $form = array();

    // inject input elements
    $searchTerms = NT2Search::getSearchTerms(true); // get all enabled search terms
    foreach($searchTerms as &$searchTerm) {
      $searchTerm->injectInputs($form);
    }

    // TODO: primitive check for name clashes
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Search'),
    );

    return $form;
  }
  
  public static function quickSearchFormSubmit($form, &$form_state) {
    $values = $form_state['values'];
    
    // Drupal should check that only form values are present here
    // TODO: test that this is the case
    $options = array(
      'query' => $values,
    );
    
    // pass query to the search page
    // TODO: see if this can be justified, as it likely renders Drupal's form parameter checking useless
    drupal_goto('nt2_search', $options);
  }
  
  public static function page() {
    // TODO: this page can be rendered even if not directed from the form - is that useful or horrific?

    $params = array();
    
    $fields = ['propertyRef'];
    $params['fields'] = implode(':', $fields);

    // extract search queries
    $searchTerms = NT2Search::getSearchTerms(true); // get all enabled search terms
    foreach($searchTerms as &$searchTerm) {
      $searchTerm->injectParams($params);
    }
    dpm($params, 'params');
    
    $api = NeontabsIO::getInstance();
    $json = $api->get('property', $params);
        
    $render_array = array();
    
    // TODO: $json['results'] will not exist in the event of the API returning an error
    // at the moment this is not accounted for
    dpm($json, 'json');
    foreach ($json['results'] as $property) {
  
     $node = CottageNodeManager::loadNode($property['propertyRef']);
    
     $render_array[$property['propertyRef']] = nt2_node_type_teaser_node_render_array($node);

    }
    
    return $render_array;
  }

  public static function getSearchTerms($onlyEnabled = false) {
    // TODO: handle onlyEnabled, including dependency checks, etc

    $searchTerms = array();

    // TODO: caching, is hitting the API everytime necessary?
    $api = NeontabsIO::getInstance();
    $json = $api->get('/'); // hit up the root API - TODO: this seems to return NULL in some scenarios
    $json = $json['constants']['searchTerms']; // we only care about searchTerms - TODO: check this doesn't have horrific performance implications
    
    // make porentially conflicting search term objects for present core terms
    // TODO: this core coverage is incomplete
    
    // convert from flat array to key=>value, with 'code' as key
    $coreTerms = array();
    foreach ($json['core'] as $coreTerm) {
      $coreTerms[$coreTerm['code']] = $coreTerm;
    }

    // add potential terms from present and sane core terms
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

    // TODO: explain this
    if (array_key_exists('fromDate', $coreTerms) && $coreTerms['fromDate']['type'] == 'string') {
      // TODO: work out the date SearchTerm
      
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

        // TODO: join and add as a GroupedSearchTerm
      }
      // TODO: add it on its own as well
    }

    // make a basic simple input for every attribute
    foreach ($json['attributes'] as $attribute) {
      // TODO: do we want /all/ of the attributes?
      switch (strtolower($attribute['type'])) {
        case 'boolean':
          $searchTerms[] = new NT2CheckboxSearchTerm($attribute['code'], $attribute['label']);
          break;
        case 'number':
          // TODO: handle the number use-case
          break;
        case 'text':
        case 'long text':
          // TODO: handle the string use-case
          break;
        default:
          // TODO: there are probably more types
          break;
      }
    }

    return $searchTerms;
  }
}
