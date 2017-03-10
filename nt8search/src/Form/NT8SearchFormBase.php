<?php

namespace Drupal\nt8search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\nt8tabsio\Service\NT8TabsRestService;

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
  public function __construct(
    NT8TabsRestService $nt8tabsio_tabs_service
  ) {
    $this->nt8tabsioTabsService = $nt8tabsio_tabs_service;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('nt8tabsio.tabs_service')
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
    $form['nt8_search_form_field_arrival_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Arrival Date'),
    ];
    $form['nt8_search_form_field_nights'] = [
      '#type' => 'select',
      '#title' => $this->t('Nights'),
      '#options' => array('1' => $this->t('1'), '2' => $this->t('2'), '3' => $this->t('3'), '4' => $this->t('4')),

    ];
    $form['nt8_search_form_field_people'] = [
      '#type' => 'select',
      '#title' => $this->t('People'),
      '#options' => array('1' => $this->t('1'), '2' => $this->t('2'), '3' => $this->t('3'), '4' => $this->t('4')),

    ];
    $form['nt8_search_form_field_property_name_reference'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Property Name/Reference'),
      '#maxlength' => 32,
      '#size' => 15
    ];
    $form['nt8_search_form_field_search'] = [
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
    // Display result.
    foreach ($form_state->getValues() as $key => $value) {
        drupal_set_message($key . ': ' . $value);
    }

  }

}
