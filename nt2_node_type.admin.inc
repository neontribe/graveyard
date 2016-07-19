<?php

function nt2_node_type_admin_form() {
  $form = array();

  $form['nt2_node_type_propref'] = array(
  	'#title' => t('Property reference'),
  	'#type' => 'textfield',
  	'#description' => t('If a prop ref is entered here it will trigger an import/refresh on the propref.'),
  );

  $form['reload_tax'] = array(
    '#type' => 'submit',
    '#value' => 'Reload Tags',
    '#submit' => array('nt2_entity_admin_form_reload_tax'),
  );

  $form['submit'] = array(
  	'#type' => 'submit',
  	'#value' => 'Save'
  );

  return $form;
}

function nt2_entity_admin_form_reload_tax() {
  $vocab = CottageNodeManager::vocabTypeExists(variable_get("COTTAGE_VOCAB_MACHINE_NAME"));
  if($vocab) {
    #Delete each individual term.
    foreach (taxonomy_get_tree($vocab->vid) as $term) {
        taxonomy_term_delete($term->tid);
    }
  }
  $message = t('Taxonomy vocabulary data reloaded.');
  drupal_set_message($message);

  #Create the cottage tag vocabulary.
  CottageNodeManager::setup_vocabulary_from_api(variable_get("COTTAGE_VOCAB_MACHINE_NAME"), "/");
}

function nt2_node_type_admin_form_submit($form, &$form_state) {
  form_state_values_clean($form_state);

  $values = $form_state['values'];

  // Here you can process transitional values (e.g. refresh an entity.
  if (!empty($values['nt2_node_type_propref'])) {
  	$propref = $values['nt2_node_type_propref'];

  	#Fetch Property data from the API
  	$api_response = CottageNodeManager::fetchPropertyFromAPI($propref, "_ZZ");

    dpm($api_response);

  	#Parse the returned data.
  	$parsed_data = CottageNodeManager::parseAPIPropertyReturnData(variable_get("COTTAGE_VOCAB_MACHINE_NAME"), $api_response);

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