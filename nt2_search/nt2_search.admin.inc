<?php

/**
 * @file
 * The admin functions for nt2 search.
 *
 * @todo Not proud of this file:
 * - Massive functions
 * - Cryptic code
 * - Poorly documented
 * - Barely-passable dependency checking
 *
 * This could do with a cleanup, preferably involving splitting up some of the
 * more scary functions.
 */

use Drupal\nt2_search\uk\co\neontabs\SearchUI;
use Drupal\nt2_search\uk\co\neontabs\SearchTabs;

/**
 * Implements hook form().
 *
 * Provides the admin form for search term visibility settings.
 */
function nt2_search_visibility_form() {
  $search_terms = SearchTabs::getTerms();
  $form = array();

  $description = array(
    '<p>Control which search terms are visible for what types of search.</p>',
    '<p>Different search terms have different API code coverage. ',
    'For this reason, search terms that both use a certain code cannot be both selected.</p>',
    '<p>Other search terms require codes to be covered by another search term to be enabled.</p>',
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
      '#markup' => $search_term->getName(),
    );

    // Column 2: The codes the search term covers.
    $row['codes'] = array(
      '#markup' => implode($search_term->getCodes(), ', '),
    );

    // Column 3: The codes the search term needs covered.
    $row['dependencyCodes'] = array(
      '#markup' => implode($search_term->getDependencyCodes(), ', '),
    );

    // Columns 4->n: A checkbox for each search type to indicate visibility.
    foreach (SearchUI::SEARCH_TYPES as $search_type) {
      // We prepend 'type' to prevent any clashes.
      $row['type' . $search_type] = array(
        '#type' => 'checkbox',
        '#default_value' => $search_term->isVisible($search_type),
      );
    }

    // Add our row to the table.
    $visibility_table[$search_term->getSpecificId()] = $row;
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
 * This consists of finding out which Terms the user wants visible for each
 * search type, checking that their codes don't clash and then ensuring that
 * their dependencies are satisfied, before making the changes persistent.
 */
function nt2_search_visibility_form_submit($form, &$form_state) {
  form_state_values_clean($form_state);
  $values = $form_state['values'];

  $search_terms = SearchTabs::getTerms();

  // An attempt has been made to make a Term visible that uses these codes.
  $claimed_codes = array();

  // A term has been successfully made visible that uses these codes.
  $enabled_codes = array();

  // These terms still have dependencies to satisfy.
  $owed_dependency = array();

  // Intialise an empty array for each search type.
  foreach (SearchUI::SEARCH_TYPES as $search_type) {
    $claimed_codes[$search_type] = array();
    $enabled_codes[$search_type] = array();
    $owed_dependency[$search_type] = array();
  }

  $visibility_table = $values['visibilityTable'];

  // Determine visibility for each search term.
  foreach ($search_terms as $search_term) {
    $term_name = $search_term->getSpecificId();

    if (!array_key_exists($term_name, $visibility_table)) {
      // The search term is not present, ignore.
      continue;
    }

    // The checkbox values for each search type.
    $visibility_for = $visibility_table[$term_name];

    // Check for each search type if the user wishes the Term to be visible for it.
    foreach (SearchUI::SEARCH_TYPES as $search_type) {
      // 'type' was prepended in form generation earlier to prevent clashes.
      $type_field_name = 'type' . $search_type;
      if (!array_key_exists($type_field_name, $visibility_for)) {
        // The search type is not present, ignore.
        continue;
      }

      // Is the checkbox ticked, to indicate visibility?
      $visible = $visibility_for[$type_field_name] === 1;

      if ($visible) {
        // PHP doesn't have labelled for-loops :(.
        $can_set = TRUE;

        // Check that any of the term's codes are not already in use.
        foreach ($search_term->getCodes() as $code) {
          if (array_key_exists($code, $claimed_codes[$search_type])) {
            $can_set = FALSE;

            $us = $search_term->getName();
            $thief = $claimed_codes[$search_type][$code]->getName();
            $msg = "$us cannot be made visible for $search_type, as $thief already uses $code.";
            drupal_set_message($msg, 'error');
          }
        }

        // One of them was, we can't set this Search Term to visible.
        if (!$can_set) {
          continue;
        }

        // Claim any codes the search term uses.
        foreach ($search_term->getCodes() as $code) {
          $claimed_codes[$search_type][$code] = $search_term;
        }

        if (count($search_term->getDependencyCodes()) > 0) {
          // This search term can be enabled if its dependencies are satisfied.
          // Check if they are later.
          $owed_dependency[$search_type][] = $search_term;
        }
        else {
          // No dependencies, we can enable right away.
          $search_term->setVisible($search_type, $visible);

          // Store where dependencies are fulfilled.
          foreach ($search_term->getCodes() as $code) {
            $enabled_codes[$search_type][$code] = $search_term;
          }
        }
      }
      else {
        // Persist the user's wishes for it not be visible.
        $search_term->setVisible($search_type, $visible);
      }
    }
  }

  foreach (SearchUI::SEARCH_TYPES as $search_type) {
    $fulfilled_dependency = TRUE;

    // Don't stop trying until we're no longer fulfilling dependencies.
    while ($fulfilled_dependency) {
      $fulfilled_dependency = FALSE;

      // Make a copy of the array, so we can remove stuff as we step through.
      $owed_dependency_type = $owed_dependency[$search_type];
      foreach ($owed_dependency_type as $search_term) {
        $all_dependencies_present = TRUE;
        foreach ($search_term->getDependencyCodes() as $dependency_code) {
          if (!array_key_exists($dependency_code, $enabled_codes[$search_type])) {
            // This dependency is not fulfilled.
            $all_dependencies_present = FALSE;
          }
        }

        if ($all_dependencies_present) {
          // Enable.
          $search_term->setVisible($search_type, $visible);

          // Store what dependencies are fulfilled by this enabling.
          foreach ($search_term->getCodes() as $code) {
            $enabled_codes[$search_type][$code] = $search_term;
          }

          $fulfilled_dependency = TRUE;

          // We've found its dependencies, we don't need to check anymore.
          $owed_index = array_search($owed_dependency[$search_type]);
          unset($owed_dependency[$search_type][$owed_index]);
          $owed_dependency[$search_type] = array_values($owed_dependency[$search_type]);
        }
      }
    }

    // Notify the user of any unfulfilled dependencies.
    foreach ($owed_dependency[$search_type] as $search_term) {
      $missing_codes = array();

      foreach ($search_term->getDependencyCodes() as $dependency_code) {
        if (!array_key_exists($dependency_code, $enabled_codes[$search_type])) {
          $missing_codes[] = $dependency_code;
        }
      }

      $us = $search_term->getName();
      $needed = implode(', ', $missing_codes);
      $msg = "\"$us\" cannot be made visible for $search_type Search, as these codes are not covered: $needed.";
      drupal_set_message($msg, 'error');
    }
  }
}

/**
 * Implements hook form().
 *
 * Injects configuration options for every enabled search term.
 */
function nt2_search_configuration_form() {
  $search_terms = SearchTabs::getTerms();
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
      '#title' => $search_term->getName(),
    );

    // Add the search term's configuration inputs to the form.
    $term_fieldset = array_merge($term_fieldset, $search_term->renderConfigInputs());

    // Add the sub-form to the main form.
    $form[$search_term->getSpecificId()] = $term_fieldset;
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

  $search_terms = SearchTabs::getTerms();

  foreach ($search_terms as $search_term) {
    $term_name = $search_term->getSpecificId();

    if (!array_key_exists($term_name, $values)) {
      // The search term is not present in the submitted values, ignore.
      continue;
    }

    // The search term's section.
    $term_values = $values[$term_name];

    // Let the search term handle the form response.
    $config = $search_term->buildConfig($term_values);

    // Persist their changes.
    $search_term->setConfig($config);
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
    t('Needed Codes'),
  );

  foreach (SearchUI::SEARCH_TYPES as $search_type) {
    $header[] = t($search_type);
  };

  $output = theme('table', array(
    'header' => $header,
    'rows' => $rows,
  ));
  $output .= drupal_render_children($element);
  return $output;
}
