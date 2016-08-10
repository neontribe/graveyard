<?php

/**
 * @file
 * Contains the NT2CheckboxSearchTerm class.
 */

/**
 * A basic SearchTerm implementation, obtaining a boolean value from a checkbox.
 */
class NT2CheckboxSearchTerm extends NT2SearchTerm {

  /**
   * Initialise with the code that is covered and a default label.
   *
   * @param string $code
   *   The singular code that this search term implementation covers.
   * @param string $defaultLabel
   *   The default label for the search term provided in the API.
   */
  public function __construct($code, $defaultLabel) {
    parent::__construct([$code]);
    $this->defaultLabel = $defaultLabel;
  }

  /**
   * {@inheritdoc}
   */
  public function injectInputs(&$form) {
    // Inject an HTML checkbox input.
    $form[$this->getName()] = array(
      '#type' => 'checkbox',
      // @todo Consider passing this through t()
      '#title' => $this->getLabel(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function injectParams(&$params) {
    // Extract the value from the get request.
    $formValue = filter_input(INPUT_GET, $this->getName());

    // Omit from query if checkbox is unticked.
    // @todo Potentially make this optional or clever or something.
    if ($formValue == '1') {
      $params[$this->getIds()[0]] = 'true';
    }
  }

  /**
   * Returns the default label, as previously provided by the API.
   *
   * @return string
   *   The default label.
   */
  private function getLabel() {
    // @todo This should be configurable.
    return $this->defaultLabel;
  }

}
