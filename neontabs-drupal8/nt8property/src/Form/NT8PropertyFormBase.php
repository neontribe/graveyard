<?php

namespace Drupal\nt8property\Form;

use Drupal\nt8property\Batch\NT8PropertyBatch;
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
      '#title' => 'Single/Multiple Property Options',
    ];

    $form['nt8_tabsio']['single']['load_single_prop'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Load comma delimited properties into drupal.'),
      '#description' => $this->t('Example: V419, H610_ZZ, Y121, G288'),
      '#default_value' => '',
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
      '#value' => $this->t('Load Properties'),
      '#submit' => [[$this, 'loadProperties']],
    ];

    /*
     * Batch loading.
     */
    $form['nt8_tabsio']['batch'] = [
      '#type' => 'fieldset',
      '#title' => 'Batch Property Options',
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
  public function __construct(
    Client $httpClient,
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
    if (isset($limit_attrs)) {
      $new_arr = array_map(
        'trim',
        explode(',', $limit_attrs)
      ) ?: [];
    }

    $attrib_data = $this->propertyMethods->getAttributeDataFromTabs($new_arr);
    $attrib_update_status = $this->propertyMethods->createAttributesFromTabs($attrib_data);

    drupal_set_message("Updated The Following Attributes: ${attrib_update_status}");
  }

  /**
   * Runs the necessary methods in the property service to setup the taxonomy.
   *
   * @see ::createAreaLocTermsFromTabs
   */
  public function setupAreaLocTaxonomy(array &$form, FormStateInterface $formState) {
    $limit_arealocs = $formState->getValue('attribute_code_limit') ?: NULL;

    $new_arr = [];
    if (isset($limit_arealocs)) {
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
  public function loadProperties(array &$form, FormStateInterface $formState) {
    $proprefs = $formState->getValue('load_single_prop') ?: '';
    $modify_replace = $formState->getValue('modify_replace_single') ?: 0;

    $new_arr = [];
    if (isset($proprefs)) {
      $new_arr = array_map(
        function($curr_ref) {
          $curr_ref = trim($curr_ref);
          $curr_ref = $this->nt8RestService->splitPropref($curr_ref)[0] ?? '';

          return $curr_ref;
        },
        explode(',', $proprefs)
      );
    }

    $multiple_property_data = $this->propertyMethods->getPropertiesFromApi($new_arr);

    $nodes_updated = '';

    if (isset($multiple_property_data) && count($multiple_property_data) > 0) {
      foreach($multiple_property_data as $key => $property_data) {
        $data__propref = $property_data->propertyRef;

        switch ($modify_replace) {
          case 0:
            $this->propertyMethods->updateNodeInstancesFromData($property_data);
            $nodes_updated .= ", $data__propref";
            break;
          default:
            $this->propertyMethods->createNodeInstanceFromData($property_data, TRUE);
            $nodes_updated .= ", $data__propref";
        }
      }

      if($modify_replace) {
        drupal_set_message("Created the following property nodes: $nodes_updated");
      } else {
        drupal_set_message("Updated the following property nodes: $nodes_updated");
      }

    } else {
      drupal_set_message('No data provided by API for your search term.');
    }
  }

  /**
   * Initiates a batch method to load all properties.
   */
  public function loadPropertyBatchAll(array &$form, FormStateInterface $formState) {
    $batch_size = $formState->getValue('batch_size') ?: 6;
    $modify_replace = $formState->getValue('modify_replace_batch') ?: 0;

    NT8PropertyBatch::propertyBatchLoad($batch_size, $modify_replace);
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
