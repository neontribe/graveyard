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
    'label' => 'Things',
  );

  /**
   * Initialises the class with the code it covers and sensible defaults.
   *
   * @param string $code
   *   The code of the one search term covered in the API.
   * @param string $humanReadable
   *   An understandable description of the search term.
   * @param array $defaultProperties
   *   Optional rendering properties. See documentation of the private
   *   $defaultProperties variable for more information.
   */
  public function __construct($code, $humanReadable, $defaultProperties = array()) {
    parent::__construct([$code], $humanReadable);

    $defaultProperties['label'] = $humanReadable;

    $this->defaultProperties = array_merge($this->defaultProperties, $defaultProperties);
  }

  /**
   * {@inheritdoc}
   */
  public function injectInputs(&$form) {
    // Inject an HTML select input.

    $configuration = $this->getConfiguration($this->defaultProperties);

    $options = array();
    $options[self::ANY_VALUE] = $configuration['unspecified'];
    for ($i = $configuration['minimum']; $i <= $configuration['maximum']; $i++) {
      // Append a '+' to the last number if unlimited.
      $suffix = ($configuration['unlimited'] && $i === $configuration['maximum']) ? '+' : '';
      // Singular if 1, else plural.
      $noun = ($i === 1) ? $configuration['singularNoun'] : $configuration['pluralNoun'];
      // Glue 'em all together.
      $built = "$i$suffix $noun";
      // Stash it in the options.
      $options[$i] = $built;
    }

    $form[$this->getName()] = array(
      '#type' => 'select',
      '#title' => $configuration['label'],
      '#options' => $options,
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
      $params[$this->getCodes()[0]] = $formValue;
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
  public function handleConfigurationInputs(&$form_state) {
    // Nothing to do here for now.
  }

}
