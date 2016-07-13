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
