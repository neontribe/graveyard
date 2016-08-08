<?php

interface NT2SearchTerm {
  // TODO: ensure that we're not being overzealous with references here
  // TODO: add proper documentation, not this strange self-invented JavaDoc-ish syntax

  /**
   * Inject the relevant input elements into the Drupal form for the search parameters to be determined.
   */
  public function injectInputs(&$form);

  /**
   * Inject the parameters into the search query.
   * This function runs with the assumation that the search form has just been submitted, as it uses filter_input()
   */
  public function injectParams(&$params);

  /**
    * Inject any custom configuration inputs into the form.
    * TODO: have a super-class implementing sensible defaults (e.g. visibility, label)
   */
  public function injectConfigurationInputs(&$form);

  /**
   * Handle the response of any configuration inputs.
   */
  public function handleConfigurationInputs(&$form_state);
}
