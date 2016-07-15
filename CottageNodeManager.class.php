<?php


class CottageNodeManager {
	/*
	* Creates a new property reference node when given returned data from the API as a parameter: $data.
	*/
	public static function setPropertyReference($propref, $type_machine_name, $data = NULL) {
		$result = self::getNodesFromPropertyReference($propref);

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

	public static function getNodesFromPropertyReference($ref) {
		#Compose a new entity query which will ascertain whether node entries exist with the same reference as provided in $ref.
		$query = new EntityFieldQuery();
		$query->entityCondition('entity_type', 'node')
  			->entityCondition('bundle', variable_get("COTTAGE_NODE_TYPE_MACHINE_NAME"))
  			->fieldCondition('cottage_reference', 'value', $ref, '=');

  		#Assign the value of the reuslt of executing the query to the variable $result.
  		$result = $query->execute();
  		
  		if (isset($result['node'])) {
			$items_imploded = array_keys($result['node']);
			$items_imploded = entity_load('node', $items_imploded);

			return $items_imploded;
		}

		return NULL;
	}

	public static function savePropertyReference($ref) {
		if(is_array($ref)) {
			foreach ($ref as $key => $propRef) {
				if( is_object($propRef) ) {
					node_save($propRef);
				}		
			}
		}

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
				'description' => 'Cottage data field.',
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