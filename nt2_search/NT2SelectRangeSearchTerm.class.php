<?php

/**
 * @file
 * Contains the NT2SelectRangeSearchTerm class.
 */

/**
 * A search term that gets a user's choice from a range of integer values.
 *
 * It makes the assumption that these range integer values are quantities of
 * an object.
 *
 * @todo Most use-cases required a '>' prefix. How should this be implemented?
 */
class NT2SelectRangeSearchTerm extends NT2SearchTerm {
  /**
   * The form value representation of any value being acceptable.
   *
   * @var string
   */
  const ANY_VALUE = 'any';

  /**
   * The default properties of the search term used for rendering.
   *
   * This array is pre-populated with default-default-values (what). So. In
   * descending order, here are the priorities of the values used:
   * * Drupal configuration settings
   * * Settings passed to the constructor
   * * The settings below.
   *
   * @var array
   */
  private $defaultProperties = array(
    'unspecified' => 'Any',
    'minimum' => 1,
    'maximum' => 10,
    'unlimited' => FALSE,
    'singularNoun' => 'thing',
    'pluralNoun' => 'things',
  );

  /**
   * Initialises the class with the code it covers and sensible defaults.
   *
   * @param string $code
   *   The code of the one search term covered in the API.
   * @param string $defaultLabel
   *   The default label specified by the API.
   * @param array $defaultProperties
   *   Optional rendering properties. See documentation of the private
   *   $defaultProperties variable for more information.
   */
  public function __construct($code, $defaultLabel, $defaultProperties = array()) {
    parent::__construct([$code]);

    $this->defaultLabel = $defaultLabel;
    $this->defaultProperties = array_merge($this->defaultProperties, $defaultProperties);
  }

  /**
   * {@inheritdoc}
   */
  public function injectInputs(&$form) {
    // Inject an HTML select input.
    $form[$this->getName()] = array(
      '#type' => 'select',
      '#title' => $this->getLabel(),
      '#options' => $this->generateOptions(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function injectParams(&$params) {
    // Extract the value from the get request.
    $formValue = filter_input(INPUT_GET, $this->getName());

    // Did the user care for search results to be filtered this way.
    if ($formValue !== self::ANY_VALUE) {
      // @todo Do we need to validate?
      // Pass the form value straight through unaltered.
      $params[$this->getIds()[0]] = $formValue;
    }
  }

  /**
   * Get the label that should be used when rendering the element.
   *
   * @todo This code has not yet been made configurable and  used the default.
   * @todo Should t() be used?
   *
   * @return string
   *   Returns the label to use when rendering the element.
   */
  private function getLabel() {
    return $this->defaultLabel;
  }

  /**
   * Generate the options for the select element.
   *
   * @todo This code has not yet been made configurable and  used the default.
   * @todo Should t() be used?
   *
   * @return array
   *   Returns an array of option elements, with the key the value of the
   *   option and the value the displayed label.
   */
  private function generateOptions() {
    $unspecified = $this->defaultProperties['unspecified'];
    $minimum = $this->defaultProperties['minimum'];
    $maximum = $this->defaultProperties['maximum'];
    $unlimited = $this->defaultProperties['unlimited'];
    $singularNoun = $this->defaultProperties['singularNoun'];
    $pluralNoun = $this->defaultProperties['pluralNoun'];

    $options = array();
    $options[self::ANY_VALUE] = $unspecified;
    for ($i = $minimum; $i <= $maximum; $i++) {
      // Append a '+' to the last number if unlimited.
      $suffix = ($unlimited && $i === $maximum) ? '+' : '';
      // Singular if 1, else plural.
      $noun = ($i === 1) ? $singularNoun : $pluralNoun;
      // Glue 'em all together.
      $built = "$i$suffix $noun";
      // Stash it in the options.
      $options[$i] = $built;
    }
    return $options;
  }

}
