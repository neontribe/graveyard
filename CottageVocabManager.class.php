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
	public static function createCottageTagVocabulary($machine_name, $vocab_fields) {
		#If vocabulary already exists.
		if(self::vocabTypeExists($machine_name)) {
			return TRUE;
		}

		$vocab = (object) array(
			'name' => 'Cottage Tag Vocab',
			'machine_name' => $machine_name,
			'description' => 'A vocabulary used to store cottage tags.',
			'weight' => 0,
		);

		#Setup field definitions and attach to the $machine_name type bundle.
		self::registerVocabularyFieldDefinitionInstances($machine_name, $vocab_fields);

		#Save the defined vocabulary in the database.
		taxonomy_vocabulary_save($vocab);

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
 		
 		#If the term already exists update it then return.
 		
 		#This is messy, should be refactored. 

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
 	* Bootstraps the creation of a vocabulary from the specified `$api` path.
 	*/
 	public static function setup_vocabulary_from_api($machine_name, $path) {
		#Construct request string.
		$data = NeontabsIO::getInstance()->get($path);
		#Create the vocabulary using the attributes contained in the data.
		$result = self::create_vocabulary_from_attrib_list($machine_name, $data["constants"]["attributes"]);
		return $result;
	}


 	/*
	* Create a vocabulary from a provided list of attributes from the API.	
	*/
	public static function create_vocabulary_from_attrib_list($machine_name, $attribs) {
		#Get the Vocabulary ID for cottages.
		$vid = variable_get('nt2_entity_vocab_id');
		foreach ($attribs as $attrib) {
			#Set the group variable.
			$currentGroup = $attrib["group"];
			
			#Set the term variable (label).
			$currentTerm = $attrib["label"];

			$field_data = array(
				'tag_code' => array(
					'und' => array(
						0 => array(
							'value' => $attrib["code"],
						),
					),
				),
				'tag_brand' => array(
					'und' => array(
						0 => array(
							'value' => $attrib["brand"],
						),
					),
				),
				'description' => $attrib["label"],
			);

			
			#Check if group parent of hierarchy exists; if it doesn't then add it to the ppphierarchy.
			$groupExists = self::taxonomy_term_exists($machine_name, $currentGroup);
			
			if(!$groupExists) {
				#Create a new primary term (parent set to NULL).
				self::addTermToVocabulary($machine_name, $currentGroup);
			} else {
				self::addTermToVocabulary($machine_name, $currentGroup);
			};
			
			#Check if child element exists; if it doesn't then add it to the hierarchy.
			$labelExists = self::taxonomy_term_exists($machine_name, $currentTerm);
			
			if(!$labelExists) {
				#Create a new term with the parent being set to the current group.
				self::addTermToVocabulary($machine_name, $currentTerm, $field_data, self::get_term_from_name($machine_name, $currentGroup));	
			} else {
				self::addTermToVocabulary($machine_name, $currentTerm, $field_data, self::get_term_from_name($machine_name, $currentGroup));
			};	
		};
		return sizeof($attribs);
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

