<?php

namespace Drupal\nt8property\Form;

use GuzzleHttp\Client;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

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
        '#title' => 'NT8 Tabs IO (API)',
      ],
    ];

    /*
     * Vocabs
     */
    $form['nt8_tabsio']['vocab'] = [
      '#type' => 'fieldset',
      '#title' => 'Taxonomy / Vocabulary Options',
    ];

    $form['nt8_tabsio']['vocab']['attribute_code_limit'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Specify which attributes to load/update (leave blank for all).'),
      '#placeholder' => 'ATTR01, ATTR02',
    ];

    $form['nt8_tabsio']['vocab']['populate_attribs'] = [
      '#type' => 'submit',
      '#name' => 'submit_property',
      '#value' => $this->t('Populate Property Attribute Taxonomy'),
      '#submit' => [[$this, 'setupAttributeTaxonomy']],
    ];

    $form['nt8_tabsio']['vocab']['populate_locs'] = [
      '#type' => 'submit',
      '#name' => 'submit_property_arealoc',
      '#value' => $this->t('Populate Property Area Location Taxonomy'),
      '#submit' => [[$this, 'setupAreaLocTaxonomy']],
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
      '#default_value' => 'H610_ZZ',
    ];

    $form['nt8_tabsio']['single']['modify_replace_single'] = [
      '#type' => 'radios',
      '#title' => $this->t('Modify Existing or Replace'),
      '#default_value' => 0,
      '#options' => [0 => $this->t('Modify'), 1 => $this->t('Replace')],
    ];

    $form['nt8_tabsio']['single']['actions']['#type'] = 'actions';
    $form['nt8_tabsio']['single']['actions']['submit_property'] = [
      '#type' => 'submit',
      '#name' => 'submit_property',
      '#value' => $this->t('Load Property'),
      '#submit' => [[$this, 'loadProperty']],
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
      '#default_value' => 8,
      '#options' => [
        1 => 1,
        2 => 2,
        3 => 4,
        4 => 8,
        5 => 16,
        6 => 32,
        7 => 64,
        8 => 128,
        9 => 256,
      ],
    ];

    $form['nt8_tabsio']['batch']['modify_replace_batch'] = [
      '#type' => 'radios',
      '#title' => $this->t('Modify Existing or Replace'),
      '#default_value' => 0,
      '#options' => [0 => $this->t('Modify'), 1 => $this->t('Replace')],
    ];

    $form['nt8_tabsio']['batch']['actions']['#type'] = 'actions';

    // @TODO: Implement this submit button feature.
    $form['nt8_tabsio']['batch']['actions']['submit_property_batch_listed'] = [
      '#type' => 'submit',
      '#name' => 'submit_property_batch_listed',
      '#disabled' => TRUE,
      '#value' => $this->t('Batch Load Listed Properties'),
      '#submit' => [[$this, 'loadPropertyBatchAll']],
    ];

    $form['nt8_tabsio']['batch']['actions']['submit_property_batch_all'] = [
      '#type' => 'submit',
      '#name' => 'submit_property_batch_all',
      '#value' => $this->t('Batch Load All Properties'),
      '#submit' => [[$this, 'loadPropertyBatchAll']],
    ];

    /*
     * Fixture loading.
     */

    $form['fixtures'] = [
      '#type' => 'container',
      'title' => [
        '#type' => 'page_title',
        '#title' => 'Fixtures',
      ],
    ];

    $form['fixtures']['single_fixture'] = [
      '#type' => 'fieldset',
      '#title' => 'Single Fixture Options',
    ];

    $form['fixtures']['single_fixture']['load_single_prop_fixture'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Load a single property fixture into drupal (from propref_brandcode).'),
      '#default_value' => 'H610_ZZ',
    ];

    $form['fixtures']['single_fixture']['actions']['#type'] = 'actions';
    $form['fixtures']['single_fixture']['actions']['submit_fixture'] = [
      '#type' => 'submit',
      '#name' => 'submit_property_fixture',
      '#value' => $this->t('Load Property'),
      '#submit' => [[$this, 'loadFixture']],
    ];

    return $form;
  }

  /**
   * NT8PropertyFormBase class construct method.
   */
  public function __construct(Client $httpClient,
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
  public function setupAttributeTaxonomy(array &$form, FormStateInterface $formState) {
    $limit_attrs = $formState->getValue('attribute_code_limit') ?: NULL;
    $new_arr = [];
    if(isset($limit_attrs)) {
      $new_arr = array_map(
        'trim',
        explode(',', $limit_attrs)
      ) ?: [];
    }

    $attrib_data = $this->propertyMethods->getAttributeDataFromTabs($new_arr);
    $attrib_update_status = $this->propertyMethods->createAttributesFromTabs($attrib_data);

    drupal_set_message("Updated The Following Attributes: ${attrib_update_status}");
  }

  public function setupAreaLocTaxonomy(array &$form, FormStateInterface $formState) {
    $limit_arealocs = $formState->getValue('attribute_code_limit') ?: NULL;

    $new_arr = [];
    if(isset($limit_arealocs)) {
      $new_arr = array_map(
        'trim',
        explode(',', $limit_arealocs)
      ) ?: [];
    }

    $locData = $this->propertyMethods->getAreaLocationDataFromTabs($new_arr);
    $arealocDataUpdateStatus = $this->propertyMethods->createAreaLocTermsFromTabs($locData);

    drupal_set_message(print_r('Updated Areas: ' . $arealocDataUpdateStatus[0], TRUE));
    drupal_set_message(print_r('Updated Locations: ' . $arealocDataUpdateStatus[1], TRUE));
  }

  /**
   * {@inheritdoc}
   */
  public function loadProperty(array &$form, FormStateInterface $formState) {
    $propref = $formState->getValue('load_single_prop') ?: '';

    $node = $this->propertyMethods->createNodeInstanceFromPropref($propref);

    if (isset($node)) {
      $node_name = $node->get('field_cottage_name')->getValue()[0]['value'];
      $node_ref = $node->get('field_cottage_reference_code')->getValue()[0]['value'];

      drupal_set_message("New Property Node: [Name: $node_name, Reference: $node_ref] Successfully Created Using Data From The API.");
    }
    else {
      drupal_set_message("Node creation unsuccessful: $node");
    }
  }

  /**
   * Initiates a batch method to load all properties.
   */
  public function loadPropertyBatchAll(array &$form, FormStateInterface $formState) {
    $batch_mode = $formState->getTriggeringElement()['#name'];
    $batch_size = $formState->getValue('batch_size') ?: 6;

    // 0 => 'Modify'
    // 1 => 'Replace'.
    $modify_replace = $formState->getValue('modify_replace_batch') ?: 0;

    $batchSizeList = [
      1 => 1,
      2 => 2,
      3 => 4,
      4 => 8,
      5 => 16,
      6 => 32,
      7 => 64,
      8 => 128,
      9 => 256,
    ];
    // Get list of properties to reload.
    $per_page = $batchSizeList[$batch_size];

    // Get page count.
    $first_page = $this->nt8RestService->get("property", ["page" => 1, "pageSize" => $per_page]);

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
    $last_page = $total_results - ($per_page * ($pages - 1));

    for ($page_counter = 0; $page_counter < $pages; $page_counter++) {
      $batch["operations"][] = [
        '\Drupal\nt8property\Batch\NT8PropertyBatch::propertyBatchLoadCallback',
        [
          $page_counter,
          [
            'per_page' => $per_page,
            'last_page' => $last_page,
            'pages' => $pages,
          ],
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
    $propref = $form['fixtures']['single_fixture']['load_single_prop_fixture']['#value'];

    $session_manager = \Drupal::service('session_manager');

    $session_name = $session_manager->getName();
    $session_id = $session_manager->getID();

    $req_path = Url::fromRoute('property.getFixture', ['propRef' => $propref], ['absolute' => TRUE]);

    $response = \Drupal::httpClient()->get($req_path->toString(), [
      'headers' => [
        'Cookie' => $session_name . '=' . $session_id,
      ],
    ]);

    $data = json_decode($response->getBody());
    $this->propertyMethods->createNodeInstanceFromData($data, TRUE);

    drupal_set_message("New Property Node: [Name: $data->name, Reference: $data->propertyRef] Successfully Created Using The Specified Fixture Data.");
  }

}
