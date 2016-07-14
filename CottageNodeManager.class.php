<?php


class CottageNodeManager {
	var $cottage_fields = array(
		'reference' => array(
			'field_name' => 'cottage_reference',
			'type' => 'text',

		),
		'brandcode' => array(
			'field_name' => 'cottage_brandcode',
			'type' => 'text',
		),

	);

	public static function generateTypeDefinitionArray($name) {
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

	public static function nodeTypeExists($name) {
		#Check to see if cottage_node type exists
		if ( in_array( $name, node_type_get_names() ) ) {
    		return TRUE;
		}

		return FALSE;
	}

	public static function registerCottageNodeTypeEntity($name = 'cottage_entry') {
		$cottage_type_defintion_array = self::generateTypeDefinitionArray($name);

		node_type_save($cottage_type_defintion_array);
		node_add_body_field($cottage_type_defintion_array);
	}

}