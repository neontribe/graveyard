<?php


class CottageNodeManager {
	private $machine_name;

	/*
	* Creates a new property reference node when given returned data from the API as a parameter: $data.
	*/
	public static function setPropertyReference($propref, $type_machine_name, $data = NULL) {
		$result = self::getNodesFromPropertyReference($type_machine_name, $propref);

		#If existing nodes exist for this property reference modify them.
		if(is_array($result) && $result != NULL) {
			
			#For each existing node set the relevant data provided by the $data array.
			foreach ($result as $key => $node) {
				if( is_array($data) ) {
					foreach ($data as $key => $value) {
						$node->$key = $value;
					}
				}
			}

			return $result;

		} else { #Else create a new node for the current property reference.
			$node = new stdClass();
			
			#Set type to custom type which is supplied to the function.
			$node->type = $type_machine_name;
			
			#Set drupal defaults for the new node before we apply the custom attribute data.
			node_object_prepare($node);

			#Set node data
			if( is_array($data) ) {
				foreach ($data as $key => $value) {
					$node->$key = $value;
				}
			}
		}

		return $node;
	}

	/*
	* Create a vocabulary used to store cottage tag entries.
	*/
	public static function createCottageTagVocabulary($machine_name) {
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
		
		taxonomy_vocabulary_save($vocab);

		$return_object = taxonomy_vocabulary_machine_name_load($machine_name);

		#If the vocabulary doesn't exist after we've saved it an error has occured so return FALSE.
		if(!isset($return_object)) {
			return FALSE;
		}

		return $return_object;
 	}

 	public static function addTermToVocabulary($machine_name, $term_name, $parent = NULL) {
 		$vocab_info = self::vocabTypeExists($machine_name);
 		
 		#If the term already exists quit the function quickly.
 		if(self::taxonomy_term_exists($machine_name, $term_name)) {
 			return FALSE;
 		}

 		#If the vocabulary exists continue to term creation.
 		if($vocab_info) {
 			$term = (object) array(
				'name' => $term_name,
				'description' => 'This is the description of the term.',
				'format' => filter_default_format(),
				'vid' => $vocab_info->vid,
				'parent' => $parent,
		  	);
			
			taxonomy_term_save($term);
 		}
 	}

 	public static function setup_vocabulary_from_api($machine_name, $path) {
		#Construct request string.
		$data = NeontabsIO::getInstance()->get($path);
		#Create the vocabulary using the attributes contained in the data.
		$result = self::create_vocabulary_from_attrib_list($machine_name, $data["constants"]["attributes"]);
		return $result;
	}


 	/**
	 *	Create a vocabulary from a provided list of attributes from the API.	
	 */
	public static function create_vocabulary_from_attrib_list($machine_name, $attribs) {
		#Get the Vocabulary ID for cottages.
		$vid = variable_get('nt2_entity_vocab_id');
		foreach ($attribs as $attrib) {
			#Set the group variable.
			$currentGroup = $attrib["group"];
			#Set the term variable (label).
			$currentTerm = $attrib["label"];
			#Check if group parent of hierarchy exists; if it doesn't then add it to the hierarchy.
			$groupExists = self::taxonomy_term_exists($machine_name, $currentGroup);
			if(!$groupExists) {
				#Create a new primary term (parent set to NULL).
				self::addTermToVocabulary($machine_name, $currentGroup);
			};
			#Check if child element exists; if it doesn't then add it to the hierarchy.
			$labelExists = self::taxonomy_term_exists($machine_name, $currentTerm);
			if(!$labelExists) {
				#Create a new term with the parent being set to the current group.
				self::addTermToVocabulary($machine_name, $currentTerm, self::get_term_from_name($machine_name, $currentGroup));	
			};	
		};
		return sizeof($attribs);
	}

 	public static function taxonomy_term_exists($machine_name, $name) {
		$term = self::get_term_from_name($machine_name, $name);
		if($term != NULL) {
			return TRUE;
		}
		
		return FALSE;
	}

	public static function get_term_from_name($machine_name, $name) {
		$tid = taxonomy_get_term_by_name($name, variable_get("cottage_tag_vocab"));
		
		if(sizeof($tid) == 0) {
			return NULL;
		}
		return array_shift($tid)->tid;
	}

 	/*
 	* Perform a DB query for nodes of the same reference as the one provided in the $ref variable. Return the nodes in the form of an array.
 	*/
	public static function getNodesFromPropertyReference($node_machine_name, $ref) {
		#Compose a new entity query which will ascertain whether node entries exist with the same reference as provided in $ref.
		$query = new EntityFieldQuery();
		$query->entityCondition('entity_type', 'node')
  			->entityCondition('bundle', $node_machine_name)
  			->fieldCondition('cottage_reference', 'value', $ref, '=');

  		#Assign the value of the result of executing the query to the variable $result.
  		$result = $query->execute();
  		
  		if (isset($result['node'])) {
			$items_imploded = array_keys($result['node']);
			$items_imploded = entity_load('node', $items_imploded);

			return $items_imploded;
		}

		return NULL;
	}

	/*
	* Takes a node or an array of nodes as input and saves the requisite node type for each reference.
	*/
	public static function savePropertyReference($ref) {
		#If there is more than one property reference loop through and individually create a node reference for each provided.
		if(is_array($ref)) {
			foreach ($ref as $key => $propRef) {
				if( is_object($propRef) ) {
					node_save($propRef);
				}		
			}
		}

		#Save a single reference.
		if( is_object($ref) ) {
			node_save($ref);
		}
	}

	/*
	* Takes an array as input and returns a string of newline separated values. The keys to be retained are specified in the `$values_to_keep` array.
	*/
	private static function parsePropertyValueArray($array_of_values, $values_to_keep) {
		#TODO: Use an entity as a wrapper for these value arrays such as images instead of a newline separated list.
		$output = array();
		foreach ($array_of_values as $key => $value) {
			$value_carry = array();
			foreach ($values_to_keep as $value_keep) {
				array_push($value_carry, $value[$value_keep]);	
			}
			$output[$key] = array('value' => implode("\n", $value_carry));
		}

		return $output;
	}
 
	/*
	* Takes an array of data (the data returned by the API for a property request) as input and returns an array
	* which is in the correct format to be saved as an instance of the custom cottage node_type.
	*/
	public static function parseAPIPropertyReturnData($machine_name, $data) {

		#Find which tags to retain
		$keysToKeep = array();
		foreach ($data["attributes"] as $key => $value) {
			if($value) {
				$keysToKeep[] = array(
					'tid' => self::get_term_from_name($machine_name, $key),
				);
			}
		};
	
		

		$images = self::parsePropertyValueArray($data["images"], array('alt', 'title', 'url'));
		
		$return_data = array(
			'title' => $data["name"],
			'language' => LANGUAGE_NONE,
			'body' => array(
				'und' => array(
					0 => array(
						'value' => 'This is the body content.',
					),
				),
			),
			'cottage_reference' => array(
				'und' => array(
					0 => array(
						'value' => $data["propertyRef"],
					),
				),
			),
			'cottage_brandcode' => array(
				'und' => array(
					0 => array(
						'value' => $data["brandCode"],
					),
				),
			),
			'cottage_url' => array(
				'und' => array(
					0 => array(
						'value' => $data["url"],
					),
				),
			),
			'cottage_slug' => array(
				'und' => array(
					0 => array(
						'value' => $data["slug"],
					),
				),
			),
			'cottage_name' => array(
				'und' => array(
					0 => array(
						'value' => $data["name"],
					),
				),
			),
			'cottage_accomodates' => array(
				'und' => array(
					0 => array(
						'value' => $data["accommodates"],
					),
				),
			),
			'cottage_accommodationdescription' => array(
				'und' => array(
					0 => array(
						'value' => $data["accommodationDescription"],
					),
				),
			),
			'cottage_bedrooms' => array(
				'und' => array(
					0 => array(
						'value' => $data["bedrooms"],
					),
				),
			),
			'cottage_changeoverday' => array(
				'und' => array(
					0 => array(
						'value' => $data["changeOverDay"],
					),
				),
			),
			'cottage_rating' => array(
				'und' => array(
					0 => array(
						'value' => $data["rating"],
					),
				),
			),
			'cottage_pets' => array(
				'und' => array(
					0 => array(
						'value' => (int)$data["pets"], #Cast Boolean to INT for storage in DB
					),
				),
			),
			'cottage_promote' => array(
				'und' => array(
					0 => array(
						'value' => (int)$data["promote"], #Cast Boolean to INT for storage in DB
					),
				),
			),
			'cottage_booking' => array(
				'und' => array(
					0 => array(
						'value' => $data["booking"],
					),
				),
			),
			'cottage_ownercode' => array(
				'und' => array(
					0 => array(
						'value' => $data["ownerCode"],
					),
				),
			),
			'cottage_images' => array(
				'und' => $images,
			),
			'cottage_term_reference' => array(
				'und' => $keysToKeep,
			),
		);
		
		

		return $return_data;
	}

	/*
	* This function queries the API for a specific property reference and returns an array of the data found.
	*/
	public static function fetchPropertyFromAPI($propref, $suffix) {
		$path = sprintf('property/' . strtoupper($propref) . $suffix);
		$data = NeontabsIO::getInstance()->get($path);

		return $data;
	}

	/*
	* Register the instances for each of the fields (attach them to the custom cottage node type via the bundle system).
	*/
	public static function registerCottageFieldDefinitionInstances($name, $cottage_fields) {
		foreach ($cottage_fields as $field_key => $field_options) {
			if(field_info_field($field_key)) {
				continue;
			}

			$field_options = field_create_field($field_options);

			$instance = array(
				'field_name' => $field_key,
				'entity_type' => 'node',
				'bundle' => $name,
				'description' => 'Cottage data field.',
				'label' => $field_key,
				'widget' => array(
					'type' => 'textfield',
				) 
			);

			field_create_instance($instance);
		}
	}

	/*
	* Check whether a node of the $name provided already exists.
	*/	
	public static function nodeTypeExists($name) {
		#Check to see if cottage_node type exists
		if ( in_array( $name, node_type_get_names() ) ) {
    		return TRUE;
		}

		return FALSE;
	}

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
	* Register a new cottage node entity when given a $name (machine name).
	*/
	public static function registerCottageNodeTypeEntity($name) {
		$cottage_type_defintion_array = self::generateTypeDefinitionArray($name);

		$status = node_type_save($cottage_type_defintion_array);
		node_add_body_field($cottage_type_defintion_array);

		return $status;
	}

	/*
	* Generate a new type definition which can then be used to create a new node type using drupal's internal Node API.
	*/
	private static function generateTypeDefinitionArray($name) {
		#Ascertain whether the node type currently is defined in the database.
		if(self::nodeTypeExists($name)) {
			return FALSE;
		}

		#Define new cottage node type
		$nt2_node_type = array(
			'type' => $name,
			'name' => st('Basic Cottage Entry'),
			'base' => 'node_content',
			'description' => st("Defines a cottage entry node."),
			'custom' => 1,
			'modified' => 1,
			'locked' => 0,
		);

		#Apply Drupal defaults to initial type definition array.
		$nt2_node_type = node_type_set_defaults($nt2_node_type);

		return $nt2_node_type;
	}
}