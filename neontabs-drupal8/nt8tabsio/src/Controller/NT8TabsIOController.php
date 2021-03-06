<?php

namespace Drupal\nt8tabsio\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\nt8tabsio\Service\NT8TabsRestService;

/**
 * Description of NT8TabsIOController.
 *
 * @author tobias@neontribe.co.uk
 */
class NT8TabsIOController extends ControllerBase {

  /**
   * Instance of NT8TabsRestService.
   *
   * @var \Drupal\nttabsio\Service\NTTabsRestService
   */
  protected $nt8TabsRestService;

  /**
   * {@inheritdoc}
   */
  public function __construct(NT8TabsRestService $nt8TabsRestService) {
    $this->nt8TabsRestService = $nt8TabsRestService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('nt8tabsio.tabs_service'));
  }

  /**
   * A sample status page.
   */
  public function status() {
    $apidata = $this->nt8TabsRestService->get('property/H610_ZZ');
    return [
      '#markup' => json_encode(json_decode($apidata), JSON_PRETTY_PRINT),
      '#prefix' => '<pre>',
      '#suffix' => '</pre>',
    ];
  }

}
