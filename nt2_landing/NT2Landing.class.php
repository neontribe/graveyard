<?php

/**
 * @file
 * Contains NT2Landing class.
 */

/**
 * Class for NT2Landing methods.
 */
class NT2Landing {

  /**
   * Method used to register the entity type.
   *
   * @param string $machineName
   *    The machine name used to define the new entity type.
   */
  public static function registerEntityType($machineName) {

    // Create the definition for the new entity type.
    $landing_type_definition_array = self::generateEntityDefinitionArray($machineName);

    // Register the new node type with Drupal.
    $status = node_type_save($landing_type_definition_array);

    // Add the default drupal body field to the node type we just created.
    node_add_body_field($landing_type_definition_array);

    // Return the status of the new node type creation.
    return $status;
  }

  /**
   * Function to strip ID from landing page name.
   *
   * Strings should be provided in the format: "Landing Page [1234]".
   *
   * Landing page ID should be enclosed in square brackets.
   *
   * @param string $search
   *    The string to search for the landing page ID.
   *
   * @return int
   *    The node ID stripped from the search string if an ID can't be found -1 is returned instead.
   */
  public static function stripIdFromSearchString($search) {
    $matches = array();

    // Check for the presence of two square brackets with one or more numbers between 0 and 9 enclosed between them.
    $matched = preg_match("/\[[0-9]+\]/", $search, $matches);

    // Initialise the $node_id variable with the value -1 (assume that the search has already failed).
    $node_id = -1;

    // If the prior regex found a match and the match was deposited in the $matches array.
    if ($matched && count($matches) > 0) {

      // Remove the two square brackets from the matched string using the following regex replace.
      $node_id = preg_replace('/((\[)|(\]))/', '', $matches[0]);
    }

    // Return the matched ID.
    return $node_id;
  }

  /**
   * Method used to load every landing page nid.
   */
  public static function loadLandingNodes($name) {
    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', 'node')
      ->entityCondition('bundle', $name);

    $result = $query->execute();

    return $result;
  }

  /**
   * Method used to generate field definition instances.
   */
  public static function registerFieldDefinitionInstances($name, $fields) {
    foreach ($fields as $field_key => $field_options) {
      $field_options_temp = field_info_field($field_key);

      // If the field already exists delete it.
      if ($field_options_temp) {
        field_delete_field($field_key);
        field_purge_batch(5);
      }

      $field_options = field_create_field($field_options);

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

      field_create_instance($instance);
    }
  }

  /**
   * Generate the entity type definition array.
   */
  public static function generateEntityDefinitionArray($name) {
    // Return from the function if a node type already exists.
    if (in_array($name, node_type_get_names())) {
      return FALSE;
    }

    // Define new cottage node type.
    $nt2_landing_type = array(
      'type' => $name,
      'name' => st('Landing Page Entry.'),
      'base' => 'node_content',
      'description' => st("Defines a landing page node."),
      'custom' => 1,
      'modified' => 1,
    // TESTING.
      'locked' => 0,
    );

    // Apply Drupal defaults to initial type definition array.
    $nt2_landing_type = node_type_set_defaults($nt2_landing_type);

    return $nt2_landing_type;
  }

}
