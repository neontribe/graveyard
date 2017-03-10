<?php

namespace Drupal\nt8search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
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

  public function __construct(
    NT8TabsRestService $nt8tabsio_tabs_service,
    NT8SearchService $nt8search_methods_service
  ) {
    $this->nt8tabsioTabsService = $nt8tabsio_tabs_service;
    $this->nt8searchMethodsService = $nt8search_methods_service;
  }

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


    //TODO: break these out into their own field definitions??
    $form['fromDate'] = [
      '#type' => 'date',
      '#title' => $this->t('Arrival Date'),
      '#default_value' => $this->nt8searchMethodsService->iak($query_items, 'fromDate') ?: '2016-11-07',
      '#date_date_format' => 'd-m-Y',
    ];

    $form['nights'] = [
      '#type' => 'select',
      '#title' => $this->t('Nights'),
      '#default_value' => $this->nt8searchMethodsService->iak($query_items, 'nights') ?: '',
      '#options' => array( // TODO: Move these options into configuation (NOT leave them hardcoded).
        '' => $this->t('Any'),
        '1' => $this->t('1'),
        '2' => $this->t('2'),
        '3' => $this->t('3'),
        '4' => $this->t('4'),
        '5' => $this->t('5'),
      ),
    ];

    $form['accommodates'] = [
      '#type' => 'select',
      '#title' => $this->t('People'),
      '#default_value' => $this->nt8searchMethodsService->iak($query_items, 'accommodates') ?: '',
      '#options' => array(
        '' => $this->t('Any'),
        '1' => $this->t('1'),
        '2' => $this->t('2'),
        '3' => $this->t('3'),
        '4' => $this->t('4'),
        '>5' => $this->t('5+'),
      ),

    ];

    $form['name'] = [
      '#type' => 'textfield',
      '#default_value' => $this->nt8searchMethodsService->iak($query_items, 'name') ?: '',
      '#title' => $this->t('Property Name/Reference'),
      '#maxlength' => 32,
      '#size' => 15
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
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $form_state->setRedirect('nt8search.nt8_search_form_base', $form_state->getValues());
  }

}
