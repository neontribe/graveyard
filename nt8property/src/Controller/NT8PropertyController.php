<?php

namespace Drupal\nt8property\Controller;

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
  public function content($propRef) {
    $build = array(
      '#type' => 'markup',
      '#markup' => $propRef,
    );
    return $build;
  }

}
