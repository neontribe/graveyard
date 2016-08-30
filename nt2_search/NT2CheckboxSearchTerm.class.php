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
  private $defaultOptions = array(
    'omit' => FALSE,
    'label' => 'Checkbox',
  );

  /**
   * Initialise with the code that is covered and a default label.
   *
   * @param string $code
   *   The singular code that this search term implementation covers.
   * @param string $humanName
   *   An understandable but brief description of the search term.
   */
  public function __construct($code, $humanName) {
    parent::__construct([$code], $humanName . ' (Checkbox)');
    $this->defaultOptions = array_merge($this->defaultOptions, array(
      'label' => $humanName,
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function injectInputs(&$form) {
    // Inject an HTML checkbox input.
    $form[$this->getName()] = array(
      '#type' => 'checkbox',
      '#title' => $this->getLabel(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function injectParams(&$params, $formValues) {
    // Extract the value from the form response.
    $formValue = $formValues[$this->getName()];

    if ($formValue === 1) {
      // The condition is TRUE.
      $params[$this->getCodes()[0]] = 'true';
      return;
    }
    else {
      // The condition is FALSE.
      if (!$this->shouldOmit()) {
        // The condition should be explicitly set to false if not ticked.
        $params[$this->getCodes()[0]] = 'false';
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function injectConfigurationInputs(&$form) {
    // Configuration input for the label.
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#description' => t('The label to show in the search form'),
      '#required' => FALSE,
      '#default_value' => $this->getLabel(),
    );

    // Configuration input for whether to omit when unticked.
    $form['omit'] = array(
      '#type' => 'checkbox',
      '#title' => t('Ignore if unspecified'),
      '#description' => t('If enabled, the checkbox has no effect on searches if unticked'),
      '#required' => FALSE,
      '#default_value' => $this->shouldOmit(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function handleConfigurationInputs($form, $formState) {
    // @todo Should some primitive form of validation be used?
    $options = array();

    $options['label'] = $formState['label'];
    $options['omit'] = $formState['omit'] === 1;

    $this->setConfiguration($options);
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

  /**
   * Returns whether the query should be unaffected if the checkbox is unticked.
   *
   * This is useful for functions such as "Pets"; an unticked checkbox does not
   * necessarily indicate a desire for there to be no pets in the cottage.
   *
   * @return bool
   *   TRUE if the value should be omitted when unticked, else FALSE.
   */
  private function shouldOmit() {
    return $this->getConfiguration($this->defaultOptions)['omit'];
  }

}
