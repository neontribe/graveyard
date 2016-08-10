<?php
/**
 * ############
 * ### NOTE ###
 * ############
 *
 * This class is incredibly unfinished, with many values just included for testing.
 * Expect implementation to occur imminently, assuming there are no problems (hah!).
 *
 * TODO: documentation, documentation, documentation
 */
abstract class NT2SearchTerm {
  // TODO: ensure that we're not being overzealous with references here
  // TODO: add proper documentation, not this strange self-invented JavaDoc-ish syntax

  protected $codes;

  public function __construct($codes) {
    $this->codes = $codes;
    sort($this->codes); // this allows a unique identifier to be constructed later
  }

  /**
   * Inject the relevant input elements into the Drupal form for the search parameters to be determined.
   */
  public abstract function injectInputs(&$form);

  /**
   * Inject the parameters into the search query.
   * This function runs with the assumation that the search form has just been submitted, as it uses filter_input()
   */
  public abstract function injectParams(&$params);

  /**
   * Returns the list of codes that this search term covers.
   * This is generally used to avoid conflicts between SearchTerms that both claim codes;
   */
  public function getIds() {
    return $this->codes;
  }

  /**
   * Get whether the search term is visible for the provided search type (e.g. "QUICK", "ADVANCED").
   * TODO: should search type be an enum or are basic strings fine?
   */
  protected function isVisible($searchType) {
    // TODO: check settings
    return TRUE;
  }

  /**
    * Inject any custom configuration inputs into the form.
   */
  public function injectConfigurationInputs(&$form) {
    // TODO: add visibility options
  }

  /**
   * Handle the response of any configuration inputs.
   */
  public function handleConfigurationInputs(&$form_state) {
    // TODO: handle label and visibility options
  }

  // TODO: thoughtfully consider the visibility of the below methods

  /**
   * Return the name of the NT2SearchTerm.
   * As any id can only be claimed be one enabled search term at any given time, this can be assumed to be unique for each session.
   */
  protected function getName() {
    return join('_', $this->codes); // $codes is pre-sorted in constructor for consistent results
  }
}
