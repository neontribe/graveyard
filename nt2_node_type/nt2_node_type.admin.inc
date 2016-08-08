<?php

function nt2_node_type_admin_form() {
  $form = array();

  $form['nt2_node_type_propref'] = array(
  	'#title' => t('Property reference'),
  	'#type' => 'textfield',
  	'#description' => t('If a prop ref is entered here it will trigger an import/refresh on the propref.'),
  );

  $form['reload_tax_tag'] = array(
    '#type' => 'submit',
    '#value' => 'Reload Tags',
    '#submit' => array('nt2_entity_admin_form_reload_tax_tag'),
  );

  $form['reload_tax_loc'] = array(
    '#type' => 'submit',
    '#value' => 'Reload Locations',
    '#submit' => array('nt2_entity_admin_form_reload_tax_loc'),
  );

  $form['load_all_prop'] = array(
    '#type' => 'submit',
    '#value' => 'Load all properties',
    '#submit' => array('nt2_entity_admin_form_load_all_prop'),
  );

  $form['submit'] = array(
  	'#type' => 'submit',
  	'#value' => 'Save',
  );

  return $form;
}

function nt2_entity_admin_form_load_all_prop() {
  #Get list of properties to reload.

  $per_page = 2;

  $results = array(); 
  $ops = array();

  #Get page count
  $firstPage = NeontabsIO::getInstance()->get("/property", array("page" => 1, "pageSize" => 1));
  dpm($firstPage);
  $totalResults = $firstPage["totalResults"];

  $batch = array(
    'operations' => array(),
    'finished' => 'nt2_entity_admin_form_batch_load_props_finished',
    'title' => t('Load all properties into cache.'),
    'init_message' => t('Starting.'),
    'progress_message' => t('Processed @current out of @total.'), #Steps of 5. 
    'error_message' => t('Fix has encountered an error.'),
    'file' => drupal_get_path('module', 'nt2_node_type') . '/nt2_node_type.admin.inc',
  );

  if(!isset($totalResults)) {
    return "Cannot Fetch Total Result Count";
  }

  $pages = ceil($totalResults / $per_page);

  for($page_counter = 1; $page_counter < $pages; $page_counter++) {
    $batch["operations"][] = array("return_function_test", array($page_counter, $per_page)); 
  }

  batch_set($batch);

  batch_process('node/1');
}

function return_function_test($page_counter, $per_page, &$context) {
  $data = NeontabsIO::getInstance()->get("/property", array("page" => $page_counter, "pageSize" => $per_page));
  foreach ($data["results"] as $result) {
    $propref = $result["propertyRef"];

    #Parse the returned data.
    $parsed_data = CottageNodeManager::parseAPIPropertyReturnData(array(
      "tag" => variable_get("COTTAGE_VOCAB_MACHINE_NAME"),
      "location" => variable_get("COTTAGE_LOC_VOCAB_MACHINE_NAME"),
      ), $result);

    #Create a test node reference.
    $reference = CottageNodeManager::setPropertyReference($propref, variable_get("COTTAGE_NODE_TYPE_MACHINE_NAME"), $parsed_data);

    #Save the node reference.
    CottageNodeManager::savePropertyReference($reference);
  }

}

function nt2_entity_admin_form_batch_load_props_finished($success, $results, $operations) {
 
}

function nt2_entity_admin_form_reload_tax_tag() {

  $vocab_tag_fields = array(  
    'tag_code' => array(
      'field_name' => 'tag_code',
      'data_key_name' => 'code',
      'type' => 'text',
    ),
    'tag_brand' => array(
      'field_name' => 'tag_brand',
      'data_key_name' => 'brand',
      'type' => 'text',
    ),
  );

  #Update the cottage tag vocabulary.
  $data = NeontabsIO::getInstance()->get("/");

  $result = CottageVocabManager::create_vocabulary_from_attrib_list(variable_get("COTTAGE_VOCAB_MACHINE_NAME"), $data["constants"]["attributes"], $vocab_tag_fields, "group", "label");
}

function nt2_entity_admin_form_reload_tax_loc() {
  
    $vocab_loc_fields = array(  
        'loc_code' => array(
          'field_name' => 'loc_code',
          'data_key_name' => 'code',
          'type' => 'text',
        ),
        'loc_name' => array(
          'field_name' => 'loc_name',
          'data_key_name' => 'name',
          'type' => 'text',
        ),
        'loc_brand' => array(
          'field_name' => 'loc_brand',
          'data_key_name' => 'brandcode',
          'type' => 'text',
        ),
        // 'loc_coordinates' => array(
        //   'field_name' => 'loc_coordinates',
        //   'data_key_name' => 'coordinates',
        //   'type' => 'text',
        // ),
    );


  #Update the cottage location vocabulary.
  $data = NeontabsIO::getInstance()->get("/utility/area");

  CottageVocabManager::create_vocabulary_from_attrib_list(variable_get("COTTAGE_LOC_VOCAB_MACHINE_NAME"), $data, $vocab_loc_fields, 'code', "locations");
}

function nt2_node_type_admin_form_submit($form, &$form_state) {
  form_state_values_clean($form_state);

  $values = $form_state['values'];

  #Here you can process transitional values (e.g. refresh an entity.
  if (!empty($values['nt2_node_type_propref'])) {
  	$propref = $values['nt2_node_type_propref'];

  	#Fetch Property data from the API
  	$api_response = CottageNodeManager::fetchPropertyFromAPI($propref, "_ZZ");

  	#Parse the returned data.
  	$parsed_data = CottageNodeManager::parseAPIPropertyReturnData(array(
      "tag" => variable_get("COTTAGE_VOCAB_MACHINE_NAME"),
      "location" => variable_get("COTTAGE_LOC_VOCAB_MACHINE_NAME"),
      ), $api_response);

  	#Create a test node reference.
  	$reference = CottageNodeManager::setPropertyReference($propref, variable_get("COTTAGE_NODE_TYPE_MACHINE_NAME"), $parsed_data);

  	#Save the node reference.
  	CottageNodeManager::savePropertyReference($reference);

  	// Now unset the value so it doesn't get saved below.
  	unset($form_state['values']['nt2_node_type_propref']);
  }

  foreach ($values as $key => $value) {
	 variable_set($key, $value);
  }

  $message = t('Settings for :module have been saved.', array(':module' => __METHOD__));
  drupal_set_message($message);
}
