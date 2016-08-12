<?php

class CottageVocabManager {
	/*
	* Check whether a node of the $name provided already exists.
	*/
	public static function vocabTypeExists($name) {
		//Check to see if the vocabulary already exists.
		$vocabulary = taxonomy_vocabulary_machine_name_load($name);
		//If it exists then exit the function as there is nothing more to do.
		if ($vocabulary) {
			return $vocabulary;
		}

		return FALSE;
	}

	/*
	* Create a vocabulary used to store cottage tag entries.
	*/
	public static function createCottageTagVocabulary($machine_name, $vocab_definition, $vocab_fields) {
		#If vocabulary already exists.
		if(self::vocabTypeExists($machine_name)) {
			return TRUE;
		}

		#Setup field definitions and attach to the $machine_name type bundle.
		self::registerVocabularyFieldDefinitionInstances($machine_name, $vocab_fields);

		#Save the defined vocabulary in the database.
		taxonomy_vocabulary_save($vocab_definition);

		#Set the return value to the taxonomy generated and subsequently stored in the DB.
		$return_object = taxonomy_vocabulary_machine_name_load($machine_name);

		#If the vocabulary doesn't exist after we've saved it an error has occured so return FALSE.
		if(!isset($return_object)) {
			return FALSE;
		}

		return $return_object;
 	}

	/*
	* Register the instances for each of the fields and attach them to the taxonomy_term data type.
	*/
 	public static function registerVocabularyFieldDefinitionInstances($machine_name, $vocab_fields) {
 		foreach ($vocab_fields as $vocab_key => $field_options) {
	 		#If the field already exists skip this iteration.
	 		if(field_info_field($vocab_key)) {
				continue;
			}

			#Create the field definition.
			field_create_field($field_options);


			#Create an array in order to describe the instance of the field we wish to create.
			$instance = array(
				'field_name' => $vocab_key,
				'entity_type' => 'taxonomy_term',
				'bundle' => $machine_name,
				'description' => 'Tag data field.',
				'label' => $vocab_key,
				'widget' => array(
					'type' => 'textfield',
				)
			);

			#Invoke the field_create_instance function with the description of the instance created prior.
			field_create_instance($instance);
		}
 	}

 	/*
 	* Function used in order to add a term (tag in this context) to the cottage vocabulary.
 	*/
 	public static function addTermToVocabulary($machine_name, $term_name, $term_data = NULL, $parent = NULL) {

 		$vocab_info = self::vocabTypeExists($machine_name);


 		if(!$vocab_info) {
 			return;
 		}
 		#If the term already exists update it then return.

 		#TODO: This is messy, should be refactored.

 		if(self::taxonomy_term_exists($machine_name, $term_name)) {

 			$terms = self::get_terms_from_name($machine_name, $term_name);

			if(isset($terms) && is_array($terms)) {

				if(is_array($term_data)) {
 					#For each term stored in the DB with the same name check if values differ between the values to be modified and update accordingly.
 					foreach ($terms as $key => $value) {
 						$modified = FALSE;

						foreach ($term_data as $data_key => $data_value) {
							$accessed_array = $value->$data_key;

							#Check if values differ (currently only checks for arrays, as arrays need to be handled differently).
							if(is_array($data_value) && is_array($accessed_array)) {
								if($data_value["und"][0]["value"] != $accessed_array["und"][0]["value"]) {
									$modified = TRUE;
									$value->$data_key = $data_value;
								}
							} else {
								if($data_value != $accessed_array) {
									$modified = TRUE;
									$value->$data_key = $data_value;
								}
							}
						}

						if($modified == TRUE) {
							#dpm("Save operation!");
							taxonomy_term_save($value);
						}
 					}
 				}
			}
 		} else {

	 		#If the vocabulary doesn't exist create new instance of term.
	 		if($vocab_info) {
	 			#Set defaults.
	 			$new_term = (object) array(
					'name' => $term_name,
					'description' => 'Default description.',
					'format' => filter_default_format(),
					'vid' => $vocab_info->vid,
					'parent' => $parent,
		  		);

			  	if(is_array($term_data)) {
					foreach ($term_data as $data_key => $data_value) {
							$new_term->$data_key = $data_value;
					}
				}

				taxonomy_term_save($new_term);
	 		}
 		}
 	}

 	/*
	* Create a vocabulary from a provided list of attributes from the API.
	*/
	public static function create_vocabulary_from_attrib_list($machine_name, $api_attribs, $vocab_definitions, $nested_parent_id = "group", $nested_item_id = "label") {
		foreach ($api_attribs as $attrib) {

			#Build the data array for the current attrib.
			$field_data = array();
			$entry_names = array_keys($vocab_definitions);

			foreach ($entry_names as $key) {
				$field_data[$key] = array(
					'und' => array(
						0 => array(
							'value' => $attrib[$vocab_definitions[$key]["data_key_name"]],
						),
					),
				);
			};

			#Set the group variable.
			$currentGroup = $attrib[$nested_parent_id];
			$currentTerm = $attrib[$nested_item_id];

			self::addTermToVocabulary($machine_name, $currentGroup, $field_data);
			
			if(is_array($currentTerm)) {

				foreach($currentTerm as $child_value) {
					
					$field_data = array();
				
					$cur_child_term = $child_value[$nested_parent_id];

					foreach ($entry_names as $key) {
			
						$field_data[$key] = array(
							'und' => array(
								0 => array(
									'value' => $child_value[$vocab_definitions[$key]["data_key_name"]],
								),
							),
						);
				
					};

					#TEMPORARY SET COORDINATES TO NOTHING
					// $field_data["loc_coordinates"] = array(
					// 	'und' => array(
					// 		0 => array(
					// 			'value' => "TO BE FILLED",
					// 		),
					// 	),
					// );

					self::addTermToVocabulary($machine_name, $cur_child_term, $field_data, self::get_term_from_name($machine_name, $currentGroup));

				}	
			} else {
				self::addTermToVocabulary($machine_name, $currentTerm, $field_data, self::get_term_from_name($machine_name, $currentGroup));
			}
				
			/* Checking Code
			* Will probably be needed when the self::addTermToVocabulary function is cleaned up.
			* It isn't needed at the moment because the ::addTermToVocab... function internally checks for duplicate entries.
			* This behaviour shouldn't be expected of this function and as such it must be removed and placed here.
			*/

			#Check if group parent of hierarchy exists; if it doesn't then add it to the hierarchy.		
			#Checks done internally (kept for reference)
			#$groupExists = self::taxonomy_term_exists($machine_name, $currentGroup);
			#Checks done internally (kept for reference)
			#Check if child element exists; if it doesn't then add it to the hierarchy.
			#$labelExists = self::taxonomy_term_exists($machine_name, $currentTerm);	
		}
			
		return sizeof($api_attribs);
	}

	/*
	* Ascertain whether a specific taxonomy_term already exists in the database.
	*/
 	public static function taxonomy_term_exists($machine_name, $name) {
		$term = self::get_term_from_name($machine_name, $name);
		if($term != NULL) {
			return TRUE;
		}

		return FALSE;
	}

	/*
	* Grab the TID of a specific term from the database and return the first of these.
	*/

	//TODO: Look into merging these two functions (avoid repetition)
	public static function get_term_from_name($machine_name, $name) {
		$tid = taxonomy_get_term_by_name($name, $machine_name);

		if(sizeof($tid) == 0) {
			return NULL;
		}
		return array_shift($tid)->tid;
	}

	public static function get_terms_from_name($machine_name, $name) {
		$tid = taxonomy_get_term_by_name($name, $machine_name);

		if(sizeof($tid) == 0) {
			return NULL;
		}

		return $tid;
	}
}

