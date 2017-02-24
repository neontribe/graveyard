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
    $response = new Response();

    $response->setContent(json_encode(array('hello' => 'world', 'goodbye' => 'world')));
    $response->headers->set('Content-Type', 'application/json');

    return $response;
  }

}
