<?php

/**
 * @file
 * Contains the class CottageNodeManager.
 */

/**
 * CottageNodeManager manages the creation, updating and deleting of cottage node entries.
 *
 * Provides a plethora of helpful methods so as to achieve this goal.
 */
class CottageNodeManager {

  /**
   * Creates a new property reference node when given returned data from the API as a parameter: $data.
   */
  public static function createNode($propRef, $typeMachineName, $data = NULL) {
    $result = self::loadNode($propRef);

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
      $node->type = $typeMachineName;

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
   * @param string $propRef
   *   Property reference string.
   * @param string $nodeMachineName
   *   Machine name for the node type.
   *
   * @return Object
   *   The loaded object or NULL.
   */
  public static function loadNode($propRef, $nodeMachineName = 'cottage_entity') {
    // Compose a new entity query which will ascertain whether node entries exist with the same reference as provided in $ref.
    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', 'node')
      ->entityCondition('bundle', $nodeMachineName)
      ->fieldCondition('cottage_reference', 'value', $propRef, '=');

    // Assign the value of the result of executing the query to the variable $result.
    $result = $query->execute();

    if (isset($result['node'])) {
      $refs = array_keys($result['node']);
      $props = entity_load('node', $refs);

      if (count($props) == 1) {
        return array_pop($props);
      }
      elseif (count($props) == 0) {
        watchdog(__METHOD__, 'Property not found :propRef', array(':propRef' => $propRef));
      }
      else {
        watchdog(__METHOD__, ':count properties not found :propRef', array(':count' => count($props), ':propRef' => $propRef));
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
   * Takes an array as input and returns a string of newline separated values. The keys to be retained are specified in the `$valuesToKeep` array.
   */
  private static function parsePropertyValueArray($arrayOfValues, $valuesToKeep) {
    // TODO: Use an entity as a wrapper for these value arrays such as images instead of a newline separated list.
    $output = array();
    foreach ($arrayOfValues as $key => $value) {
      $valueCarry = array();
      foreach ($valuesToKeep as $valueKeep) {
        if (empty($value[$valueKeep])) {
          $value[$valueKeep] = "no_" . $valueKeep;
        }

        array_push($valueCarry, $value[$valueKeep]);
      }
      $output[$key] = array('value' => implode("\n", $valueCarry));
    }

    return $output;
  }

  /**
   * Takes an array of data (the data returned by the API for a property request) as input and returns an array which is in the correct format to be saved as an instance of the custom cottage node_type.
   */
  public static function parseApiPropertyReturnData($data, $machineName = array("tag" => "", "location" => "")) {

    // Find which tags to retain.
    $tagKeysToKeep = array();
    foreach ($data["attributes"] as $key => $value) {
      if ($value) {
        $tagKeysToKeep[] = array(
          'tid' => CottageVocabManager::getTermFromName($machineName["tag"], $key),
        );
      }
    };

    $locationKey[] = array(
      'tid' => CottageVocabManager::getTermFromName($machineName["location"], $data["location"]["code"]),
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

    $returnData = array(
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

    return $returnData;
  }

  /**
   * This function queries the API for a specific property reference and returns an array of the data found.
   */
  public static function fetchPropertyFromApi($propRef, $suffix) {

    $path = sprintf('property/' . strtoupper($propRef) . $suffix);

    // TODO: Uncomment this line and fix any errors this change causes.
    // $path = sprintf('property/' . strtoupper($propRef) . "_" . $suffix);.
    $data = NeontabsIO::getInstance()->get($path);

    return $data;
  }

  /**
   * Register the instances for each of the fields (attach them to the custom cottage node type via the bundle system).
   */
  public static function registerCottageFieldDefinitionInstances($name, $cottageFields, $customInstances) {
    foreach ($cottageFields as $fieldKey => $fieldOptions) {
      if (field_info_field($fieldKey)) {
        continue;
      }

      $fieldOptions = field_create_field($fieldOptions);

      $instance = array(
      'field_name' => $fieldKey,
      'entity_type' => 'node',
      'bundle' => $name,
      'description' => 'Cottage data field.',
      'label' => $fieldKey,
      'widget' => array(
        'type' => 'textfield',
      )
       );

      if (array_key_exists($fieldKey, $customInstances)) {
        $instance = $customInstances[$fieldKey];
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
    $cottageTypeDefinitionArray = self::generateTypeDefinitionArray($name);

    $status = node_type_save($cottageTypeDefinitionArray);
    node_add_body_field($cottageTypeDefinitionArray);

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
