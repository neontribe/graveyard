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
  $searchTerms = NT2Search::getSearchTerms();
  $form = array();

  // @todo Separate attributes and core terms, if not done so  by order suitably
  // @todo Make this a table
  foreach ($searchTerms as $searchTerm) {
    foreach (NT2Search::SEARCH_TYPES as $searchType) {
      $form[$searchTerm->getName(TRUE) . '_visibility_' . $searchType] = array(
        '#type' => 'checkbox',
        '#default_value' => $searchTerm->isVisible($searchType),
        '#title' => $searchTerm->getHumanName() . ': ' . $searchType,
      );
    }
  }

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => 'Save Changes',
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

  $values = $form_state['values'];

  $searchTerms = NT2Search::getSearchTerms();

  foreach ($searchTerms as $searchTerm) {
    foreach (NT2Search::SEARCH_TYPES as $searchType) {
      $key = $searchTerm->getName(TRUE) . '_visibility_' . $searchType;
      if (in_array($key, $values) && $values[$key] == '1') {
        $searchTerm->setVisible($searchType, TRUE);
        continue;
      }
      $searchTerm->setVisible($searchType, FALSE);
    }
  }

}
