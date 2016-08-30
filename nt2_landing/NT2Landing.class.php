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
   */
  public static function registerEntityType($name) {
    $landing_type_definition_array = self::generateEntityDefinitionArray($name);
    $status = node_type_save($landing_type_definition_array);

    node_add_body_field($landing_type_definition_array);

    return $status;
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
    // if (in_array($name, node_type_get_names())) {
    // return FALSE;
    // }.

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
      'has_title' => 0,
    );

    // Apply Drupal defaults to initial type definition array.
    $nt2_landing_type = node_type_set_defaults($nt2_landing_type);

    return $nt2_landing_type;
  }

}
