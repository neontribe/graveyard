<?php

namespace Drupal\nt2_search\uk\co\neontabs\term;

/**
 * Describes a Term that will produce a search filter from form inputs.
 */
abstract class Term {

  /**
   * Renders the Term in a form to be shown as part of a Search Form.
   *
   * The function should create a form render array as one would with Drupal's
   * Form API. This child form should be able to be included in the parent
   * search form, which has the '#tree' property enabled to facilitate this.
   *
   * @return array
   *   Returns a form render array, which will be included within the search
   *   form render array.
   */
  public abstract function renderInputs();

  /**
   * Constructs filter parameters for each code, where applicable.
   *
   * This function interprets the user-response to the form, constructing
   * filters from it that will be submitted to Tabs API. Usually, a filter may
   * be generated for each code that the Term covers and - while it is not
   * mandatory to create a filter for each/every code - one should not create
   * one for any codes that this Term does not cover.
   *
   * This function is run under the assumption that the codes this Term covers
   * are covered by no other visible Terms in the search form.
   *
   * @param array $formValues
   *   The user's response to this Term's section of the search form.
   *
   * @return array
   *   Returns an associative array representing a list of filters, where the
   *   key is the filter's code and the value is the filter's value.
   */
  public abstract function buildFilter($formValues);

  /**
   * Renders a form for configuring the Term to be shown in the admin panel.
   *
   * The function should create a form render array as one would with Drupal's
   * Form API. This child form should be able to be included in the parent
   * configuration form, which has the '#tree' property enabled to facilitate
   * this.
   *
   * @return array
   *   Returns a form render array, which will be included within the config
   *   form render array.
   */
  public abstract function renderConfigInputs();

  /**
   * Constructs a configuration for this Term.
   *
   * This function interprets the user-response to the config form, constructing
   * a configuration for this Term based on it.
   *
   * @param array $formValues
   *   The user's response to this Term's section of the admin config form.
   *
   * @return array
   *   Returns an associative array representing the deduced configuration.
   */
  public abstract function buildConfig($formValues);

  /**
   * A prefix to precede every variable persisted in Drupal.
   *
   * @var string
   */
  const CONFIG_PREFIX = 'nt2_search';

  /**
   * A separator dividing more specific variable parts given after the prefix.
   *
   * @var string
   */
  const CONFIG_SEPARATOR = '_';

  /**
   * A human-readable name for the Term, describing what it does in a form.
   *
   * @var string
   */
  private $name;

  /**
   * The Tabs API search Term codes that this Term provides coverage for.
   *
   * @var string[]
   */
  private $codes;

  /**
   * Codes that other Terms must cover for this Term to be visible in a form.
   *
   * @var string[]
   */
  private $dependencyCodes;

  /**
   * Constructs a new Term object.
   *
   * @param string $name
   *   A human-readable name for the Term, describing what it does in a
   *   Form.
   * @param string[] $codes
   *   The Tabs API search Term codes that this Term provides coverage for.
   * @param string[] $dependencyCodes
   *   A list of codes that must be covered by other visible Terms if this Term
   *   is to be visible.
   */
  public function __construct($name, $codes, $dependencyCodes = array()) {
    $this->name = $name;
    $this->codes = $codes;
    $this->dependencyCodes = $dependencyCodes;
  }

  /**
   * Gets a human-readable description of the Term.
   *
   * @return string
   *   The human-readable description.
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Gets a list of Tabs API search term codes the Term provides coverage for.
   *
   * @return string[]
   *   A list of Tabs API search term codes.
   */
  public function getCodes() {
    return $this->codes;
  }

  /**
   * Gets a list of codes that must be covered by other visible Terms.
   *
   * If this Term is to be visible, these codes must be covered by other visible
   * Terms in the same form.
   *
   * @return string[]
   *   A list of Tabs API search term codes.
   */
  public function getDependencyCodes() {
    return $this->dependencyCodes;
  }

  /**
   * Gets a machine identifier for this Term from the codes it covers.
   *
   * This is generated from a sorted list of the codes this Term covers, which
   * can be assumed to be unique in the context of a generated Search Form, as
   * codes may not be covered by multiple visible Terms within one.
   *
   * @return string
   *   The machine identifier for this Term.
   */
  public function getId() {
    // The doc-comment explains this process well.
    sort($this->codes);
    return implode(self::CONFIG_SEPARATOR, $this->codes);
  }

  /**
   * Gets a machine identifier for this Term that is implementation-specific.
   *
   * This is generated from a sorted list of the codes this Term covers, which
   * can be assumed to be unique in the context of a generated Search Form, as
   * codes may not be covered by multiple visible Terms within one. It is then
   * prefixed with the name of the class to prevent different implementations of
   * the codes being ambiguous.
   *
   * @return string
   *   The implementation-specific machine identifier for this Term.
   */
  public function getSpecificId() {
    // The doc-comment explains this process well.
    return get_class($this) . self::CONFIG_SEPARATOR . $this->getId();
  }

  /**
   * Gets whether the search Term should be visible.
   *
   * @param string|null $searchType
   *   If provided, visibility will be ascertained for a particular search type.
   *   Otherwise, TRUE will be returned if the Term is visible for any search
   *   type.
   *
   * @return bool
   *   TRUE if the search Term should be visible, else FALSE.
   */
  public function isVisible($searchType = NULL) {
    // A list of search types this Term is enabled for.
    $enabledFor = variable_get($this->getVarKey('visibility'), array());

    // Do we just care if the Term is visible for ANY search type?
    if (is_null($searchType)) {
      return count($enabledFor) > 0;
    }

    // Is the search type in the list of search types the Term is visible for?
    return in_array($searchType, $enabledFor);
  }

  /**
   * Sets whether a search Term should be visible for a given search type.
   *
   * One should only set the Term to be visible under the assumption that the
   * codes the Term provides for are not covered by any other Terms for the
   * given search type and that all dependency codes are covered by other
   * visible Terms in the same form.
   *
   * @param string $searchType
   *   The search type to set visibility for.
   * @param bool $shouldBeVisible
   *   Whether the search Term should be visible for the given type.
   */
  public function setVisible($searchType, $shouldBeVisible) {
    // A list of search types this Term is enabled for.
    $enabledFor = variable_get($this->getVarKey('visibility'), array());

    // Whether the Term is currently visible for the search type.
    $currentlyVisible = in_array($searchType, $enabledFor);

    // It shouldn't be visible but is.
    if (!$shouldBeVisible && $currentlyVisible) {
      // Remove the search type from the list of visible search types.
      $key = array_search($searchType, $enabledFor);
      unset($enabledFor[$key]);
      $enabledFor = array_values($enabledFor);
    }
    elseif ($shouldBeVisible && !$currentlyVisible) {
      // Add the search type to the list of visible search types.
      $enabledFor[] = $searchType;
    }

    // Persist our changes.
    variable_set($this->getVarKey('visibility'), $enabledFor);
  }

  /**
   * Gets the current settings for the Term, returning defaults if non-existant.
   *
   * These settings are persisted via Drupal.
   *
   * @param array $defaultConfig
   *   An associative array representing the default config.
   *
   * @return array
   *   An associative array representing the config.
   */
  public function getConfig($defaultConfig) {
    // Get a persisted config, if available.
    $stored = variable_get($this->getVarKey('config'), $defaultConfig);

    // Merge with the defaultConfig, so any non-existant values in the persisted
    // config are provided.
    return array_merge($defaultConfig, $stored);
  }

  /**
   * Sets and persists the current settings to those provided as a parameter.
   *
   * @param array $config
   *   The settings wished to be used for this Term.
   */
  public function setConfig($config) {
    // Persist the config with Drupal.
    variable_set($this->getVarKey('config'), $config);
  }

  /**
   * A utility function to get a variable key for Drupal's variable storage.
   *
   * @param string $type
   *   The name of the variable being stored. Usually "configuration" or
   *   "visibilty".
   *
   * @return string
   *   A variable key for use.
   *
   * @see variable_get()
   * @see variable_set()
   */
  private function getVarKey($type) {
    // Join the config prefix, Term id and the type of variable with the
    // separator to make a key.
    return implode(self::CONFIG_SEPARATOR, array(
      self::CONFIG_PREFIX,
      $this->getSpecificId(),
      $type
    ));
  }

}
