<?php

class NT2GroupSearchTerm extends NT2SearchTerm {
  public function __construct($codes, $childSearchTerms) {
    parent::__construct($codes);
    $this->childSearchTerms = $childSearchTerms;
  }

  public function injectInputs(&$form) {
    foreach ($childSearchTerms as $childSearchTerm) {
      $childSearchTerm.injectInputs($form);
    }
  }

  public function injectParams(&$params) {
    foreach ($childSearchTerms as $childSearchTerm) {
      $childSearchTerm.injectParams($params);
    }
  }
  
  public function injectConfigurationInputs(&$form_state) {
    foreach ($childSearchTerms as $childSearchTerm) {
      $childSearchTerm.injectConfigurationInputs($form_state);
    }
  }

  public function handleConfigurationInputs(&$form_state) {
    foreach ($childSearchTerms as $childSearchTerm) {
      $childSearchTerm.handleConfigurationInputs($form_state);
    }
  }
}
