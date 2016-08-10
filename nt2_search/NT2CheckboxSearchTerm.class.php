<?php

/**
 * A basic SearchTerm, used to obtain a boolean value, represented by a checkbox in the form.
 */
class NT2CheckboxSearchTerm extends NT2SearchTerm {
  /**
   * Constructor overridden as CheckboxSearchTerm only covers one code, not multiple.
   * TODO: consider adding checking of this
   */
  public function __construct($code, $defaultLabel) {
    parent::__construct([$code]);
    $this->defaultLabel = $defaultLabel;
  }

  public function injectInputs(&$form) {
    // inject an HTML checkbox input
    $form[$this->getName()] = array(
      '#type' => 'checkbox',
      '#title' => $this->getLabel(), // TODO: consider passing this through t()
    );
  }

  public function injectParams(&$params) {
    // extract the value from the get request
    $formValue = filter_input(INPUT_GET, $this->getName());

    //////// OLD METHOD THAT DOES NOT OMIT ////////
    // http checkbox to boolean string conversion
    //$queryValue = ($formValue == '1') ? 'true' : 'false';

    // inject the boolean string value into the parameter
    // one can assume that there is only one code, owing to the constructor
    //$params[$this->getIds()[0]] = $queryValue;
    ///////////////////////////////////////////////

    // omit from query if checkbox is unticked
    // TODO: potentially make this optional or clever or something
    if ($formValue == '1') {
      $params[$this->getIds()[0]] = 'true';
    }
  }

  private function getLabel() {
    // TODO: this should be configurable
    return $this->defaultLabel;
  }
}
