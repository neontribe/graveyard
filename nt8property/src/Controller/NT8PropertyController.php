<?php

namespace Drupal\nt8property\Controller;

use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\nt8tabsio\Service\NT8TabsRestService;

/**
 * Description of NT8PropertyController
 *
 * @author oliver@neontribe.co.uk
 */
class NT8PropertyController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function fixture($propRef) {
    $path = DRUPAL_ROOT . '/' . drupal_get_path('module', 'nt8property') . "/src/Fixtures/$propRef.json";
    $fixtureData = @file_get_contents($path);

    $response = new Response('No file matching this propref was found.');

    if($fixtureData) {
      $response = new Response($fixtureData);
    }

    $response->headers->set('Content-Type', 'application/json');
    return $response;
  }

}
