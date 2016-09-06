<?php

namespace Drupal\nt2_search\uk\co\neontabs\term;

/**
 * @file
 * Contains the RangeSelectTerm class.
 */

/**
 * A search term that gets a user's choice from a range of integer values.
 *
 * It makes the assumption that these range integer values are quantities of
 * an object.
 *
 * @todo Most use-cases required a '>' prefix. How should this be implemented?
 */
class RangeSelectTerm extends Term {
  /**
   * The form value representation of any value being acceptable.
   *
   * @var string
   */
  const ANY_VALUE = 'any';

  /**
   * The default config of the search term used for rendering.
   *
   * This array is pre-populated with default-default-values (what). So. In
   * descending order, here are the priorities of the values used:
   * * Drupal configuration settings
   * * Settings passed to the constructor
   * * The settings below.
   *
   * @var array
   */
  private $defaultConfig = array(
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
   * @param string $name
   *   A description of the search term and its purpose in a search form.
   * @param string $code
   *   The Tabs API search Term code this Term provides coverage for.
   * @param string[] dependencyCodes
   *   A list of codes this Term depends on being covered to be visible.
   * @param array $defaultConfig
   *   Optional rendering config. The admin panel descriptions are most
   *   informative of each one's purpose.
   */
  public function __construct($name, $code, $dependencyCodes, $defaultConfig = array()) {
    parent::__construct($name, array($code), $dependencyCodes);

    $this->defaultConfig = array_merge($this->defaultConfig, $defaultConfig);
  }

  /**
   * {@inheritdoc}
   */
  public function renderInputs() {
    // Render an HTML select input.
    $config = $this->getConfig($this->defaultConfig);
    $form = array();

    $options = array();
    $options[self::ANY_VALUE] = $config['unspecified'];
    for ($i = $config['minimum']; $i <= $config['maximum']; $i++) {
      // Append a '+' to the last number if unlimited.
      $suffix = ($config['unlimited'] && $i === $config['maximum']) ? '+' : '';
      // Singular if 1, else plural.
      $noun = ($i === 1) ? $config['singularNoun'] : $config['pluralNoun'];
      // Glue 'em all together.
      $built = "$i$suffix $noun";
      // Stash it in the options.
      $options[$i] = $built;
    }

    $form[$this->getCodes()[0]] = array(
      '#type' => 'select',
      '#title' => $config['label'],
      '#options' => $options,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildFilter($formValues) {
    // Extract the value from the form response.
    $formValue = $formValues[$this->getCodes()[0]];
    $filter = array();

    // Did the user care for search results to be filtered this way.
    if ($formValue !== self::ANY_VALUE) {
      // @todo Do we need to validate?
      // Pass the form value straight through unaltered.
      $filter[$this->getCodes()[0]] = $formValue;
    }

    return $filter;
  }

  /**
   * {@inheritdoc}
   *
   * @todo There is massive code repetition here; can some be function'd away?
   */
  public function renderConfigInputs() {
    $config = $this->getConfig($this->defaultConfig);
    $form = array();

    $form['unspecified'] = array(
      '#type' => 'textfield',
      '#title' => t('Unspecified option'),
      '#description' => t('The default dropdown option, when no option has been selected'),
      '#required' => FALSE,
      '#default_value' => $config['unspecified'],
    );

    $form['minimum'] = array(
      '#type' => 'textfield',
      '#title' => t('Minimum'),
      '#description' => t('The bottom end of the range'),
      '#required' => FALSE,
      '#default_value' => $config['minimum'],
    );

    $form['maximum'] = array(
      '#type' => 'textfield',
      '#title' => t('Maximum'),
      '#description' => t('The top end of the range'),
      '#required' => FALSE,
      '#default_value' => $config['maximum'],
    );

    $form['unlimited'] = array(
      '#type' => 'checkbox',
      '#title' => t('Unlimited'),
      '#description' => t('Whether the last option should be a minimum bound (e.g. 5+ rather than 5)'),
      '#required' => FALSE,
      '#default_value' => $config['unlimited'],
    );

    $form['singularNoun'] = array(
      '#type' => 'textfield',
      '#title' => t('Singular noun'),
      '#description' => t('The noun to use when there is just one of the object'),
      '#required' => FALSE,
      '#default_value' => $config['singularNoun'],
    );

    $form['pluralNoun'] = array(
      '#type' => 'textfield',
      '#title' => t('Plural noun'),
      '#description' => t('The noun to use when there are multiple objects'),
      '#required' => FALSE,
      '#default_value' => $config['pluralNoun'],
    );

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#description' => t('The label to show in the search form'),
      '#required' => FALSE,
      '#default_value' => $config['label'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Validation is very broken here, especially with integer. There is
   * surely a way that this can be made cleaner, ideally eliminating
   * code-repetition as it does so.
   */
  public function buildConfig($formValues) {
    $config = array();

    // Set strings.
    $config['unspecified'] = $formValues['unspecified'];
    $config['singularNoun'] = $formValues['singularNoun'];
    $config['pluralNoun'] = $formValues['pluralNoun'];
    $config['label'] = $formValues['label'];

    // Set booleans (from checkboxes).
    $config['unlimited'] = $formValues['unlimited'] === 1;

    // Set integers.
    foreach (['minimum', 'maximum'] as $integerKey) {
      $providedValue = $formValues[$integerKey];

      // This appears to be the most sane way to parse an integer in PHP :(.
      $parsedValue = json_decode($providedValue);

      if (gettype($parsedValue) !== 'integer') {
        $msg = $form[$integerKey]['#title'] . ' in ' . $this->getHumanName() . ' should be a whole number';
        drupal_set_message($msg, 'error');
        break;
      }

      // @todo Maybe limit to a sensible range?

      $config[$integerKey] = $parsedValue;
    }

    // @todo Is silently fixing this really the best plan?
    $minimum = min($config['minimum'], $config['maximum']);
    $maximum = max($config['minimum'], $config['maximum']);

    $config['minimum'] = $minimum;
    $config['maximum'] = $maximum;

    return $config;
  }

}
