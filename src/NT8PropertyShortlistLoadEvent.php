<?php

namespace Drupal\nt8propertyshortlist;

use Symfony\Component\EventDispatcher\Event;

class NT8PropertyShortlistLoadEvent extends Event {
  const NAME = 'nt8propertyshortlist.load';

  protected $results;

  public function __construct($results)
  {
    $this->results = $results;
  }

  public function getResults() {
    return $this->results;
  }

  public function nt8SearchCompleteDescription() {
    return "Fired when the property shortlist page is loaded.";
  }
}