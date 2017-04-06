<?php

namespace Drupal\nt8property\Service;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityInterface;
use Drupal\nt8tabsio\Service\NT8TabsRestService;

/**
 * Provides the methods necessary to manage properties in Neontabs.
 *
 * @author oliver@neontribe.co.uk
 */
class NT8PropertyService {
  protected $entityTypeManager;
  protected $entityQuery;
  protected $nt8RestService;

  /**
   * NT8PropertyService constructor.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $entityQuery
   *   Container injection.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   Container injection.
   * @param \Drupal\nt8tabsio\Service\NT8TabsRestService $nt8RestService
   *   Container injection.
   */
  public function __construct(
    QueryFactory $entityQuery,
    EntityTypeManager $entityTypeManager,
    NT8TabsRestService $nt8RestService
  ) {
    $this->entityQuery = $entityQuery;
    $this->entityTypeManager = $entityTypeManager;
    $this->nt8RestService = $nt8RestService;
  }

  /**
   * Load node objects from an array of property references.
   *
   * @param array $proprefs
   *   Property references.
   *
   * @return array
   *   Loaded property nodes.
   */
  public function loadNodesFromProprefs(array $proprefs) {
    $loadedNodes = [];

    foreach ($proprefs as $propref) {
      $nodes = $this->loadNodesFromPropref($propref);
      if (!isset($nodes)) {
        continue;
      }

      foreach ($nodes as $node) {
        $loadedNodes[$propref][] = $node;
      }
    }

    return $loadedNodes;
  }

  /**
   * Loads property nodes which have the same propref as the one provided.
   *
   * @param string $propref
   *   Property reference to query for.
   * @param bool $load
   *   True: Return loaded node objects.
   *   False: Make no attempt to load and return an array of NIDs.
   *
   * @return array|\Drupal\Core\Entity\EntityInterface[]|int|null
   *   An array of loaded property entities if the $load flag is true
   *   otherwise an array of NIDs.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function loadNodesFromPropref(string $propref, bool $load = TRUE) {
    // Get the nodes to update with this data.
    $nodeQuery = $this->entityQuery->get('node');
    $nodeStorage = $this->entityTypeManager->getStorage('node');
    $nids = $nodeQuery->condition('field_cottage_reference_code.value', $propref, '=')->execute();

    if (!$load) {
      return $nids;
    }

    $nodes = $nodeStorage->loadMultiple($nids);

    if (count($nodes) === 0) {
      \Drupal::logger('NT8PropertyService')->notice("Could not load a node for this propref: @propref", ['@propref' => print_r($propref, TRUE)]);
      $nodes = NULL;
    }

    return $nodes;
  }

  /**
   * Updates all matching nodes with data provided by the TABS api.
   *
   * Example Usage:
   *   $data = json_decode($api_property_data_response_string);
   *   updateNodeInstancesFromData($data);
   *
   * @param \stdClass $data
   *   Property data as returned from the API.
   *
   * @return array
   *   Returns an array of all of the successfully updated properties' NIDs.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateNodeInstancesFromData(\stdClass $data) {
    $updatedProperties = [];

    $nids = self::loadNodesFromPropref($data->propertyRef, FALSE);
    $updatedValues = self::generateUpdateArray($data, FALSE);

    if (count($nids) > 0) {
      $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);

      foreach ($nodes as $node) {
        $updated = $this->updateNodeInstanceFromData($updatedValues, $node);

        if ($updated) {
          $updatedProperties[] = $data->propertyRef;
          $node->save();
        }
      }
    }

    return $updatedProperties;
  }

  /*
   * @TODO Ship these out into a global set of helper functions.
   */

  /**
   * Retrieves a field value from a specified node.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   Loaded property node.
   * @param string $fieldName
   *   Field to load.
   * @param int $index
   *   Load $index of the field instance array.
   * @param string $keyname
   *   Load $keyname of the specified $index of the field instance array.
   *
   * @return mixed
   *   The field value as specified by the parameters.
   */
  public static function getNodeFieldValue(
    EntityInterface $node,
    string $fieldName,
    int $index = -1,
    string $keyname = 'value'
  ) {
    $field_instance = self::getNodeField($node, $fieldName);
    $field_instance_value = $field_instance->getValue();

    if ($index > -1) {
      $field_instance_value = $field_instance_value[$index][$keyname];
    }

    return $field_instance_value;
  }

  /**
   * Gets the field object instance for a specified field.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   Loaded property node entity.
   * @param string $fieldName
   *   Name of the field to load.
   *
   * @return \Drupal\Core\Field\FieldItemList|null
   *   Returns an instance of FieldItemList.
   */
  public static function getNodeField(EntityInterface $node, string $fieldName) {
    return $node->get($fieldName);
  }

  /**
   * Updates an EntityInterface from a provided array of updated values.
   *
   * @param array $updatedValues
   *   Array of updated values to be compared against.
   * @param \Drupal\Core\Entity\EntityInterface $nodeInstance
   *   Instance of the node which has the fields to compare against.
   *
   * @return bool
   *   Indicates that field(s) have been changed on the node.
   */
  public function updateNodeInstanceFromData(array $updatedValues, EntityInterface &$nodeInstance) {
    $updated = FALSE;

    // Compare for differences and update if a difference is found.
    foreach ($updatedValues as $updatedValueKey => $updatedValue) {
      $currentNodeField = self::getNodeFieldValue($nodeInstance, $updatedValueKey);

      $length_of_update_fields = count($updatedValue);

      $updateIndex = 0;

      // For each field on the current node.
      // Iterate through the child entries attached to the field.
      // This works for all fields even those with cardinality: 1.
      foreach ($currentNodeField as $index => $nodeFieldValue) {
        $comparisonUpdate = $updatedValue;

        // If the field has more than 1 entries.
        // Set the comparison to the value of the current entry.
        // We keep track of current entry by incrementing the `updateIndex` var.
        if ($length_of_update_fields > 1) {
          $comparisonUpdate = self::issetGet($updatedValue, $updateIndex++) ?: $updatedValue;
        }

        // Sometimes the data to compare is nested another level deep.
        // This retrieves it and lets us continue as if it were a flat array.
        $nestedComparison = self::issetGet($comparisonUpdate, 0);
        if ($nestedComparison && is_array($nestedComparison)) {
          $comparisonUpdate = $nestedComparison;
        }

        // Sort both arrays so the equality check below evaluates correctly.
        sort($comparisonUpdate);
        sort($nodeFieldValue);

        // Compare the two field entries for differences.
        $difference = ($comparisonUpdate == $nodeFieldValue);

        if ($difference == 0) {
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

  /**
   * Create a new node from API /property response data.
   *
   * @param \stdClass $data
   *   Property data as returned from the API.
   * @param bool $deleteExisting
   *   If this is set to true any existing nodes in the DB
   *   of the same propref as $data will be deleted.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The created property entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  public function createNodeInstanceFromData(\stdClass $data, bool $deleteExisting = FALSE) {
    if (isset($data->errorCode)) {
      throw new \Exception($data->errorCode);
    }

    $nodeStorage = $this->entityTypeManager->getStorage('node');

    if ($deleteExisting) {
      $nodeQuery = $this->entityQuery->get('node');
      $nids = $nodeQuery->condition('field_cottage_reference_code.value', $data->propertyRef, '=')->execute();

      if (count($nids) > 0) {
        // Delete any existing property entities which have already been loaded
        // in under the same propref.
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

  /**
   * Creates a property node populated with data returned from the API.
   *
   * @param string $propRef
   *   propRef to load into a property node.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The created property entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  public function createNodeInstanceFromPropref(string $propRef = "") {
    $data = $this->getPropertyFromApi($propRef);

    $created_node = NULL;
    if ($data && $data instanceof \stdClass) {
      $created_node = $this->createNodeInstanceFromData($data, TRUE);
    }

    return $created_node;
  }

  /**
   * Fetches a property from the TABS API under the provided propRef.
   *
   * @param string $propRef
   *   propRef to load from the API.
   *
   * @return \stdClass|mixed
   *   The JSON decoded API response.
   */
  public function getPropertyFromApi(string $propRef = "") {
    $_api_property_data = $this->nt8RestService->get("property/$propRef");
    $data = json_decode($_api_property_data);

    return $data;
  }

  /**
   * Checks if a value is set in an array and returns the value if is true.
   *
   * @param array $array
   *   Array to check.
   * @param mixed $key
   *   Key to access the passed in array.
   *
   * @return bool|mixed
   *   Returns value stored under $key in the array otherwise defaults to FALSE.
   */
  public static function issetGet(array $array, $key) {
    return isset($array[$key]) ? $array[$key] : FALSE;
  }

  /**
   * Strips null values from a provided array.
   *
   * TODO: This is technically a helper function and should be moved.
   *
   * @param array $array
   *   Array to check.
   *
   * @return array
   *   Stripped array.
   */
  public static function stripNullValues(array $array = []) {
    return array_filter($array, create_function('$value', 'return $value !== NULL;'));
  }

  /**
   * Generates the new array which is used to populate new entities.
   *
   * This is used both in the creation of entities and the updating of entities.
   * An API property data response object is passed in from which the array
   * is populated with data.
   *
   * @param \stdClass $data
   *   Property data as returned from the API.
   * @param bool $is_node
   *   Flag used to specify whether this method is being used to generate the
   *   definition for a new entity instance or as a node update comparison.
   *     We need this in order to strip some values out for comparisons.
   *
   * @return array
   *   Property definition array.
   */
  protected static function generateUpdateArray(\stdClass $data, bool $is_node = TRUE) {
    $brandcode = $data->brandCode;
    $brandcode_info = $data->brands->{$brandcode};
    $address = $data->address;
    $pricing = json_encode(
      $brandcode_info->pricing
    );

    // Placeholder Image Data.
    $config = \Drupal::config('nt8property.config');
    $placeholder_image_url = $config->get('property-teaser.placeholder-image.url');

    $image_data = [];
    $image_links = [];
    if (count($data->images) > 0) {
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
    else {
      $image_links[] = [
        'uri' => $placeholder_image_url,
        'title' => 'Placeholder Image',
        'options' => [],
      ];
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
        'value' => (string) $data->accommodates,
      ],
      'field_cottage_pets' => [
        'value' => (string) ((int) $data->pets),
      ],
      'field_cottage_bedrooms' => [
        'value' => (string) $data->bedrooms,
      ],
      'field_cottage_promote' => [
        'value' => (string) ((int) $data->promote),
      ],
      'field_cottage_rating' => [
        'value' => (string) $data->rating,
      ],
      'field_cottage_changeover_day' => [
        'value' => $data->changeOverDay,
      ],
      'field_cottage_pricing' => [
        'value' => $pricing,
      ],
      'field_cottage_coordinates' => [
        [
          'value' => (string) round($data->coordinates->latitude, 4),
        ],
        [
          'value' => (string) round($data->coordinates->longitude, 4),
        ],
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
      'field_cottage_featured_image' => self::issetGet($image_links, 0),
      'field_cottage_images' => $image_links,
    ];

    // If this isn't for a node discard the Drupal node specific keys.
    if (!$is_node) {
      unset($return_definition['type']);
      unset($return_definition['promote']);
      unset($return_definition['title']);
    }

    return $return_definition;
  }

}
