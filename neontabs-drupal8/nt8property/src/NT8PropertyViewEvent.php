<?php

namespace Drupal\nt8property;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class NT8PropertyViewEvent
 *
 * Returns the propRef of the visited property.
 *
 * @package Drupal\nt8property
 */
class NT8PropertyViewEvent extends Event {
  const NAME = 'nt8property.view';

  protected $results;

  public function __construct($propref)
  {
    $this->results = $propref;
  }

  public function getResult() {
    return $this->results;
  }

  public function nt8SearchCompleteDescription() {
    return "Fired when a property page is viewed.";
  }
}