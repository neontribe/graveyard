<?php
/**
* @file
* Contains \Drupal\my_event_subscriber\EventSubscriber\MyEventSubscriber.
*/

namespace Drupal\nt8map\EventSubscriber;

use Drupal\nt8map\Service\NT8MapService;
use Drupal\nt8property\Service\NT8PropertyService;
use Drupal\nt8propertyshortlist\NT8PropertyShortlistLoadEvent;
use Drupal\nt8search\NT8SearchCompleteEvent;
use Drupal\nt8search\Service\NT8SearchService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
* Event Subscriber MyEventSubscriber.
*/
class NT8MapSubscriber implements EventSubscriberInterface {

  protected $nt8map;
  protected $nt8search;
  protected $nt8property;

  public function __construct(
    NT8MapService $nt8map,
    NT8SearchService $nt8search,
    NT8PropertyService $nt8property) {
      $this->nt8map = $nt8map;
      $this->nt8search = $nt8search;
      $this->nt8property = $nt8property;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[NT8SearchCompleteEvent::NAME][] = ['onSearchComplete'];
    $events[NT8PropertyShortlistLoadEvent::NAME][] = ['onShortlistPageLoad'];
    return $events;
  }

  /**
   * Triggered upon search completion.
   *
   * @see \Drupal\nt8search\Service\NT8SearchService
   */
  public function onSearchComplete(NT8SearchCompleteEvent $event) {
    $results = $event->getResults();
    $loadedResultNodes = $this->nt8search->loadSearchResultIntoNodes($results);
    $this->nt8map->setMapState($loadedResultNodes);
  }

  /**
   * Triggered on shortlist page load.
   *
   * @see \Drupal\nt8propertyshortlist\Controller\NT8PropertyShortlistController
   */
  public function onShortlistPageLoad(NT8PropertyShortlistLoadEvent $event) {
    $results = $event->getResults();
    $loadedResultNodes = $this->nt8property
      ->loadNodesFromProprefs($results);
    $this->nt8map->setMapState($loadedResultNodes);
  }
}