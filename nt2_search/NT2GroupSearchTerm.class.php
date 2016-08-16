<?php

/**
 * @file
 * Contains the NT2GroupSearchTerm class.
 */

/**
 * A search term that acts as a parent for child search terms.
 *
 * This class allows the reuse of other search terms to be included in larger,
 * semantic search terms.
 *
 * For example, "fromDate" can be its own search term. "nights", however, may
 * only be used with "fromDate". Therefore, one can make both the search term
 * for "fromDate" and an NT2GroupSearchTerm containing the search terms for
 * "fromDate" and "nights".
 *
 * With the checking that codes are not claimed by multiple search terms, this
 * allows for clashes to be checked easily. Either the NT2GroupSearchTerm is
 * enabled, the "fromDate" search term is enabled alone or neither are enabled.
 *
 * In this manner, clashes are prevented and search terms can be grouped
 * together where it makes sense to do so.
 *
 * TL;DR: hacky multiple inheritance, sorry future maintainers; I promise it
 * earns its worth and makes code in other areas of the codebase much, much
 * nicer.
 */
class NT2GroupSearchTerm extends NT2SearchTerm {
  /**
   * The child search terms that this group contains.
   *
   * @var NT2SearchTerm[]
   */
  private $childSearchTerms;

  /**
   * Constructs a group search term with the given codes and children.
   *
   * @param string[] $codes
   *   The codes that the group search term has coverage of.
   * @param string $humanReadable
   *   An understandable description of the search term.
   * @param NT2SearchTerm[] $childSearchTerms
   *   The child search terms that inheritance is drawn from.
   */
  public function __construct($codes, $humanReadable, $childSearchTerms) {
    parent::__construct($codes, $humanReadable);
    $this->childSearchTerms = $childSearchTerms;
  }

  /**
   * {@inheritdoc}
   */
  public function injectInputs(&$form) {
    // Call injectInputs() on all children.
    foreach ($childSearchTerms as $childSearchTerm) {
      $childSearchTerm->injectInputs($form);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function injectParams(&$params) {
    // Call injectParams() on all children.
    foreach ($childSearchTerms as $childSearchTerm) {
      $childSearchTerm->injectParams($params);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function injectConfigurationInputs(&$formState) {
    // Call injectConfigurationInputs() on all children.
    foreach ($childSearchTerms as $childSearchTerm) {
      $childSearchTerm->injectConfigurationInputs($formState);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function handleConfigurationInputs(&$formState) {
    // Call handleConfigurationInputs() on all children.
    foreach ($childSearchTerms as $childSearchTerm) {
      $childSearchTerm->handleConfigurationInputs($formState);
    }
  }

}
