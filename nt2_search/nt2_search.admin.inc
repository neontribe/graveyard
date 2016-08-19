<?php

/**
 * @file
 * The admin functions for nt2 search.
 */

/**
 * Implements hook form().
 *
 * Provides the admin form for search term visibility settings.
 *
 * @todo Add per-search-term settings.
 */
function nt2_search_visibility_form() {
  $search_terms = NT2Search::getSearchTerms();
  $form = array();

  $description = array(
    '<p>Control which search terms are visible for what types of search.</p>',
    '<p>Different search terms have different API code coverage. ',
    'For this reason, search terms that both use a certain code cannot be both selected.</p>',
  );

  $form['description'] = array(
    '#markup' => implode($description),
  );

  // @todo Separate attributes and core terms, if not done so by order suitably

  // The table containing visibility settings.
  // Tree allows us to nest form elements.
  // The custom theme allows for rendering as a table to occur.
  $visibility_table = array(
    '#tree' => 'TRUE',
    '#theme' => 'nt2_search_admin_visibility_form_table',
  );

  // Make a row for every search term.
  foreach ($search_terms as $search_term) {
    $row = array();

    // Column 1: The search term's human name.
    $row['humanName'] = array(
      '#markup' => $search_term->getHumanName(),
    );

    $row['codes'] = array(
      '#markup' => implode($search_term->getCodes(), ', '),
    );

    // Columns 2-n: A checkbox for each search type to indicate visibility.
    foreach (NT2Search::SEARCH_TYPES as $search_type) {
      // We prepend 'type' to prevent any clashes.
      $row['type' . $search_type] = array(
        '#type' => 'checkbox',
        '#default_value' => $search_term->isVisible($search_type),
      );
    }

    // Add our row to the table.
    $visibility_table[$search_term->getName(TRUE)] = $row;
  }

  // Add the table to the form.
  $form['visibilityTable'] = $visibility_table;

  // Add a save button.
  $form_actions = array(
    '#type' => 'actions',
  );
  $form_actions['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save Changes'),
  );
  $form['actions'] = $form_actions;

  return $form;
}

/**
 * Implements hook form_submit().
 *
 * Handles submission of the admin form for visibility settings.
 *
 * @todo Form should error if a code is claimed by multiple search terms.
 */
function nt2_search_visibility_form_submit($form, &$form_state) {
  form_state_values_clean($form_state);
  $values = $form_state['values'];

  $search_terms = NT2Search::getSearchTerms();

  // Fields describing visibility.
  $visibility_table = $values['visibilityTable'];
  foreach ($search_terms as $search_term) {
    $term_name = $search_term->getName(TRUE);

    if (!array_key_exists($term_name, $visibility_table)) {
      // The search term is not present, ignore.
      continue;
    }

    // The checkbox values for each search type.
    $visibility_for = $visibility_table[$term_name];
    foreach (NT2Search::SEARCH_TYPES as $search_type) {
      // 'type' was prepended in form generation earlier to prevent clashes.
      $type_field_name = 'type' . $search_type;
      if (!array_key_exists($type_field_name, $visibility_for)) {
        // The search type is not present, ignore.
        continue;
      }
      // Is the checkbox ticked, to indicate visibility?
      $visible = $visibility_for[$type_field_name] == '1';

      // Persist the user's wishes.
      $search_term->setVisible($search_type, $visible);
    }
  }
}

/**
 * Implements hook form().
 *
 * Injects configuration options for every enabled search term.
 */
function nt2_search_configuration_form() {
  $search_terms = NT2Search::getSearchTerms();
  $form = array();

  $description = array(
    '<p>Configure individual search terms.</p>',
    '<p><strong>NOTE: </strong>Only search terms enabled for at least one search type will be visible.</p>',
  );

  $form['description'] = array(
    '#markup' => implode($description),
  );

  // Make a row for every search term.
  foreach ($search_terms as $search_term) {
    if (!$search_term->isVisible()) {
      // Don't show configurations for unused/disabled search terms.
      continue;
    }

    // Make a fieldset for each search term.
    $term_fieldset = array(
      '#tree' => 'TRUE',
      '#type' => 'fieldset',
      '#collapsible' => 'TRUE',
      '#collapsed' => 'TRUE',
      '#title' => $search_term->getHumanName(),
    );

    // Add the search term's configuration inputs to the form.
    $search_term->injectConfigurationInputs($term_fieldset);

    // Add the sub-form to the main form.
    $form[$search_term->getName(TRUE)] = $term_fieldset;
  }

  // Add a save button.
  $form_actions = array(
    '#type' => 'actions',
  );
  $form_actions['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save Changes'),
  );
  $form['actions'] = $form_actions;

  return $form;
}

/**
 * Implements hook form_submit().
 *
 * Handles submission of the search term configuration form, passing the
 * returned values to each search term to handle.
 */
function nt2_search_configuration_form_submit($form, &$form_state) {
  form_state_values_clean($form_state);
  $values = $form_state['values'];

  $search_terms = NT2Search::getSearchTerms();

  foreach ($search_terms as $search_term) {
    $term_name = $search_term->getName(TRUE);

    if (!array_key_exists($term_name, $values)) {
      // The search term is not present in the submitted values, ignore.
      continue;
    }

    $term_form = $form[$term_name];
    $term_values = $values[$term_name];

    // Let the search term handle the form response.
    $search_term->handleConfigurationInputs($term_form, $term_values);
  }
}

/**
 * The theming for the visibility table: used above.
 *
 * See https://www.drupal.org/node/1876710.
 */
function theme_nt2_search_admin_visibility_form_table($variables) {
  $element = $variables['element'];

  $rows = array();
  foreach (element_children($element) as $name) {
    $row = array(
      'data' => array(),
    );

    foreach (element_children($element[$name]) as $field_name) {
      $row['data'][] = drupal_render($element[$name][$field_name]);
    }

    $rows[] = $row;
  }

  $header = array(
    t('Search Term'),
    t('API Codes'),
  );

  foreach (NT2Search::SEARCH_TYPES as $search_type) {
    $header[] = t($search_type);
  };

  $output = theme('table', array(
    'header' => $header,
    'rows' => $rows,
  ));
  $output .= drupal_render_children($element);
  return $output;
}
