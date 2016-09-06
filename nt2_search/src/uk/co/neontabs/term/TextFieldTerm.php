<?php

namespace Drupal\nt2_search\uk\co\neontabs\term;

/**
 * @file
 * Contains the TextFieldTerm class.
 */

/**
 * A basic Term implementation, obtaining a string value from a text field.
 */
class TextFieldTerm extends Term {
  /**
   * The default options to use when rendering and interpreting the Term.
   *
   * @var array
   */
  private $defaultOptions = array(
    'omit' => TRUE,
    'label' => 'Text',
  );

  /**
   * Constructs a new TextFieldTerm.
   *
   * @param string $name
   *   A human-readable name, describing what the Term does in a form.
   * @param string $code
   *   The Tabs API search Term code this Term provides coverage for.
   * @param string[] $dependencyCodes
   *   A list of codes this Term depends on being covered to be visible.
   * @param string $label
   *   A label for the text field.
   */
  public function __construct($name, $code, $dependencyCodes, $label) {
    parent::__construct($name, array($code), $dependencyCodes);

    // Store this default option.
    $this->defaultOptions = array_merge($this->defaultOptions, array(
      'label' => $label,
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function renderInputs() {
    $config = $this->getConfig($this->defaultOptions);
    $form = array();

    // Inject an HTML text field input.
    $form[$this->getCodes()[0]] = array(
      '#type' => 'textfield',
      '#title' => $config['label'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildFilter($formValues) {
    $config = $this->getConfig($this->defaultOptions);
    $filter = array();

    // Extract the value from the form response.
    $formValue = $formValues[$this->getCodes()[0]];

    // @todo Do we need to sanitise this?

    if (strlen($formValue) !== 0) {
      // The user has submitted some text.
      $filter[$this->getCodes()[0]] = $formValue;
    }
    else {
      // The search field is blank.
      if (!$config['omit']) {
        // The contents should be submitted even if empty.
        $filter[$this->getCodes()[0]] = $formValue;
      }
    }

    return $filter;
  }

  /**
   * {@inheritdoc}
   */
  public function renderConfigInputs() {
    $config = $this->getConfig($this->defaultOptions);
    $form = array();

    // Configuration input for the label.
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#description' => t('The label to show in the search form'),
      '#required' => FALSE,
      '#default_value' => $config['label'],
    );

    // Configuration input for whether to omit when unticked.
    $form['omit'] = array(
      '#type' => 'checkbox',
      '#title' => t('Ignore if unspecified'),
      '#description' => t('If enabled, the search field has no effect on searches if empty'),
      '#required' => FALSE,
      '#default_value' => $config['omit'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfig($formState) {
    // @todo Should some primitive form of validation be used?
    $config = array();

    $config['label'] = $formState['label'];
    $config['omit'] = $formState['omit'] === 1;

    return $config;
  }

}
