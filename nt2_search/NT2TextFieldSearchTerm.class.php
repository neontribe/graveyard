<?php

/**
 * @file
 * Contains the NT2TextFieldSearchTerm class.
 */

/**
 * A basic SearchTerm implementation, obtaining a string value from a text field.
 */
class NT2TextFieldSearchTerm extends NT2SearchTerm {
  /**
   * The default options to use when rendering and interpreting the search term.
   *
   * @var array
   */
  private $defaultOptions = array(
    'omit' => TRUE,
    'label' => 'Text',
  );

  /**
   * Initialise with the $code that is covered and a default label.
   *
   * @param string $code
   *   The singular code that this search term implementation covers.
   * @param string $humanName
   *   An understandable but brief description of the search term.
   */
  public function __construct($code, $humanName) {
    parent::__construct([$code], $humanName . ' (Text Field)');
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
      '#type' => 'textfield',
      '#title' => $this->getLabel(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function injectParams(&$params, $formValues) {
    // Extract the value from the form response.
    $formValue = $formValues[$this->getName()];

    // @todo Do we need to sanitise this?

    if (strlen($formValue) !== 0) {
      // The user has submitted some text.
      $params[$this->getCodes()[0]] = $formValue;
      return;
    }
    else {
      // The search field is blank.
      if (!$this->shouldOmit()) {
        // The contents should be submitted even if empty.
        $params[$this->getCodes()[0]] = $formValue;
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
      '#description' => t('If enabled, the search field has no effect on searches if empty'),
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
   * Returns the label to be rendered with the input element.
   *
   * @return string
   *   The default label.
   */
  private function getLabel() {
    return $this->getConfiguration($this->defaultOptions)['label'];
  }

  /**
   * Returns whether the query should be unaffected if the textfield is empty.
   *
   * @return bool
   *   TRUE if the value should be omitted when empty, else FALSE.
   */
  private function shouldOmit() {
    return $this->getConfiguration($this->defaultOptions)['omit'];
  }

}
