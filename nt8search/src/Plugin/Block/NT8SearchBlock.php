<?php

namespace Drupal\nt8search\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\nt8tabsio\Service\NT8TabsRestService;

/**
 * Provides a 'NT8SearchBlock' block.
 *
 * @Block(
 *  id = "nt8search_block",
 *  admin_label = @Translation("Neontabs 8 Search Block"),
 * )
 */
class NT8SearchBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\nt8tabsio\Service\NT8TabsRestService definition.
   *
   * @var \Drupal\nt8tabsio\Service\NT8TabsRestService
   */
  protected $nt8tabsioTabsService;

  /**
   * NT8SearchBlock constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\nt8tabsio\Service\NT8TabsRestService $nt8tabsio_tabs_service
   *   Instance of NT8TabsIO service.
   */
  public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        NT8TabsRestService $nt8tabsio_tabs_service
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->nt8tabsioTabsService = $nt8tabsio_tabs_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('nt8tabsio.tabs_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [
      '#cache' => [
        'contexts' => [
          'url.path',
        ],
      ],
    ];

    $builtForm = \Drupal::formBuilder()->getForm('Drupal\nt8search\Form\NT8SearchFormBase');
    $build['form'] = $builtForm;

    return $build;
  }

}
