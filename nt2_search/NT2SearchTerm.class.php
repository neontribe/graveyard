<?php

/**
 * @file
 * Contains the abstract class NT2SearchTerm.
 * @todo Learn how to link to classes in documentation.
 */

/**
 * A renderable search term that can inject parameters based on user response.
 *
 * Implementations will inject inputs into a search form and extract from the
 * form response parameters that will be injected into the search query.
 */
abstract class NT2SearchTerm {
  /**
   * The prefix for terms to use when saving details with variable_set().
   *
   * @var string
   */
  const CONFIGURATION_PREFIX = NT2Search::CONFIGURATION_PREFIX . 'term_';

  /**
   * The codes from the search terms API this object may provide values for.
   *
   * @var string[]
   */
  protected $codes;

  /**
   * A human-readable description of the search term for the admin screen.
   *
   * @var string
   */
  protected $humanName;

  /**
   * Initialises with the codes that the search term provides coverage of.
   *
   * This is likely to be extended with additional parameters by extending
   * classes.
   *
   * @param string[] $codes
   *   The list of codes from the search terms API that the search term
   *   provides coverage for.
   * @param string $humanName
   *   A human-readable description of the search term, for the admin screen.
   */
  public function __construct($codes, $humanName) {
    $this->codes = $codes;
    $this->humanName = $humanName;

    // This allows a unique but consistent identifier to be constructed later.
    sort($this->codes);
  }

  /**
   * Injects this search term's input elements into the form.
   *
   * Any input elements that are required for the value of the search
   * term's parameters to be ascertained will be injected.
   *
   * @param array $form
   *   The form object being constructed, passed by reference to allow for
   *   modification by the function.
   */
  public abstract function injectInputs(&$form);

  /**
   * Converts the user's form response into injected search query parameters.
   *
   * Useful data from the form's response is extracted and reformatted as
   * necessary to be injected as a parameter for the search query.
   *
   * This function runs with the assumption that the search form has just been
   * submitted, as it uses filter_input().
   *
   * @param array $params
   *   The parameters that will be passed to the API when a search request is
   *   made, with the key as the parameter name and the value as the
   *   parameter's value. Passed by reference to allow for modification by the
   *   function.
   */
  public abstract function injectParams(&$params);

  /**
   * Injects any configurable options into the admin form.
   *
   * This usually includes parameters such as a label or maybe a minimum and
   * maximum for a ranged-input.
   *
   * @param array $form
   *   The form to inject the inputs into. Passed by reference, to allow for
   *   modification.
   */
  public abstract function injectConfigurationInputs(&$form);

  /**
   * Handles the configuration of the options after the admin form's submission.
   *
   * This ensures that changes are acted on and are persistant.
   *
   * @param array $form_state
   *   The parameters passed on the submission of the admin form.
   *
   * @todo Need this be passed by reference?
   */
  public abstract function handleConfigurationInputs(&$form_state);

  /**
   * Returns a list of codes that this search term provides coverage for.
   *
   * This is generally used to avoid conflicts between SearchTerms that both
   * try to claim the same code.
   *
   * @return string[]
   *   A list of the codes, as strings in an array.
   */
  public function getCodes() {
    return $this->codes;
  }

  /**
   * Returns a human, understandable name defined for the search term.
   *
   * This is usually hardcoded for core attributes or determined from label for
   * the attributes and is displayed on the admin page when configuring the
   * search term.
   *
   * @return string
   *   The understandable name for the search term.
   */
  public function getHumanName() {
    return $this->humanName;
  }

  /**
   * Returns an identifying name for the search term.
   *
   * This is based on the joining of the alphabetically-sorted codes this
   * search term has coverage of, which is unique enough for a form, as no
   * other form element will cover the same codes.
   *
   * If the search term must be identifiable in implementation too, one can
   * specify for the class name to be prepended.
   *
   * @param bool $implementationSpecific
   *   Whether or not the implementating class name should be prepended to make
   *   the name unique to this implementation of the codes.
   *
   * @return string
   *   A collection of code names joined with underscores, as a string.
   */
  public function getName($implementationSpecific = FALSE) {
    // $codes is pre-sorted in constructor for consistent results.
    $genericName = implode('_', $this->codes);

    if ($implementationSpecific) {
      return get_class($this) . '_' . $genericName;
    }

    return $genericName;
  }

  /**
   * Determines if this search term should be visible for the given search type.
   *
   * @param string $searchType
   *   (optional) The search type to check for. (e.g. "QUICK", "ADVANCED"). If
   *   not specified, the function will check if the search term is visible in
   *   at least one form.
   *
   * @todo Should search type be an enum or are basic strings fine?
   *
   * @return bool
   *   TRUE if the search term should be visible, otherwise FALSE.
   */
  public function isVisible($searchType = NULL) {
    // A list of the search types the term is enabled for.
    $enabledFor = variable_get($this->getVariableKey('visibility'), []);

    if (is_null($searchType)) {
      // Is the search term visible for at least one form?
      return count($enabledFor) > 0;
    }

    // Is the search type set to be visible for this search term?
    return in_array($searchType, $enabledFor);
  }

  /**
   * Sets whether this search term should be visible for the given search type.
   *
   * @param string $searchType
   *   The search type to determine visibility for. (e.g. "QUICK, "ADVANCED").
   *
   * @todo Should search type be an enum or are basic strings fine?
   */
  public function setVisible($searchType, $visible) {
    // A list of the search types the term is enabled for.
    $enabledFor = variable_get($this->getVariableKey('visibility'), []);

    // Is the search term already enabled for this search type?
    $alreadyEnabled = in_array($searchType, $enabledFor);

    if (!$visible && $alreadyEnabled) {
      // It is enabled but should not be: remove it.
      $key = array_search($searchType, $enabledFor);
      unset($enabledFor[$searchType]);
      $enabledFor = array_values($enabledFor);
    }
    elseif ($visible && !$alreadyEnabled) {
      // It is not enabled but should be: add it.
      $enabledFor[] = $searchType;
    }

    // Persist our changes.
    variable_set($this->getVariableKey('visibility'), $enabledFor);
  }

  /**
   * Sets the given configuration options to the provided values.
   *
   * @todo In the future, it might be nice to have an option to reset to
   * default values.
   *
   * @param array $configuration
   *   *   The configuration as an associative array.
   */
  protected function setConfiguration($configuration) {
    foreach ($configuration as $key => $defaultValue) {
      $variableKey = $this->getVariableKey('configuration', $key);
      variable_set($variableKey, $defaultValue);
    }
  }

  /**
   * Gets the configured options, with their set values else the default values.
   *
   * @param array $defaultConfiguration
   *   The default configuration as an associative array.
   *
   * @return array
   *   The complete configuration with specified values substituted in where
   *   possible.
   */
  protected function getConfiguration($defaultConfiguration) {
    $configuration = array();
    foreach ($defaultConfiguration as $key => $defaultValue) {
      $variableKey = $this->getVariableKey('configuration', $key);
      $configuration[$key] = variable_get($variableKey, $defaultValue);
    }
    return $configuration;
  }

  /**
   * Gets the key to use when storing configuration details.
   *
   * This is just a convenient private utility method to prevent code repetition
   * in the various functions of this class that handle persistance of
   * configuration.
   *
   * @todo Surely the current method of concatenation is inefficient?
   * @todo Document the inside of this function a little better, it's cryptic
   *
   * @param string $configurationType
   *   The type of configuration. (e.g. "configuration", "visibility")
   * @param string $suffix
   *   (optional) An additional term to suffix at the end. (e.g. a key when
   *   saving configuration).
   */
  private function getVariableKey($configurationType, $suffix = NULL) {
    $key = NT2SearchTerm::CONFIGURATION_PREFIX;
    $key = $key . $configurationType . '_' . $this->getName();

    if (is_null($suffix)) {
      return $key;
    }

    $key = $key . '_' . $suffix;
  }

}
