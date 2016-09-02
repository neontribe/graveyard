<?php

namespace Drupal\nt2_search\uk\co\neontabs\term;

/**
 * @file
 * Contains the CheckboxTerm class.
 */

/**
 * A basic Term implementation, obtaining a boolean value from a checkbox.
 */
class CheckboxTerm extends Term {
  /**
   * The default options to use when rendering and interpreting the Term.
   *
   * @var array
   */
  private $defaultOptions = array(
    'omit' => FALSE,
    'label' => 'Checkbox',
  );

  /**
   * Constructs a new CheckboxTerm.
   *
   * @param string $name
   *   A human-readable name, describing what the Term does in a form.
   * @param string $code
   *   The Tabs API search Term code this Term provides coverage for.
   * @param string[] $dependencyCodes
   *   A list of codes this Term depends on being covered to be visible.
   * @param string $label
   *   A label for the checkbox.
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

    // Create an HTML checkbox input.
    $form[$this->getCodes()[0]] = array(
      '#type' => 'checkbox',
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

    if ($formValue === 1) {
      // The checkbox is ticked, indicating true.
      $filter[$this->getCodes()[0]] = 'true';
    }
    else {
      // The checkbox is unticked...
      if (!$config['omit']) {
        // ...and we want to treat this as indicating false.
        $filter[$this->getCodes()[0]] = 'false';
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
      '#description' => t('If enabled, the checkbox has no effect on searches if unticked'),
      '#required' => FALSE,
      '#default_value' => $config['omit'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfig($formValues) {
    // @todo Should some primitive form of validation be used?
    $config = array();

    $config['label'] = $formValues['label'];
    $config['omit'] = $formValues['omit'] === 1;

    return $config;
  }

}
