<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class NT2Search {
  // TODO: consistent capitalisation pls
  
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
    // TODO: this page can be rendered even if not directed from the form

    $params = array();

    // extract search queries
    $searchTerms = NT2Search::getSearchTerms(true); // get all enabled search terms
    foreach($searchTerms as &$searchTerm) {
      $searchTerm->injectParams($params);
    }
    
    $api = NeontabsIO::getInstance();
    $json = $api->get('property', $params);
        
    $render_array = array();
    
    // TODO: $json['results'] will not exist in the event of the API returning an error
    // at the moment this is not accounted for
    dpm($json);
    foreach ($json['results'] as $property) {
//      $node = CottageNodeManager::fetchPropertyFromAPI($property['propertyRef'], '_ZZ');
//      $_render_array = nt2_node_type_node_render_array($node);
//      $render_array[$property['id']] = $_render_array;
      $render_array[$property['propertyRef']] = array(
        '#markup' => $property['propertyRef'],
        '#prefix' => '<div>',
        '#suffix' => '</div>',
      );
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
    
    // TODO: handle custom "core" search terms

    // make a CheckboxSearchTerm for every attribute
    foreach ($json['attributes'] as $attribute) {
      $searchTerms[] = new NT2CheckboxSearchTerm($attribute['code'], $attribute['label']);
    }

    return $searchTerms;
  }
}
