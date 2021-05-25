<?php

/**
 * @file
 * General booking path admin files.
 */

/**
 * Implements hook_form().
 */
function nt2_io_admin_form() {
  $form = array();

  $form['nt2'] = array(
    '#type' => 'fieldset',
    '#title' => t('IO Settings'),
    '#collapsible' => FALSE,
    '#collapsed' => FALSE,
  );

  $form['nt2']['tabs_io_config_api_base_url'] = array(
    '#type' => 'textfield',
    '#title' => t('API URI'),
    '#required' => TRUE,
    '#default_value' => variable_get('tabs_io_config_api_base_url'),
  );

  $form['nt2']['tabs_io_config_api_api_key'] = array(
    '#type' => 'textfield',
    '#title' => t('API Key'),
    '#required' => TRUE,
    '#default_value' => variable_get('tabs_io_config_api_api_key'),
  );

  $form['nt2']['tabs_io_config_api_secret'] = array(
    '#type' => 'textfield',
    '#title' => t('API Secret'),
    '#required' => TRUE,
    '#default_value' => variable_get('tabs_io_config_api_secret'),
  );

  $form['nt2']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );

  return $form;
}

/**
 * Implements hook_form().
 */
function nt2_io_admin_form_submit($form, $form_state) {
  form_state_values_clean($form_state);

  $items = array();

  foreach ($form_state['values'] as $key => $value) {
    variable_set($key, $value);
    $items[] = $key;
  }

  drupal_set_message(
    t('Settings :items saved.', array(':items' => implode(', ', $items)))
  );
}
