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
   * The codes from the search terms API this object may provide values for.
   *
   * @var string[]
   */
  protected $codes;

  /**
   * Initialises with the codes that the search term provides coverage of.
   *
   * This is likely to be extended with additional parameters by extending
   * classes.
   *
   * @param string[] $codes
   *   The list of codes from the search terms API that the search term
   *   provides coverage for.
   */
  public function __construct($codes) {
    $this->codes = $codes;

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
   * Returns a list of codes that this search term provides coverage for.
   *
   * This is generally used to avoid conflicts between SearchTerms that both
   * claim codes.
   *
   * @return string[]
   *   A list of the codes, as strings in an array.
   */
  public function getIds() {
    return $this->codes;
  }

  /**
   * Determines if this search term should be visible for the given search type.
   *
   * @param string $searchType
   *   The search type to check for. (e.g. "QUICK", "ADVANCED")
   *
   * @todo Should search type be an enum or are basic strings fine?
   * @todo Potential check to see if the search term is visible for ANY type.
   *
   * @return bool
   *   TRUE if the search term should be visible, otherwise FALSE.
   */
  protected function isVisible($searchType) {
    // @todo This should check the configuration.
    return TRUE;
  }

  /**
   * Injects any configurable options into the admin form.
   *
   * The admin form is shown in Drupal configuration to allow the admin to
   * configure what search terms are rendered and how they are rendered.
   *
   * @param array $form
   *   The form to inject the inputs into. Passed by reference, to allow for
   *   modification.
   */
  public function injectConfigurationInputs(&$form) {
    // @todo Add visibility options.
  }

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
  public function handleConfigurationInputs(&$form_state) {
    // @todo As a bare minimum, visibility options should be handled.
  }

  /**
   * Returns an identifying name for the search term.
   *
   * This is based on the joining of the alphabetically-sorted codes this
   * search term has coverage of.
   *
   * This should be unique for any generated search form, as it is not allowed
   * for a search form to cover the same codes multiple times.
   *
   * It would not be sufficiently unique for configuration, however, as there
   * may be dormant alternative implementations of coverage for the same codes
   * that, while not enabled, may still haev configuration options.
   *
   * @todo Will prepending the implementing class name solve the above issue?
   *
   * @return string
   *   A collection of code names joined with underscores, as a string.
   */
  protected function getName() {
    // $codes is pre-sorted in constructor for consistent results.
    return implode('_', $this->codes);
  }

}
