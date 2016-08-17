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
 * @todo Add per-search-term settings.
 * @todo Fix table rendering.
 */
function nt2_search_admin_form() {
  $search_terms = NT2Search::getSearchTerms();
  $form = array();

  // @todo Separate attributes and core terms, if not done so by order suitably

  // The table containing visibility settings.
  // Tree allows us to nest form elements.
  // The custom theme allows for rendering as a table to occur.
  $visibility_table = array(
    '#tree' => 'TRUE',
    '#theme' => 'nt2_search_admin_form_table',
  );

  // Make a row for every search term.
  foreach ($search_terms as $search_term) {
    $row = array();

    // Column 1: The search term's human name.
    $row['humanName'] = array(
      '#markup' => $search_term->getHumanName(),
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
 * Handles submission of the admin form for search settings.
 */
function nt2_search_admin_form_submit($form, &$form_state) {
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
 * Implements hook_theme().
 *
 * @todo This doesn't seem to work.
 */
function nt2_search_theme() {
  $theme['nt2_search_admin_form_table'] = array(
    'render element' => 'element',
    'file' => 'nt2_search.admin.inc',
  );
  return $theme;
}

/**
 * Theme the table element.
 *
 * @todo This doesn't seem to work.
 * @todo More accurate doc comment please.
 */
function theme_nt2_search_admin_form_table($variables) {
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
