<?php

namespace Drupal\nt8property\Form;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use \GuzzleHttp\Client;

use Drupal\nt8property\Service\NT8PropertyService;
use Drupal\nt8tabsio\Service\NT8TabsRestService;

use Symfony\Component\DependencyInjection\ContainerInterface;
/**
 * Implements an example form.
 */
class NT8PropertyFormBase extends FormBase {

  protected $httpClient;
  protected $propertyMethods;
  protected $nt8RestService;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nt8property_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['actions']['#type'] = 'actions';

    $form['nt8_tabsio'] = [
      '#type' => 'container',
      'title' => [
        '#type' => 'page_title',
        '#title' => 'NT8 Tabs IO (API)'
      ]
    ];

    /*
     * Single loading.
     */
    $form['nt8_tabsio']['single'] = [
      '#type' => 'fieldset',
      '#title' => 'Single Property Options',
    ];

    $form['nt8_tabsio']['single']['load_single_prop'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Load a single property into drupal (from propref_brandcode).'),
      '#default_value' => 'H610_ZZ'
    ];

    $form['nt8_tabsio']['single']['modify_replace_single'] = [
      '#type' => 'radios',
      '#title' => $this->t('Modify Existing or Replace'),
      '#default_value' => 0,
      '#options' => [ 0 => $this->t('Modify'), 1 => $this->t('Replace') ],
    ];

    $form['nt8_tabsio']['single']['actions']['#type'] = 'actions';
    $form['nt8_tabsio']['single']['actions']['submit_property'] = [
      '#type' => 'submit',
      '#name' => 'submit_property',
      '#value' => $this->t('Load Property'),
      '#submit' => [ [ $this, 'loadProperty' ] ],
    ];

    /*
     * Batch loading.
     */
    $form['nt8_tabsio']['batch'] = [
      '#type' => 'fieldset',
      '#title' => 'Batch Property Options',
    ];

    $form['nt8_tabsio']['batch']['listed_batch'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Specify a list of proprefs to batch load into Drupal.'),
      '#default_value' => 'H610_ZZ, V503_ZZ',
    ];

    $form['nt8_tabsio']['batch']['batch_size'] = [
      '#type' => 'select',
      '#title' => $this->t('Batch Chunk Size (number of properties to load at a time)'),
      '#options' => [
        1 => 1,
        2 => 2,
        3 => 4,
        4 => 8,
        5 => 16,
        6 => 32,
        7 => 64,
      ],
    ];

    $form['nt8_tabsio']['batch']['modify_replace_batch'] = [
      '#type' => 'radios',
      '#title' => $this->t('Modify Existing or Replace'),
      '#default_value' => 1,
      '#options' => [ 0 => $this->t('Modify'), 1 => $this->t('Replace') ],
    ];

    $form['nt8_tabsio']['batch']['actions']['#type'] = 'actions';
    $form['nt8_tabsio']['batch']['actions']['submit_property_batch_listed'] = [
      '#type' => 'submit',
      '#name' => 'submit_property_batch_all',
      '#value' => $this->t('Batch Load Listed Properties'),
      '#submit' => [ [ $this, 'loadPropertyBatchAll' ] ],
    ];

    $form['nt8_tabsio']['batch']['actions']['submit_property_batch_all'] = [
      '#type' => 'submit',
      '#name' => 'submit_property_batch_all',
      '#value' => $this->t('Batch Load All Properties'),
      '#submit' => [ [ $this, 'loadPropertyBatchAll' ] ],
    ];

    /*
     * Fixture loading.
     */

    $form['fixtures'] = [
      '#type' => 'container',
      'title' => [
        '#type' => 'page_title',
        '#title' => 'Fixtures'
      ]
    ];

    $form['fixtures']['single_fixture'] = [
      '#type' => 'fieldset',
      '#title' => 'Single Fixture Options',
    ];

    $form['fixtures']['single_fixture']['load_single_prop_fixture'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Load a single property fixture into drupal (from propref_brandcode).'),
      '#default_value' => 'H610_ZZ'
    ];

    $form['fixtures']['single_fixture']['actions']['#type'] = 'actions';
    $form['fixtures']['single_fixture']['actions']['submit_fixture'] = [
      '#type' => 'submit',
      '#name' => 'submit_property_fixture',
      '#value' => $this->t('Load Property'),
      '#submit' => [ [ $this, 'loadFixture' ] ],
    ];

    return $form;
  }

  public function __construct(\GuzzleHttp\Client $httpClient,
                              NT8PropertyService $propertyMethods,
                              NT8TabsRestService $nt8RestService) {
    $this->httpClient = $httpClient;
    $this->propertyMethods = $propertyMethods;
    $this->nt8RestService = $nt8RestService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client'),
      $container->get('nt8property.property_methods'),
      $container->get('nt8tabsio.tabs_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function loadProperty(array &$form, FormStateInterface $formState) {
    $propref = $form['nt8_tabsio']['single']['load_single_prop']['#value'] ?: '';

    $_api_property_data = $this->nt8RestService->get("property/$propref");
    $data = json_decode($_api_property_data);

    if($data) {
      $this->propertyMethods->createNodeInstanceFromData($data, TRUE);
      drupal_set_message("New Property Node: [Name: $data->name, Reference: $data->propertyRef] Successfully Created Using Data From The API.");
    } else {
      drupal_set_message("API Call unsuccessful: $_api_property_data");
    }
  }

  public function loadPropertyBatchAll(array &$form, FormStateInterface $formState) {
    $batch_size = $form['nt8_tabsio']['batch']['batch_size']['#value'] ?: 6;

    // 0 => 'Modify'
    // 1 => 'Replace'
    $modify_replace = $form['nt8_tabsio']['batch']['modify_replace_batch']['#value'];

    $batchSizeList = [
      1 => 1,
      2 => 2,
      3 => 4,
      4 => 8,
      5 => 16,
      6 => 32,
      7 => 64,
    ];
    // Get list of properties to reload.
    $per_page = $batchSizeList[$batch_size];

    // Get page count.
    $first_page = $this->nt8RestService->get("property", ["page" => 1, "pageSize" => 1]);

    $first_page = json_decode($first_page);

    $search_instance_id = $first_page->searchId;
    $total_results = $first_page->totalResults;

    $batch = [
      'title' => t('Loading all properties from API.'),
      'operations' => [],
      'progress_message' => t('Processed @current out of @total.'),
      'finished' => '\Drupal\nt8property\Batch\NT8PropertyBatch::propertyBatchLoadFinishedCallback',
    ];

    $pages = ceil($total_results / $per_page);
    for ($page_counter = 1; $page_counter < $pages; $page_counter++) {
      $batch["operations"][] = [
        '\Drupal\nt8property\Batch\NT8PropertyBatch::propertyBatchLoadCallback',
        [
          $page_counter,
          $per_page,
          $search_instance_id,
          $modify_replace,
        ],
      ];
    }

    batch_set($batch);
  }

  /**
   * {@inheritdoc}
   */
  public function loadFixture(array &$form, FormStateInterface $form_state) {
    $propref = $form['fixtures']['load_single_prop_fixture']['#value'];

    $req_path = Url::fromRoute('property.getFixture', ['propRef' => $propref], ['absolute' => TRUE])->toString();

    $response = $this->httpClient->get($req_path, []);
    $data = json_decode($response->getBody());

    $this->propertyMethods->createNodeInstanceFromData($data, TRUE);

    drupal_set_message("New Property Node: [Name: $data->name, Reference: $data->propertyRef] Successfully Created Using The Specified Fixture Data.");
  }
}
