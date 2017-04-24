<?php

namespace Drupal\nt8search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\nt8tabsio\Service\NT8TabsRestService;
use Drupal\nt8search\Service\NT8SearchService;

/**
 * Class NT8SearchFormBase.
 *
 * @package Drupal\nt8search\Form
 */
class NT8SearchFormBase extends FormBase {

  /**
   * Drupal\nt8tabsio\Service\NT8TabsRestService definition.
   *
   * @var \Drupal\nt8tabsio\Service\NT8TabsRestService
   */
  protected $nt8tabsioTabsService;

  /**
   * Drupal\nt8search\Service\NT8SearchService definition.
   *
   * @var \Drupal\nt8search\Service\NT8SearchService
   */
  protected $nt8searchMethodsService;

  /**
   * NT8SearchFormBase constructor.
   *
   * @param \Drupal\nt8tabsio\Service\NT8TabsRestService $nt8tabsio_tabs_service
   *   Instance of the tabs rest service.
   * @param \Drupal\nt8search\Service\NT8SearchService $nt8search_methods_service
   *   Instance of the tabs search service.
   */
  public function __construct(
    NT8TabsRestService $nt8tabsio_tabs_service,
    NT8SearchService $nt8search_methods_service
  ) {
    $this->nt8tabsioTabsService = $nt8tabsio_tabs_service;

    // @TODO: We only include this service for access to the helper function.
    $this->nt8searchMethodsService = $nt8search_methods_service;
  }

  /**
   * Drupal container instantiation wrapper function.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Container used for dependency injection via Symfony and .service.yml.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('nt8tabsio.tabs_service'),
      $container->get('nt8search.methods')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nt8_search_form_base';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $query_items = \Drupal::request()->query->all();

    $form['#cache'] = ['max-age' => 0];

    // TODO: break these out into their own field definitions??
    $form['fromDate'] = [
      '#type' => 'date',
      '#title' => $this->t('Arrival Date'),
      '#default_value' => $this->nt8searchMethodsService->issetGet($query_items, 'fromDate') ?: date('Y-m-d'),
      '#date_date_format' => 'd-m-Y',
    ];

    $form['nights'] = [
      '#type' => 'select',
      '#title' => $this->t('Nights'),

    // TODO: Move these options into configuation (NOT leave them hardcoded).
      '#options' => [
        '' => $this->t('Any'),
        '1' => $this->t('1'),
        '2' => $this->t('2'),
        '3' => $this->t('3'),
        '4' => $this->t('4'),
        '5' => $this->t('5'),
      ],
      '#default_value' => $this->nt8searchMethodsService->issetGet($query_items, 'nights') ?: '',
    ];

    $form['accommodates'] = [
      '#type' => 'select',
      '#title' => $this->t('People'),
      '#options' => [
        '' => $this->t('Any'),
        '1' => $this->t('1'),
        '2' => $this->t('2'),
        '3' => $this->t('3'),
        '4' => $this->t('4'),
        '>5' => $this->t('5+'),
      ],
      '#default_value' => $this->nt8searchMethodsService->issetGet($query_items, 'accommodates') ?: '',
    ];

    $form['name'] = [
      '#type' => 'textfield',
      '#default_value' => $this->nt8searchMethodsService->issetGet($query_items, 'name') ?: '',
      '#title' => $this->t('Property Name/Reference'),
      '#maxlength' => 32,
      '#size' => 15,
    ];

    $form['search'] = [
      '#type' => 'submit',
      '#title' => $this->t('Search'),
      '#value' => $this->t('Search'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $form_state->setRedirect('nt8search.nt8_search_form_base', $form_state->getValues());
  }

}
