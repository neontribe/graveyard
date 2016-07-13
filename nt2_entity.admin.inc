<?php

/**
 * @file
 * NT2 Entity Admin File
 */

/**
 * Implements hook_form().
 */
function nt2_entity_admin_form() {
  $form = array();

  $form['nt2_entity_propref'] = array(
    '#title' => t('Property reference'),
    '#type' => 'textfield',
    '#description' => t('If a prop ref is entered here it will trigger an import/refresh on the propref.'),
  );

  $form['reload_tax'] = array(
    '#type' => 'submit',
    '#value' => 'Reload Tags',
    '#submit' => array('nt2_entity_admin_form_reload_tax'),
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => 'Save'
  );

  return $form;
}

/**
 * Implements hook_form().
 */
function nt2_entity_admin_form_submit($form, &$form_state) {
  form_state_values_clean($form_state);

  $values = $form_state['values'];

  // Here you can process transitional values (e.g. refresh an entity.
  if (!empty($values['nt2_entity_propref'])) {
    $propref = $values['nt2_entity_propref'];
    // TODO: Call entity refresh here.

    // Now unset the value so it doesn't get saved below.
    unset($form_state['values']['nt2_entity_propref']);
  }

  foreach ($values as $key => $value) {
    variable_set($key, $value);
  }

  $message = t('Settings for :module have been saved.', array(':module' => __METHOD__));
  drupal_set_message($message);
}

/**
  * Secondary implementation of hook_form(), used to reload Taxonomy attributes from API.
  */
function nt2_entity_admin_form_reload_tax($form, &$form_state) {
  #Delete old data.
  taxonomy_vocabulary_delete(variable_get('nt2_entity_vocab_id'));

  #Recreate Table
  create_vocabulary();

  #Setup the vocabulary with terms fetched from the API root.
  $result = setup_vocabulary_from_api("/");

  #Display a message displaying the result of the vocabulary creation
  $message = t('Taxonomy has been refreshed (:module). :records Changed.', array(':module' => __METHOD__, ':records' => $result));
  drupal_set_message($message);
}