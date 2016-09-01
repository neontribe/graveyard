<?php

namespace Drupal\nt2_search\uk\co\neontabs\term;

/**
 * @file
 * Contains the SelectRangeSearchTerm class.
 */

/**
 * A search term that gets a user's choice from a range of integer values.
 *
 * It makes the assumption that these range integer values are quantities of
 * an object.
 *
 * @todo Most use-cases required a '>' prefix. How should this be implemented?
 */
class SelectRangeSearchTerm extends SearchTerm {
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
    parent::__construct([$code], $humanReadable . ' (Select range)');

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
  public function injectParams(&$params, $formValues) {
    // Extract the value from the form response.
    $formValue = $formValues[$this->getName()];

    // Did the user care for search results to be filtered this way.
    if ($formValue !== self::ANY_VALUE) {
      // @todo Do we need to validate?
      // Pass the form value straight through unaltered.
      $params[$this->getCodes()[0]] = $formValue;
    }
  }

  /**
   * {@inheritdoc}
   *
   * @todo There is massive code repetition here; can some be function'd away?
   */
  public function injectConfigurationInputs(&$form) {
    $configuration = $this->getConfiguration($this->defaultProperties);

    $form['unspecified'] = array(
      '#type' => 'textfield',
      '#title' => t('Unspecified option'),
      '#description' => t('The default dropdown option, when no option has been selected'),
      '#required' => FALSE,
      '#default_value' => $configuration['unspecified'],
    );

    $form['minimum'] = array(
      '#type' => 'textfield',
      '#title' => t('Minimum'),
      '#description' => t('The bottom end of the range'),
      '#required' => FALSE,
      '#default_value' => $configuration['minimum'],
    );

    $form['maximum'] = array(
      '#type' => 'textfield',
      '#title' => t('Maximum'),
      '#description' => t('The top end of the range'),
      '#required' => FALSE,
      '#default_value' => $configuration['maximum'],
    );

    $form['unlimited'] = array(
      '#type' => 'checkbox',
      '#title' => t('Unlimited'),
      '#description' => t('Whether the last option should be a minimum bound (e.g. 5+ rather than 5)'),
      '#required' => FALSE,
      '#default_value' => $configuration['unlimited'],
    );

    $form['singularNoun'] = array(
      '#type' => 'textfield',
      '#title' => t('Singular noun'),
      '#description' => t('The noun to use when there is just one of the object'),
      '#required' => FALSE,
      '#default_value' => $configuration['singularNoun'],
    );

    $form['pluralNoun'] = array(
      '#type' => 'textfield',
      '#title' => t('Plural noun'),
      '#description' => t('The noun to use when there are multiple objects'),
      '#required' => FALSE,
      '#default_value' => $configuration['pluralNoun'],
    );

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#description' => t('The label to show in the search form'),
      '#required' => FALSE,
      '#default_value' => $configuration['label'],
    );
  }

  /**
   * {@inheritdoc}
   *
   * @todo Validation is very broken here, especially with integer. There is
   * surely a way that this can be made cleaner, ideally eliminating
   * code-repetition as it does so.
   */
  public function handleConfigurationInputs($form, $formState) {
    $configuration = array();

    // Set strings.
    $configuration['unspecified'] = $formState['unspecified'];
    $configuration['singularNoun'] = $formState['singularNoun'];
    $configuration['pluralNoun'] = $formState['pluralNoun'];
    $configuration['label'] = $formState['label'];

    // Set booleans (from checkboxes).
    $configuration['unlimited'] = $formState['unlimited'] === 1;

    // Set integers.
    foreach (['minimum', 'maximum'] as $integerKey) {
      $providedValue = $formState[$integerKey];

      // This appears to be the most sane way to parse an integer in PHP :(.
      $parsedValue = json_decode($providedValue);

      if (gettype($parsedValue) !== 'integer') {
        $msg = $form[$integerKey]['#title'] . ' in ' . $this->getHumanName() . ' should be a whole number';
        drupal_set_message($msg, 'error');
        break;
      }

      // @todo Maybe limit to a sensible range?

      $configuration[$integerKey] = $parsedValue;
    }

    // @todo Is silently fixing this really the best plan?
    $minimum = min($configuration['minimum'], $configuration['maximum']);
    $maximum = max($configuration['minimum'], $configuration['maximum']);

    $configuration['minimum'] = $minimum;
    $configuration['maximum'] = $maximum;

    $this->setConfiguration($configuration);
  }

}
