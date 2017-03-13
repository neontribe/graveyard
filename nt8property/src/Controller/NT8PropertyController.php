<?php

namespace Drupal\nt8property\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\nt8property\Service\NT8PropertyService;

/**
 * Description of NT8PropertyController
 *
 * @author oliver@neontribe.co.uk
 */
class NT8PropertyController extends ControllerBase {
  protected $propertyMethods;

  public function __construct(NT8PropertyService $propertyMethods) {
    $this->propertyMethods = $propertyMethods;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('nt8property.property_methods')
    );
  }

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

  /**
   * {@inheritdoc}
   */
  public function propertyLoad($propRef) {
    $node = $this->propertyMethods->createNodeInstanceFromPropref($propRef);
    $url =  \Drupal\Core\Url::fromRoute('property.generate')->toString();

    if(isset($node) && $node instanceof \Drupal\node\Entity\Node) {
      $options = ['absolute' => TRUE];
      $nid = $node->nid->getValue()[0]['value'];
      $url = \Drupal\Core\Url::fromRoute('entity.node.canonical', ['node' => $nid], $options);
      $url = $url->toString();

      drupal_set_message("Node: $nid created successfully!");
    } else {
      drupal_set_message("There was an issue creating a node for: $propRef");
    }

    $response = new RedirectResponse($url);

    return $response;
  }

  public function propertyUpdate($propRef) {
    //TODO: Implement this.
  }

}
