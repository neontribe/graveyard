<?php

/**
 * @file
 * The admin functions for nt2 search.
 */

/**
 * Implements hook form().
 *
 * Provides the admin form for search settings.
 *
 * @todo Implement.
 */
function nt2_search_admin_form() {
  $form = array();

  $form['foo'] = array(
    '#title' => 'bar',
    '#type' => 'textfield',
  );

  return $form;
}

/**
 * Implements hook form_submit().
 *
 * Handles submission of the admin form for search settings.
 *
 * @todo Implement.
 */
function nt2_search_admin_form_submit($form, &$form_state) {
  form_state_values_clean($form_state);

  foreach ($form_state['values'] as $key => $value) {
    variable_set($key, $value);
  }
}
