<?php

namespace Drupal\nt8propertyshortlist\Service;
use Drupal\Core\Session\AccountInterface;
use Drupal\nt8property\Service\NT8PropertyService;
use Drupal\nt8tabsio\Service\NT8TabsRestService;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Class NT8PropertyShortlistService.
 *
 * @package Drupal\nt8propertyshortlist\Service
 */
class NT8PropertyShortlistService {

  /**
   * Drupal\nt8property\Service\NT8PropertyService definition.
   *
   * @var \Drupal\nt8property\Service\NT8PropertyService
   */
  protected $nt8propertyPropertyMethods;
  /**
   * Drupal\nt8tabsio\Service\NT8TabsRestService definition.
   *
   * @var \Drupal\nt8tabsio\Service\NT8TabsRestService
   */
  protected $nt8tabsioTabsService;
  /**
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  private $sessionManager;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  private $currentUser;

  /**
   * @var \Drupal\user\PrivateTempStore
   */
  protected $store;

  /**
   * Constructs a new NT8PropertyShortlistService object.
   */
  public function __construct(
    NT8PropertyService $nt8property_property_methods,
    NT8TabsRestService $nt8tabsio_tabs_service,
    PrivateTempStoreFactory $temp_store_factory,
    SessionInterface $session_manager,
    AccountInterface $current_user
  ) {
    $this->nt8propertyPropertyMethods = $nt8property_property_methods;
    $this->nt8tabsioTabsService = $nt8tabsio_tabs_service;
    $this->tempStoreFactory = $temp_store_factory;
    $this->sessionManager = $session_manager;
    $this->currentUser = $current_user;

    if ($this->currentUser->isAnonymous() && !isset($_SESSION['session_started'])) {
      $_SESSION['session_started'] = true;
      $this->sessionManager->start();
    }

    $this->store = $this->tempStoreFactory->get('nt8search.search_results');
  }

  /**
   * @param string $name
   *
   * @return mixed
   */
  public function getStore(string $name = 'nt8propertyshortlist.list') {
    return $this->store->get($name) ?: [];
  }

  /**
   * @param array $newStore
   * @param string $name
   *
   * @return array
   * @throws \Drupal\user\TempStoreException
   */
  public function setStore(array $newStore, string $name = 'nt8propertyshortlist.list') {
    $this->store->set($name, $newStore);
    return $newStore;
  }

  /**
   * @param string $propRef
   * @param bool $state
   */
  public function setEntry(string $propRef, bool $state = TRUE) {
    $store = $this->getStore();

    $store[$propRef] = $propRef;
    if(!$state) unset($store[$propRef]);

    $this->setStore($store);

    return $store;
  }


  public function getEntry(string $propRef) {
    $store = $this->getStore();

    if($this->nt8propertyPropertyMethods->issetGet($store, $propRef)) {
      return $propRef;
    }

    return NULL;
  }

  /**
   * Toggles an entry in the property shortlist.
   *
   * @param string $propRef
   */
  public function toggleEntry(string $propRef) {
    $loadedCandidates = $this->nt8propertyPropertyMethods->loadNodesFromPropref($propRef);
    $currentShortlist = $this->getStore();

    if(count($loadedCandidates) === 0) {
      \Drupal::logger('nt8propertyshortlist.toggleEntry')->info("Property $propRef does not exist.");
      return $currentShortlist;
    }

    $setState = TRUE;
    if(isset($currentShortlist[$propRef])) $setState = FALSE;
    $currentShortlist = $this->setEntry($propRef, $setState);

    return $currentShortlist;
  }

}
