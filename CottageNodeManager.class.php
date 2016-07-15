<?php


class CottageNodeManager {
	/*
	* Creates a new property reference node when given returned data from the API as a parameter: $data.
	*/
	public static function createNewPropertyReference($type_machine_name, $data = NULL) {
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
		
		return $node;
	}

	public static function savePropertyReference($ref) {
		#Save the reference.
		if( is_object($ref) ) {
			node_save($ref);
		}
	}

	public static function parseAPIReturnData($data) {
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
			'cottage_brandcode'=> array(
				'und' => array(
					0 => array(
						'value' => $data["brandCode"],
					),
				),
			),
		);

		return $return_data;
	}

	public static function fetchPropertyFromAPI($propref) {
		$path = sprintf('property/' . strtoupper($propref) . '_ZZ');
		$data = NeontabsIO::getInstance()->get($path);

		return $data;
	}


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
				'description' => '.',
				'label' => $field_key,
				'widget' => array(
					'type' => 'textfield',
				) 
			);

			field_create_instance($instance);
		}
	}

	public static function nodeTypeExists($name) {
		#Check to see if cottage_node type exists
		if ( in_array( $name, node_type_get_names() ) ) {
    		return TRUE;
		}

		return FALSE;
	}

	public static function registerCottageNodeTypeEntity($name = 'cottage_entity') {
		$cottage_type_defintion_array = self::generateTypeDefinitionArray($name);

		node_type_save($cottage_type_defintion_array);
		node_add_body_field($cottage_type_defintion_array);
	}

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