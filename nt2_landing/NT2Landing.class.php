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
    // Define an array which is used to store each of the matches for numbers enclosed in square brackets.
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
   *
   * @param string $machineName
   *    The machine name of the nodes to load.
   *
   * @return array
   *    An array of every node fetched as a result of executing the EntityFieldQuery.
   */
  public static function loadLandingNodes($machineName) {
    $query = new EntityFieldQuery();

    // Setup the entity conditions: `match all nodes which have a bundle type of $machineName`.
    $query->entityCondition('entity_type', 'node')
      ->entityCondition('bundle', $machineName);

    // Execute the query.
    $result = $query->execute();

    // Return the nodes matched by the query.
    return $result;
  }

  /**
   * Helper function for sorting entry weights.
   *
   * @param array $a
   *    First entry to compare.
   * @param array $b
   *    Second entry to compare.
   */
  public static function weightArraysort($a, $b) {
    // If both $a and $b have weight attributes return true/false if $a['weight'] is smaller or larger than $b['weight'].
    if (isset($a['weight']) && isset($b['weight'])) {
      return $a['weight'] < $b['weight'] ? -1 : 1;
    }
    return 0;
  }

  /**
   * Method used to generate field definition instances.
   *
   * @param string $machineName
   *    Machine name of the node type the fields will be bundled with.
   * @param array $fields
   *    Should contain definitions for the fields we want to define.
   */
  public static function registerFieldDefinitionInstances($machineName, $fields) {
    foreach ($fields as $field_key => $field_options) {
      $field_options_temp = field_info_field($field_key);

      // If the field already exists delete it.
      if ($field_options_temp) {
        field_delete_field($field_key);
        field_purge_batch(5);
      }

      // Assign a new set of field options to the variable $field_options.
      $field_options = field_create_field($field_options);

      // Define a new instance array for each field.
      $instance = array(
        'field_name' => $field_key,
        'entity_type' => 'node',
        'bundle' => $machineName,
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

      // Register the instance with drupal.
      field_create_instance($instance);
    }
  }

  /**
   * Generate the entity type definition array.
   *
   * @param string $machineName
   *    Machine name of the entity you want to generate a definition array for.
   *
   * @return array
   *    The entity definition array.
   */
  public static function generateEntityDefinitionArray($machineName) {
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
      'locked' => 0,
    );

    // Apply Drupal defaults to initial type definition array.
    $nt2_landing_type = node_type_set_defaults($nt2_landing_type);

    return $nt2_landing_type;
  }

}
