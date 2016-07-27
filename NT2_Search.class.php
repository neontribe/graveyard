<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class NT2_Search {
  
  
  public static function quickSearchForm() {
    $form = array();
    
    $form['NT2_Search_accomodates'] = array(
      '#type' => 'select',
      '#title' => t('How many people'),
      '#options' => drupal_map_assoc(array(1,2,3,4,5,6,7,'8+',)),
    );
    
    // Go and get a list of valid locations from the API.
    // Hint, it's in a vovabulary.
    $options = array(
      'SWAN' => 'Swansea',
      'PONT' => 'Pontardawe',
      'NEAT' => 'Neath',
    );
    $form['NT2_Search_location'] = array(
      '#type' => 'select',
      '#title' => t('Where'),
      '#options' => $options,
    );
    
    $form['NT2_Search_submit'] = array(
      '#type' => 'submit',
      '#value' => t('Search'),
    );
    
    return $form;
  }
  
  public static function quickSearchFormSubmit($form, &$form_state) {
    $values = $form_state['values'];
  
    // Now we do the search
    $params = array(
      'accommodates' => $values['NT2_Search_accomodates'],
      'location' => $values['NT2_Search_location'],
    );
    
    $options = array(
      'query' => $params,
    );
    
    drupal_goto('nt2_search', $options);
  }
  
  public static function page() {
    $accom = filter_input(INPUT_GET, 'accommodates');
    $location = filter_input(INPUT_GET, 'location');
    
    $params = array(
      'accommodates' => $accom,
      'location' => $location,
//      'fields' => array(
//        'propertyRef',
//      ),
    );
    
    $api = NeontabsIO::getInstance();
    $json = $api->get('/property', $params);
    dpm($json, 'json');
    
    // See if you can make this back into a render array, with pre wrapper.
    return $json;
//    $render_array = array();
//    
//    foreach ($json['results'] as $property) {
//      $node = CottageNodeManager::fetchPropertyFromAPI($property['propertyRef'], '_ZZ');
//      $_render_array = nt2_node_type_node_render_array($node);
//      $render_array[$property['id']] = $_render_array;
//    }
//    
//    return $render_array;
  }
}
