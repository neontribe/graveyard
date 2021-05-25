<?php

/**
 * @file
 * Implentation of the `ntlp` drush command.
 */

/**
 * Implements hook_drush_command().
 */
function nt2_node_type_drush_command() {

  $items['drush-load-properties'] = array(
    'description' => 'Load list of properties from API into local cache.',
    'aliases' => array('ntlp'),
    'arguments' => array(
      'attrs' => 'Comma separated list of property attributes to load.',
    ),
    'options' => array(
      // 'attrs' => '',.
    ),
    'examples' => array(
      'drush ntlp' => '',
      'drush ntlp <A241, L249,...>' => 'Comma separated list of property attributes to load.',
    ),
  );

  return $items;
}

/**
 * Callback for the drush-load-properties command.
 */
function drush_nt2_node_type_drush_load_properties($attrs = FALSE) {
  module_load_include("inc", "nt2_node_type", "nt2_node_type.admin");

  var_dump("Loading properties");

  if ($attrs) {
    $attrs = explode(",", $attrs);
    // Load the list of properties as exploded from the supplied CSV data as the `attr` paramater.
    nt2_entity_admin_load_attr_list($attrs);
  }
  else {
    // Load all properties, telling the function it is being invoked from the backend.
    nt2_entity_admin_load_all_prop(NULL, NULL, TRUE);
  }

  var_dump("Properties loaded.");

}
