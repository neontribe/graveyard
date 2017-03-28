<?php

namespace Drupal\nt8property\Service;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\Query\QueryFactory;

use Drupal\nt8tabsio\Service\NT8TabsRestService;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Description of NT8PropertyService
 *
 * @author oliver@neontribe.co.uk
 */
class NT8PropertyService {
  protected $entityTypeManager;
  protected $entityQuery;
  protected $nt8RestService;


  public function __construct(
    QueryFactory $entityQuery,
    EntityTypeManager $entityTypeManager,
    NT8TabsRestService $nt8RestService
  ) {
    $this->entityQuery = $entityQuery;
    $this->entityTypeManager = $entityTypeManager;
    $this->nt8RestService = $nt8RestService;
  }

  public function loadNodesFromProprefs(array $proprefs) {
    $loadedNodes = [];

    foreach($proprefs as $propref) {
      $nodes = $this->loadNodesFromPropref($propref);
      if(!isset($nodes)) continue;

      foreach($nodes as $node) {
        $loadedNodes[$propref][] = $node;
      }
    }

    return $loadedNodes;
  }

  public function loadNodesFromPropref($propref, $load = TRUE) {
    // Get the nodes to update with this data.
    $nodeQuery = $this->entityQuery->get('node');
    $nodeStorage = $this->entityTypeManager->getStorage('node');
    $nids = $nodeQuery->condition('field_cottage_reference_code.value', $propref, '=')->execute();

    if(!$load) return $nids;

    $nodes = $nodeStorage->loadMultiple($nids);

    if(count($nodes) === 0) {
      \Drupal::logger('NT8PropertyService')->notice("Could not load a node for this propref: @propref", ['@propref' => print_r($propref, TRUE)]);
      $nodes = NULL;
    }

    return $nodes;
  }

  public function updateNodeInstancesFromData(\stdClass $data) {
    $updatedProperties = [];

    $nids = self::loadNodesFromPropref($data->propertyRef, FALSE);
    $updatedValues = self::generateUpdateArray($data, FALSE);

    if(count($nids) > 0) {
      $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);

      foreach ($nodes as $node) {
        $updated = $this->updateNodeInstanceFromData($updatedValues, $node);

        if($updated) {
          $updatedProperties[] = self::getNodeFieldValue($node, 'field_cottage_reference_code', 0);
          $node->save();
        }
      }
    }

    return $updatedProperties;
  }

  /*-----------@todo Ship these out into a global set of helper functions----------------*/
  public static function getNodeFieldValue($node, $fieldName, $index = -1, $keyname = 'value') {
    $field_instance = $node->get($fieldName)->getValue();
    $field_value = $field_instance;

    if($index > -1) {
      $field_value = $field_instance[$index][$keyname];
    }

    return $field_value;
  }

  public static function getNodeField($node, $fieldName) {
    return $node->get($fieldName);
  }
  /*---------------------------------------------------------------------------------------*/

  /**
   * @param array $updatedValues
   * @param $nodeInstance
   */
  public function updateNodeInstanceFromData(array $updatedValues, &$nodeInstance) {
    $updated = FALSE;

    // Compare for differences and update if a difference is found.
    foreach($updatedValues as $updatedValueKey => $updatedValue) {
      $currentNodeField = self::getNodeFieldValue($nodeInstance, $updatedValueKey);

      $length_of_update_fields = count($updatedValue);

      $updateIndex = 0;

      // For each field on the current node iterate through the child entries attached to the field.
      // This works for all fields even those with cardinality: 1.
      foreach($currentNodeField as $index => $nodeFieldValue) {
        $comparisonUpdate = $updatedValue;

        // If the field has more than 1 entries set the comparison to the value of the current entry.
        // We keep track of current entry by incrementing the `updateIndex` counter.
        if($length_of_update_fields > 1) {
          $comparisonUpdate = self::isset($updatedValue, $updateIndex++) ?: $updatedValue;
        }

        // Sometimes the data to compare is nested another level deep.
        // This retrieves it and lets the program continue as if it were a flat array.
        $nestedComparison = self::isset($comparisonUpdate, 0);
        if($nestedComparison && is_array($nestedComparison)) {
          $comparisonUpdate = $nestedComparison;
        }

        // Sort both arrays so the equality check below evaluates correctly.
        sort($comparisonUpdate);
        sort($nodeFieldValue);

        // Compare the two field entries for differences.
        $difference = ($comparisonUpdate == $nodeFieldValue);

        if($difference == 0) {
          // If a difference is found update the whole field entry.
          $fieldRef = self::getNodeField($nodeInstance, $updatedValueKey);
          $fieldRef->setValue($updatedValue);

          // We should only save if $updated is equal to TRUE.
          $updated = TRUE;
        }
      }
    }

    return $updated;
  }

  public function createNodeInstanceFromData(\stdClass $data, $deleteExisting = FALSE) {
    if(isset($data->errorCode)) {
      throw new \Exception($data->errorCode);
    }

    if($deleteExisting) {
      $nodeQuery = $this->entityQuery->get('node');
      $nodeStorage = $this->entityTypeManager->getStorage('node');
      $nids = $nodeQuery->condition('field_cottage_reference_code.value', $data->propertyRef, '=')->execute();

      if(count($nids) > 0) {
        // Delete any existing property entities which have already been loaded in under the same propref.
        $nodes = $nodeStorage->loadMultiple($nids);
        $nodeStorage->delete($nodes);
      }
    }

    $creationArray = self::generateUpdateArray($data);

    // Use the entity manager.
    $node = $nodeStorage->create($creationArray);
    $node->enforceIsNew();
    $node->save();

    return $node;
  }

  public function createNodeInstanceFromPropref($propRef = "") {
    $data = $this->getPropertyFromAPI($propRef);

    $created_node = NULL;
    if($data && $data instanceof \stdClass) {
      $created_node = $this->createNodeInstanceFromData($data, TRUE);
    }

    return $created_node;
  }

  public function getPropertyFromAPI($propRef = "") {
    $_api_property_data = $this->nt8RestService->get("property/$propRef");
    $data = json_decode($_api_property_data);

    return $data;
  }

  // If a key is set in the provided array return the value or false if it isn't. (helper function).
  public static function isset($array, $key = '') {
    return isset($array[$key]) ? $array[$key] : FALSE;
  }

  // Strips null values from the array.
  public static function stripNullValues(array $array = []) {
    return array_filter($array, create_function('$value', 'return $value !== NULL;'));
  }

  protected static function generateUpdateArray(\stdClass $data, bool $is_node = TRUE) {
    $brandcode = $data->brandCode;
    $brandcode_info = $data->brands->{$brandcode};
    $address = $data->address;
    $pricing = json_encode(
      $brandcode_info->pricing
    );

    $image_data = [];
    $image_links = [];
    if(count($data->images) > 0) {
      foreach ($data->images as $image) {
        $image_data[] = [
          'value' => json_encode($image),
        ];

        $image_links[] = [
          'uri' => $image->url,
          'title' => $image->alt,
          'options' => [],
        ];
      }
    }

    $return_definition = [
      'type' => 'property',
      'promote' => '0',
      'title' => "$data->name",
      'field_cottage_name' => [
        'value' => $data->name,
      ],
      'field_cottage_brandcode' => [
        'value' => $brandcode,
      ],
      'field_cottage_slug' => [
        'value' => $data->slug,
      ],
      'field_cottage_ownercode' => [
        'value' => $data->ownerCode,
      ],
      'field_cottage_url' => [
        'uri' => $data->url,
        'title' => $data->name,
        'options' => [],
      ],
      'field_cottage_teaser_description' => [
        'value' => $brandcode_info->teaser,
        'format' => NULL,
      ],
      'field_cottage_reference_code' => [
        'value' => $data->propertyRef,
      ],
      'field_cottage_booking' => [
        'uri' => $data->booking,
        'title' => 'Booking',
        'options' => [],
      ],
      'field_cottage_accommodates' => [
        'value' => (string) $data->accommodates
      ],
      'field_cottage_pets' => [
        'value' => (string) ((int) $data->pets)
      ],
      'field_cottage_bedrooms' => [
        'value' => (string) $data->bedrooms
      ],
      'field_cottage_promote' => [
        'value' => (string) ((int) $data->promote),
      ],
      'field_cottage_rating' => [
        'value' => (string) $data->rating
      ],
      'field_cottage_changeover_day' => [
        'value' => $data->changeOverDay
      ],
      'field_cottage_pricing' => [
        'value' => $pricing,
      ],
      'field_cottage_coordinates' => [
        [
          'value' => (string) round($data->coordinates->latitude, 4)
        ],
        [
          'value' => (string) round($data->coordinates->longitude, 4),
        ]
      ],
      'field_cottage_address' => [
        'address_line1' => $address->addr1,
        'address_line2' => $address->addr2,
        'locality' => $address->town,
        'administrative_area' => $address->county,
        'postal_code' => $address->postcode,
        'country_code' => $address->country,
        'langcode' => NULL,
        'dependent_locality' => NULL,
        'sorting_code' => NULL,
        'organization' => NULL,
        'given_name' => NULL,
        'additional_name' => NULL,
        'family_name' => NULL,
      ],
      'field_cottage_image_info' => $image_data,
      'field_cottage_featured_image' => self::isset($image_links, 0) ?: [
        'uri' => '',
        'title' => '',
        'options' => [],
      ],
      'field_cottage_images' => $image_links,
    ];

    // If this isn't for a node discard the Drupal node specific keys.
    if(!$is_node) {
      unset($return_definition['type']);
      unset($return_definition['promote']);
      unset($return_definition['title']);
    }

    return $return_definition;
  }
}