<?php

class nt2EntityEntityController extends EntityAPIController {
	
	//Override buildContent
	public function buildContent($entity, $view_mode = 'full', $langcode = NULL, $content = array()) {
		//Invoke parent and grab return so we can modify for output.
		$build = parent::buildContent($entity, $view_mode, $langcode, $content);

		$build['reference'] = array(
			'#type' => 'markup',
			'#markup' => check_plain($entity->reference),
			'#prefix' => '<div class="cottage-reference"><p>Reference:',
			'#suffix' => '</p></div>',
		);
		$build['brandcode'] = array(
			'#type' => 'markup',
			'#markup' => check_plain($entity->brandcode),
			'#prefix' => '<div class="cottage-brandcode"><p>Cottage Code: ',
			'#suffix' => '</p></div>',
		);
		$build['name'] = array(
			'#type' => 'markup',
			'#markup' => check_plain($entity->name),
			'#prefix' => '<div class="cottage-name"><p>Name: ',
			'#suffix' => '</p></div>',
		);

		return $build;
	}

	public static function create_taxonomy_term($name, $vid, $parentTID = NULL) {
		$term = new stdClass();
		$term->name = $name;
		$term->vid = $vid;

		if($parentTID != NULL) {
			$term->parent = array($parentTID);
		}

		taxonomy_term_save($term);
		return $term->tid;
	}

	public static function taxonomy_term_exists($name) {
		$term = self::get_term_from_name($name);

		if($term != NULL) {
			return TRUE;
		}
		
		return FALSE;
	}

	public static function get_term_from_name($name) {
		$tid = taxonomy_get_term_by_name($name, variable_get("cottage_tag_vocab"));
		
		if(sizeof($tid) == 0) {
			return NULL;
		}

		return array_shift($tid)->tid;
	}

	public static function create_vocabulary() {
		$vocab = array(
			'name' => 'Cottage Tag Vocabulary',
			'machine_name' => variable_get("COTTAGE_TAG_VOCAB_MACHINE_NAME"),
			'description' => t('Vocabulary of available cottage tags'),
			'module' => 'nt2_entity',
			'hierarchy' => 1,
		);

		taxonomy_vocabulary_save((object) $vocab);

		$vocab = taxonomy_vocabulary_machine_name_load(variable_get("COTTAGE_TAG_VOCAB_MACHINE_NAME"));
		$vid = $vocab->vid;

		//Set 'nt2_entity_vocab_id' to the ID of the vocabulary created for the entity.
		variable_set('nt2_entity_vocab_id', $vid);
	}


	public static function setup_vocabulary_from_api($path) {
		#Construct request string.
		$data = NeontabsIO::getInstance()->get($path);

		#Create the vocabulary using the attributes contained in the data.
		$result = self::create_vocabulary_from_attrib_list($data["constants"]["attributes"]);

		return $result;
	}

	/**
	 *	Create a vocabulary from a provided list of attributes from the API.	
	 */
	public static function create_vocabulary_from_attrib_list($attribs) {
		#Get the Vocabulary ID for cottages.
		$vid = variable_get('nt2_entity_vocab_id');

		foreach ($attribs as $attrib) {
			#Set the group variable.
			$currentGroup = $attrib["group"];
			#Set the term variable (label).
			$currentTerm = $attrib["label"];

			#Check if group parent of hierarchy exists; if it doesn't then add it to the hierarchy.
			$groupExists = self::taxonomy_term_exists($currentGroup);
			if(!$groupExists) {
				#Create a new primary term (parent set to NULL).
				nt2EntityEntityController::create_taxonomy_term($currentGroup, $vid);
			};

			#Check if child element exists; if it doesn't then add it to the hierarchy.
			$labelExists = self::taxonomy_term_exists($currentTerm);
			if(!$labelExists) {
				#Create a new term with the parent being set to the current group.
				nt2EntityEntityController::create_taxonomy_term($currentTerm, $vid, self::get_term_from_name($currentGroup));	
			};	
		};

		return sizeof($attribs);
	}
}