<?php

namespace Drupal\nt8property\events;

use Symfony\Component\EventDispatcher\Event;

class NT8PropertyLoadEvent extends Event {

  const SUBMIT = 'nt8property.load';
  protected $referenceID;

  public function __construct($referenceID)
  {
    $this->referenceID = $referenceID;
  }

  public function getReferenceID()
  {
    return $this->referenceID;
  }

  public function myEventDescription() {
    return "Fired when a property page is loaded.";
  }
}