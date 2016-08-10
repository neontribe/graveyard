<?php

class NT2SelectRangeSearchTerm extends NT2SearchTerm {
  const ANY_VALUE = 'any';

  private $defaultProperties = array(
    'unspecified' => 'Any',
    'minimum' => 1,
    'maximum' => 10,
    'unlimited' => FALSE,
    'singularNoun' => 'thing',
    'pluralNoun' => 'things',
  );

  public function __construct($code, $defaultLabel, $defaultProperties) {
    parent::__construct([$code]);

    $this->defaultLabel = $defaultLabel;
    // TODO: explain multiple meta-layers of defaults
    $this->defaultProperties = array_merge($this->defaultProperties, $defaultProperties);
  }

  public function injectInputs(&$form) {
    // inject an HTML select input
    $form[$this->getName()] = array(
      '#type' => 'select',
      '#title' => $this->getLabel(),
      '#options' => $this->generateOptions(),
    );
  }

  public function injectParams(&$params) {
    // extract the value from the get request
    $formValue = filter_input(INPUT_GET, $this->getName());

    // did the user care for search results to be filtered this way
    if ($formValue !== self::ANY_VALUE) {
      // TODO: do we need to validate?
      $params[$this->getIds()[0]] = $formValue; // pass the form value straight through unaltered
    }
  }

  private function getLabel() {
    // TODO: this should be configurable
    return $this->defaultLabel;
  }

  private function generateOptions() {
    // TODO: this should be configurable
    $unspecified = $this->defaultProperties['unspecified'];
    $minimum = $this->defaultProperties['minimum'];
    $maximum = $this->defaultProperties['maximum'];
    $unlimited = $this->defaultProperties['unlimited'];
    $singularNoun = $this->defaultProperties['singularNoun'];
    $pluralNoun = $this->defaultProperties['pluralNoun'];

    $options = array();
    $options[self::ANY_VALUE] = $unspecified; // TODO: should we be using t()
    for ($i = $minimum; $i <= $maximum; $i++) {
      $suffix = ($unlimited && $i === $maximum) ? '+' : ''; // append a '+' to the last number if unlimited
      $noun = ($i === 1) ? $singularNoun : $pluralNoun; // singular if 1, else plural
      $built = "$i$suffix $noun"; // glue 'em all together
      $options[$i] = $built; // stash it in the options
    }
    return $options;
  }
}
