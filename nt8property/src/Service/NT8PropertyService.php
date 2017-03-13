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


  public function __construct(QueryFactory $entityQuery,
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

      foreach($nodes as $node) {
        $loadedNodes[$propref][] = $node;
      }
    }

    return $loadedNodes;
  }

  public function loadNodesFromPropref($propref) {
    // Get the nodes to update with this data.
    $nodeQuery = $this->entityQuery->get('node');
    $nodeStorage = $this->entityTypeManager->getStorage('node');
    $nids = $nodeQuery->condition('field_cottage_reference_code.value', $propref, '=')->execute();
    $nodes = $nodeStorage->loadMultiple($nids);

    return $nodes;
  }

  public function updateNodeInstancesFromData(\stdClass $data) {
    $updatedProperties = [];

    // Get the nodes to update with this data.
    $nodeQuery = $this->entityQuery->get('node');
    $nodeStorage = $this->entityTypeManager->getStorage('node');
    $nids = $nodeQuery->condition('field_cottage_reference_code.value', $data->propertyRef, '=')->execute();

    $updatedValues = self::generateUpdateArray($data);

    if(count($nids) > 0) {
      $nodes = $nodeStorage->loadMultiple($nids);

      // Just in case more than one exist.
      foreach ($nodes as $node) {
        $updated = $this->updateNodeInstanceFromData($updatedValues, $node);
        //TODO: refactor field logging.
        if($updated) {
          $fields = $node->getFields();
          $reference_field = self::iak($fields, 'field_cottage_reference_code');

          if(isset($reference_field)) {
            $field_value = $reference_field->getValue()[0]['value'];
            $updatedProperties[] = $field_value;
          }

          $node->save();
        }
      }
    }

    return $updatedProperties;
  }

  /**
   * @param array $updatedValues
   * @param $nodeInstance
   */
  public function updateNodeInstanceFromData(array $updatedValues, &$nodeInstance) {
    $currentNodeFields = $nodeInstance->getFields() ?: [];
    $updated = FALSE;


    // Compare for differences and update if there is one.
    foreach($updatedValues as $updatedValueKey => $updatedValue) {
      $currentNodeField = self::iak($currentNodeFields, $updatedValueKey);
      if($currentNodeField instanceof \Drupal\Core\Field\FieldItemList) {
        $currentNodeFieldValue = $currentNodeField->getValue();
      } else {
        \Drupal::logger('NT8PropertyService')->info('Failed to load field: @currentNodeField.', array('@currentNodeField' => $updatedValueKey));
        continue;
      }


      if(is_array($currentNodeFieldValue) && count($currentNodeFieldValue) > 1) {
        $index = 0;

        foreach($currentNodeFieldValue as $currentNodeFieldValueInc) {
          if(self::getFieldUpdateStatus($currentNodeFieldValueInc, self::iak($updatedValue, $index))) {
            $index++;
            continue;
          }

          $currentNodeField->setValue($updatedValue);
          $updated = TRUE;

          $index++;
        }
      } else {
        $field_data = self::iak($currentNodeFieldValue, 0);

        if(self::getFieldUpdateStatus($field_data, $updatedValue)) continue;

        $currentNodeField->setValue($updatedValue);
        $updated = TRUE;
      }
    }

    return $updated;
  }

  public function createNodeInstanceFromData(\stdClass $data, $deleteExisting = FALSE) {
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

  // If a key is set in the provided array return the value or false if it isn't. (helper function).
  public static function iak($array, $key = '') {
    return isset($array[$key]) ? $array[$key] : FALSE;
  }

  // Strips null values from the array.
  public static function stripNullValues(array $array = []) {
    return array_filter($array, create_function('$value', 'return $value !== NULL;'));
  }

  protected static function getFieldUpdateStatus($currentNodeField, $updatedValue) {
    $fields_to_check = [
      'target_id',
      'value',
      'uri',
      'country_code',
      'administrative_area',
      'locality',
      'postal_code',
      'address_line1',
      'address_line2',
    ];

    if(is_array($currentNodeField)) {
      $currentNodeField = self::stripNullValues($currentNodeField);
    }

    $not_changed = FALSE;
    foreach($fields_to_check as $current_field) {
      $current_field_value = self::iak($currentNodeField, $current_field);
      $current_updated_value = $updatedValue;
      if(is_array($updatedValue)) {
        $current_updated_value = self::iak($updatedValue, $current_field);
      }


      $found_one = !(($current_field_value === false) || ($current_updated_value === false));

      if($current_field_value === $current_updated_value && $found_one) {
        $not_changed = TRUE;
      }
    }

    return $not_changed;
  }

  protected static function generateUpdateArray(\stdClass $data) {
    $brandcode = $data->brandCode;
    $brandcode_info = $data->brands->{$brandcode};

    $address = $data->address;

    $pricing = json_encode(
      $brandcode_info->pricing
    );

    $image_data = [];
    if(count($data->images) > 0) {
      foreach ($data->images as $image) {
        $image_data[] = json_encode($image);
      }
    }

    return [
      'type' => 'property',
      'promote' => '0',
      'title' => "$data->name",
      'field_cottage_name' => $data->name,
      'field_cottage_brandcode' => $brandcode,
      'field_cottage_slug' => $data->slug,
      'field_cottage_ownercode' => $data->ownerCode,
      'field_cottage_url' => $data->url,
      'field_cottage_teaser_description' => $brandcode_info->teaser,
      'field_cottage_reference_code' => $data->propertyRef,
      'field_cottage_booking' => $data->booking,
      'field_cottage_accommodates' => (string) $data->accommodates,
      'field_cottage_pets' => (string) ((int) $data->pets),
      'field_cottage_bedrooms' => (string) $data->bedrooms,
      'field_cottage_promote' => (string) ((int) $data->promote),
      'field_cottage_rating' => (string) $data->rating,
      'field_cottage_changeover_day' => $data->changeOverDay,
      'field_cottage_pricing' => $pricing,
      'field_cottage_coordinates' => [
        (string) round($data->coordinates->latitude, 4),
        (string) round($data->coordinates->longitude, 4),
      ],
      'field_cottage_address' => [
        'address_line1' => $address->addr1,
        'address_line2' => $address->addr2,
        'locality' => $address->town,
        'administrative_area' => $address->county,
        'postal_code' => $address->postcode,
        'country_code' => $address->country,
      ],
      'field_cottage_image_info' => $image_data,
    ];
  }
}