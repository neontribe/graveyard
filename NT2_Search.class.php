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

    $form['NT2_Search_pets'] = array(
      '#type' => 'checkbox',
      '#title' => t('Pets'),
    );

    $form['NT2_Search_garden'] = array(
      '#type' => 'checkbox',
      '#title' => t('Garden'),
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
      'pets' => $values['NT2_Search_pets'],
      'garden' => $values['NT2_Search_garden'],
    );
    
    $options = array(
      'query' => $params,
    );
    
    drupal_goto('nt2_search', $options);
  }
  
  public static function page() {
    $accom = filter_input(INPUT_GET, 'accommodates');
    $location = filter_input(INPUT_GET, 'location');
    $pets = filter_input(INPUT_GET, 'pets');
    $garden = filter_input(INPUT_GET, 'garden');

    // convert the checkbox 0 or 1 to 'false' or 'true' respectively
    $pets = ($pets === 1) ? 'true' : 'false';
    $garden = ($garden === 1) ? 'true' : 'false';
    
    $params = array(
      'accommodates' => $accom,
      'location' => $location,
      'pets' => $pets,
      'ATTR08' => $garden,
      'fields' => 'propertyRef',
    );
    
    $api = NeontabsIO::getInstance();
    $json = $api->get('/property', $params);
        
    $render_array = array();
    
    // TODO: $json['results'] will not exist in the event of the API returning an error
    // at the moment this is not accounted for
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
}
