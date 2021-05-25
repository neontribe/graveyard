<?php

/**
 * @file
 * Contains the class CottageVocabManager.
 */

/**
 * CottageVocabManager manages the creation, updating and deleting of vocabularies.
 *
 * Provides a plethora of helpful methods so as to achieve this goal.
 */
class CottageVocabManager {

  /**
   * Check whether a node of the $name provided already exists.
   *
   * @param string $machineName
   *   The machine name of the vocabulary to check the existence of.
   *
   * @return object
   *   The vocabulary, if one is found. False if one isn't.
   */
  public static function vocabTypeExists($machineName) {
    // Check to see if the vocabulary already exists.
    $vocabulary = taxonomy_vocabulary_machine_name_load($machineName);
    // If it exists then exit the function as there is nothing more to do.
    if ($vocabulary) {
      return $vocabulary;
    }

    return FALSE;
  }

  /**
   * Create a vocabulary used to store cottage tag entries.
   *
   * @param string $machineName
   *   The machine name of the vocabulary to be created.
   * @param object $vocabDefinition
   *   The object definition of the vocabulary to be created.
   *
   * @return object
   *   The finalised vocabulary definition.
   */
  public static function createCottageTagVocabulary($machineName, $vocabDefinition, $vocabFields) {
    // If vocabulary already exists.
    if (self::vocabTypeExists($machineName)) {
      return TRUE;
    }

    // Setup field definitions and attach to the $machineName type bundle.
    self::registerVocabularyFieldDefinitionInstances($machineName, $vocabFields);

    // Save the defined vocabulary in the database.
    taxonomy_vocabulary_save($vocabDefinition);

    // Set the return value to the taxonomy generated and subsequently stored in the DB.
    $returnObject = taxonomy_vocabulary_machine_name_load($machineName);

    // If the vocabulary doesn't exist after we've saved it an error has occured so return FALSE.
    if (!isset($returnObject)) {
      return FALSE;
    }

    return $returnObject;
  }

  /**
   * Register the instances for each of the fields and attach them to the taxonomy_term data type.
   *
   * @param string $machineName
   *   The machine name of the vocabulary to define the definition instances of.
   * @param array $vocabFields
   *   The definition of the fields to be instantiated and bound to the vocab by this function.
   */
  public static function registerVocabularyFieldDefinitionInstances($machineName, $vocabFields) {
    foreach ($vocabFields as $vocabKey => $fieldOptions) {
      // If the field already exists skip this iteration.
      if (field_info_field($vocabKey)) {
        continue;
      }

      // Create the field definition.
      field_create_field($fieldOptions);

      // Create an array in order to describe the instance of the field we wish to create.
      $instance = array(
      'field_name' => $vocabKey,
      'entity_type' => 'taxonomy_term',
      'bundle' => $machineName,
      'description' => 'Tag data field.',
      'label' => $vocabKey,
      'widget' => array(
        'type' => 'textfield',
      )
     );

      // Invoke the field_create_instance function with the description of the instance created prior.
      field_create_instance($instance);
    }
  }

  /**
   * Function used to add a term (tag in this context) to the cottage vocabulary.
   *
   * @param string $machineName
   *   The machine name of the vocabulary to which a term should be added.
   * @param string $termName
   *   The name of the term to be added.
   * @param array $termData
   *   An array of data to be used to defined the term (defaults to NULL if )
   * @param object $parent
   *   The parent object for this current term (defaults to NULL if the term has no parent).
   */
  public static function addTermToVocabulary($machineName, $termName, $termData = NULL, $parent = NULL) {

    $vocabInfo = self::vocabTypeExists($machineName);

    if (!$vocabInfo) {
      return;
    }
    // If the term already exists update it then return.

    // TODO: This is messy, should be refactored.

    if (self::taxonomyTermExists($machineName, $termName)) {

      $terms = self::getTermsFromName($machineName, $termName);

      if (isset($terms) && is_array($terms)) {

        if (is_array($termData)) {
          // For each term stored in the DB with the same name check if values differ between the values to be modified and update accordingly.
          foreach ($terms as $key => $value) {
            $modified = FALSE;

            foreach ($termData as $dataKey => $dataValue) {
              $accessedArray = $value->$dataKey;

              // Check if values differ (currently only checks for arrays, as arrays need to be handled differently).
              if (is_array($dataValue) && is_array($accessedArray)) {
                if ($dataValue["und"][0]["value"] != $accessedArray["und"][0]["value"]) {
                  $modified = TRUE;
                  $value->$dataKey = $dataValue;
                }
              }
              else {
                if ($dataValue != $accessedArray) {
                  $modified = TRUE;
                  $value->$dataKey = $dataValue;
                }
              }
            }

            if ($modified == TRUE) {
              // dpm("Save operation!");.
              taxonomy_term_save($value);
            }
          }
        }
      }
    }
    else {

      // If the vocabulary doesn't exist create new instance of term.
      if ($vocabInfo) {
        // Set defaults.
        $newTerm = (object) array(
          'name' => $termName,
          'description' => 'Default description.',
          'format' => filter_default_format(),
          'vid' => $vocabInfo->vid,
          'parent' => $parent,
        );

        if (is_array($termData)) {
          foreach ($termData as $dataKey => $dataValue) {
            $newTerm->$dataKey = $dataValue;
          }
        }

        taxonomy_term_save($newTerm);
      }
    }
  }

  /**
   * Create a vocabulary from a provided list of attributes from the API.
   *
   * @param string $machineName
   *   The machine name of the vocabulary to create.
   * @param array $apiAttribs
   *   The attributes which define the vocabulary as provided by the API.
   * @param array $vocabDefinitions
   *   List of the field definitions for the vocabulary.
   * @param string $nestedParentId
   *   The ID of the parent.
   * @param string $nestedItemId
   *   The ID of the nested item.
   *
   * @return int
   *   Number of attributes initialised.
   */
  public static function createVocabularyFromAttribList($machineName, $apiAttribs, $vocabDefinitions, $nestedParentId = "group", $nestedItemId = "label") {
    foreach ($apiAttribs as $attrib) {

      // Build the data array for the current attrib.
      $fieldData = array();
      $entryNames = array_keys($vocabDefinitions);

      foreach ($entryNames as $key) {
        $fieldData[$key] = array(
          'und' => array(
            0 => array(
              'value' => $attrib[$vocabDefinitions[$key]['data_key_name']],
            ),
          ),
        );
      }

      // Set the group variable.
      $currentGroup = $attrib[$nestedParentId];
      $currentTerm = $attrib[$nestedItemId];

      self::addTermToVocabulary($machineName, $currentGroup, $fieldData);

      // Apply nesting to the vocabulary.
      if (is_array($currentTerm)) {

        foreach ($currentTerm as $childValue) {

          $fieldData = array();

          $curChildTerm = $childValue[$nestedParentId];

          foreach ($entryNames as $key) {

            $fieldData[$key] = array(
              'und' => array(
                0 => array(
                  'value' => $childValue[$vocabDefinitions[$key]["data_key_name"]],
                ),
              ),
            );

          };
          self::addTermToVocabulary($machineName, $curChildTerm, $fieldData, self::getTermFromName($machineName, $currentGroup));
        }
      }
      else {
        self::addTermToVocabulary($machineName, $currentTerm, $fieldData, self::getTermFromName($machineName, $currentGroup));
      }
    }

    return count($apiAttribs);
  }

  /**
   * Ascertain whether a specific taxonomy_term already exists in the database.
   *
   * @param string $machineName
   *   The machine name of the taxonomy used to check the existence of the term $name.
   * @param string $name
   *   The name of the term to check the existence of.
   *
   * @return bool
   *   A boolean indicating whether the term exists in the taxonomy or not.
   */
  public static function taxonomyTermExists($machineName, $name) {
    $term = self::getTermFromName($machineName, $name);
    if ($term != NULL) {
      return TRUE;
    }

    return FALSE;
  }

  /*
   * Grab the TID of a specific term from the database and return the first of these.
   */

  /**
   * TODO: Look into merging these two functions (avoid repetition)
   */
  public static function getTermFromName($machineName, $name) {
    $tid = taxonomy_get_term_by_name($name, $machineName);

    if (count($tid) == 0) {
      return NULL;
    }
    return array_shift($tid)->tid;
  }

  /**
   * Fetches an array of terms from the taxonomy based on the provided $name.
   */
  public static function getTermsFromName($machineName, $name) {
    $tid = taxonomy_get_term_by_name($name, $machineName);

    if (count($tid) == 0) {
      return NULL;
    }

    return $tid;
  }

}
