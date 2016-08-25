<?php

/**
 * @file
 * Contains the class CottageNodeManager.
 */

use Drupal\nt2_io\uk\co\neontabs\NeontabsIO;

/**
 * CottageNodeManager manages the creation, updating and deleting of cottage node entries.
 *
 * Provides a plethora of helpful methods so as to achieve this goal.
 */
class CottageNodeManager {

  /**
   * Creates a new property reference node when given returned data from the API as a parameter: $data.
   */
  public static function createNode($propref, $type_machine_name, $data = NULL) {
    $result = self::loadNode($propref);

    // If existing nodes exist for this property reference modify them.
    if ($result != NULL) {

      // dpm($result, 'Result');
      // dpm($data, 'Data');.
      if (is_array($data)) {
        foreach ($data as $key => $value) {
          // dpm($value, $key);.
          $result->$key = $value;
        }
      }

      // dpm($result, 'Updated Property');.
      return $result;

    }
    // Else create a new node for the current property reference.
    else {
      $node = new stdClass();

      // Set type to custom type which is supplied to the function.
      $node->type = $type_machine_name;

      // Set drupal defaults for the new node before we apply the custom attribute data.
      node_object_prepare($node);

      // Set node data.
      if (is_array($data)) {
        foreach ($data as $key => $value) {
          $node->$key = $value;
        }
      }
    }
    // dpm($node, 'New Property');.

    return $node;
  }

  /**
   * Gets a single property node.
   *
   * @param string $propref
   *   Property reference string.
   * @param string $node_machine_name
   *   Machine name for the node type.
   *
   * @return Object
   *   The loaded object or NULL.
   */
  public static function loadNode($propref, $node_machine_name = 'cottage_entity') {
    // Compose a new entity query which will ascertain whether node entries exist with the same reference as provided in $ref.
    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', 'node')
      ->entityCondition('bundle', $node_machine_name)
      ->fieldCondition('cottage_reference', 'value', $propref, '=');

    // Assign the value of the result of executing the query to the variable $result.
    $result = $query->execute();

    if (isset($result['node'])) {
      $refs = array_keys($result['node']);
      $props = entity_load('node', $refs);

      if (count($props) == 1) {
        return array_pop($props);
      }
      elseif (count($props) == 0) {
        watchdog(__METHOD__, 'Property not found :propref', array(':propref' => $propref));
      }
      else {
        watchdog(__METHOD__, ':count properties not found :propref', array(':count' => count($props), ':propref' => $propref));
        return array_pop($props);
      }
    }

    return NULL;
  }

  /**
   * Takes a node or an array of nodes as input and saves the requisite node type for each reference.
   */
  public static function saveNode($node) {
    // If there is more than one property reference loop through and individually create a node reference for each provided.
    if (is_array($node)) {
      foreach ($node as $key => $propRef) {
        if (is_object($propRef)) {
          node_save($propRef);
        }
      }
    }
    elseif (is_object($node)) {
      // Save a single reference.
      node_save($node);
    }
  }

  /**
   * Takes an array as input and returns a string of newline separated values. The keys to be retained are specified in the `$values_to_keep` array.
   */
  private static function parsePropertyValueArray($array_of_values, $values_to_keep) {
    // TODO: Use an entity as a wrapper for these value arrays such as images instead of a newline separated list.
    $output = array();
    foreach ($array_of_values as $key => $value) {
      $value_carry = array();
      foreach ($values_to_keep as $value_keep) {
        if (empty($value[$value_keep])) {
          $value[$value_keep] = "no_" . $value_keep;
        }

        array_push($value_carry, $value[$value_keep]);
      }
      $output[$key] = array('value' => implode("\n", $value_carry));
    }

    return $output;
  }

  /**
   * Takes an array of data (the data returned by the API for a property request) as input and returns an array which is in the correct format to be saved as an instance of the custom cottage node_type.
   */
  public static function parseApiPropertyReturnData($data, $machine_name = array("tag" => "", "location" => "")) {

    // Find which tags to retain.
    $tagKeysToKeep = array();
    foreach ($data["attributes"] as $key => $value) {
      if ($value) {
        $tagKeysToKeep[] = array(
          'tid' => CottageVocabManager::getTermFromName($machine_name["tag"], $key),
        );
      }
    };

    $locationKey[] = array(
      'tid' => CottageVocabManager::getTermFromName($machine_name["location"], $data["location"]["code"]),
    );

    $address = array(
      'thoroughfare' => $data["address"]["addr1"],
      'premise' => $data["address"]["addr2"],
      'locality' => $data["address"]["town"],
      'administrative_area' => $data["address"]["county"],
      'postal_code' => $data["address"]["postcode"],
      'country' => $data["address"]["country"],
    );

    $coordinates = array(
      0 => array(
        'value' => $data["coordinates"]["latitude"],
      ),
      1 => array(
        'value' => $data["coordinates"]["longitude"],
      ),
    );

    $images = self::parsePropertyValueArray($data["images"],
      array(
        'alt',
        'title',
        'url',
      )
    );

    $return_data = array(
      'title' => $data["name"],
      'language' => LANGUAGE_NONE,
      'body' => array(
        'und' => array(
          0 => array(
            'value' => '',
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
      'cottage_description' => array(
        'und' => array(
          0 => array(
            'value' => $data["brands"][$data["brandCode"]]["description"],
          ),
        ),
      ),
      'cottage_description_short' => array(
        'und' => array(
          0 => array(
            'value' => $data["brands"][$data["brandCode"]]["short"],
          ),
        ),
      ),
      'cottage_description_teaser' => array(
        'und' => array(
          0 => array(
            'value' => $data["brands"][$data["brandCode"]]["teaser"],
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
    // Cast Boolean to INT for storage in DB.
            'value' => (int) $data["pets"],
          ),
        ),
      ),
      'cottage_promote' => array(
        'und' => array(
          0 => array(
    // Cast Boolean to INT for storage in DB.
            'value' => (int) $data["promote"],
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
        'und' => $tagKeysToKeep,
      ),
      'cottage_location_reference' => array(
        'und' => $locationKey,
      ),
      'cottage_fieldaddress' => array(
        'und' => array(
          0 => $address,
        ),
      ),
      'cottage_coordinates' => array(
        'und' => $coordinates,
      ),
      'cottage_pricing' => array(
        'und' => array(
          0 => array(
            'value' => drupal_json_encode($data["brands"][$data["brandCode"]]["pricing"]),
          ),
        ),
      ),
    );

    return $return_data;
  }

  /**
   * This function queries the API for a specific property reference and returns an array of the data found.
   */
  public static function fetchPropertyFromApi($propref, $suffix) {

    $path = sprintf('property/' . strtoupper($propref) . $suffix);

    // TODO: Uncomment this line and fix any errors this change causes.
    // $path = sprintf('property/' . strtoupper($propref) . "_" . $suffix);.
    $data = NeontabsIO::getInstance()->get($path);

    return $data;
  }

  /**
   * Register the instances for each of the fields (attach them to the custom cottage node type via the bundle system).
   */
  public static function registerCottageFieldDefinitionInstances($name, $cottage_fields, $custom_instances) {
    foreach ($cottage_fields as $field_key => $field_options) {

      $field_options_temp = field_info_field($field_key);

      if (!$field_options_temp) {
        $field_options = field_create_field($field_options);
      }

      $instance = array(
        'field_name' => $field_key,
        'entity_type' => 'node',
        'bundle' => $name,
        'description' => 'Cottage data field.',
        'label' => $field_options["label"],
        'widget' => array(
          'type' => 'textfield',
        ),
        'display' => array(
          'default' => array(
            'label' => 'above',
            'settings' => array(),
            'type' => 'default',
            'weight' => 0,
          ),
          'teaser' => array(
            'label' => 'above',
            'settings' => array(),
            'type' => 'default',
            'weight' => 0,
          ),
        ),
      );

      if (array_key_exists($field_key, $custom_instances)) {
        $instance = $custom_instances[$field_key];
      }

      field_create_instance($instance);
    }
  }

  /**
   * Check whether a node of the $name provided already exists.
   */
  public static function nodeTypeExists($name) {
    // Check to see if cottage_node type exists.
    if (in_array($name, node_type_get_names())) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Register a new cottage node entity when given a $name (machine name).
   */
  public static function registerCottageNodeTypeEntity($name) {
    $cottage_type_defintion_array = self::generateTypeDefinitionArray($name);

    $status = node_type_save($cottage_type_defintion_array);
    node_add_body_field($cottage_type_defintion_array);

    return $status;
  }

  /**
   * Generate a new type definition which can then be used to create a new node type using drupal's internal Node API.
   */
  private static function generateTypeDefinitionArray($name) {
    // Ascertain whether the node type currently is defined in the database.
    if (self::nodeTypeExists($name)) {
      return FALSE;
    }

    // Define new cottage node type.
    $nt2_node_type = array(
      'type' => $name,
      'name' => st('Basic Cottage Entry'),
      'base' => 'node_content',
      'description' => st("Defines a cottage entry node."),
      'custom' => 1,
      'modified' => 1,
    // TESTING.
      'locked' => 0,
    );

    // Apply Drupal defaults to initial type definition array.
    $nt2_node_type = node_type_set_defaults($nt2_node_type);

    return $nt2_node_type;
  }

}
