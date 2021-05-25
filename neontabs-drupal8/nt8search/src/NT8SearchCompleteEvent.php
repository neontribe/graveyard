<?php

namespace Drupal\nt8search;

use Symfony\Component\EventDispatcher\Event;

class NT8SearchCompleteEvent extends Event {
  const NAME = 'nt8search.complete';

  protected $results;

  public function __construct($results)
  {
    $this->results = $results;
  }

  public function getResults() {
    return $this->results;
  }

  public function nt8SearchCompleteDescription() {
    return "Fired when a property search completes execution.";
  }
}