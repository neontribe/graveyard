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
   * The default options to use when rendering and interpreting the search term.
   *
   * @var array
   */
  private $defaultOptions;

  /**
   * Initialise with the code that is covered and a default label.
   *
   * @param string $code
   *   The singular code that this search term implementation covers.
   * @param string $humanName
   *   An understandable but brief description of the search term.
   */
  public function __construct($code, $humanName) {
    parent::__construct([$code], $humanName);
    $this->defaultOptions = array(
      'label' => $humanName,
    );
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
      $params[$this->getCodes()[0]] = 'true';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function injectConfigurationInputs(&$form) {
    // Nothing to do here for now.
  }

  /**
   * {@inheritdoc}
   */
  public function handleConfigurationInputs(&$formState) {
    // Nothing to do here for now.
  }

  /**
   * Returns the default label, as previously provided by the API.
   *
   * @return string
   *   The default label.
   */
  private function getLabel() {
    return $this->getConfiguration($this->defaultOptions)['label'];
  }

}
