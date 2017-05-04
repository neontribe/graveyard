<?php

namespace Drupal\nt8property\Service;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityInterface;
use Drupal\nt8tabsio\Service\NT8TabsRestService;

use Drupal\taxonomy\Entity\Term;

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
   * @param mixed $entityQuery
   *   Container injection.
   * @param mixed $entityTypeManager
   *   Container injection.
   * @param \Drupal\nt8tabsio\Service\NT8TabsRestService $nt8RestService
   *   Container injection.
   */
  public function __construct(
    $entityQuery,
    $entityTypeManager,
    NT8TabsRestService $nt8RestService
  ) {
    $this->entityQuery = $entityQuery;
    $this->entityTypeManager = $entityTypeManager;
    $this->nt8RestService = $nt8RestService;
  }

  /**
   * Fetches data for the specified attributes from Tabs.
   *
   * @param array $limit
   *   Limit the returned results by attribute code.
   *   Example: $limit = [ 'ATTR01', 'ATTR02' ]
   *   This will only return data for those attributes from the API.
   *   If an empty array is passed all of the data will be returned.
   *
   * @return array
   *   Data for each attribute contained in an array.
   */
  public function getAttributeDataFromTabs(array $limit = []) {
    $api_root_data = json_decode($this->nt8RestService->get('/'));
    $attrib_data = $api_root_data->constants->attributes;

    if (count($limit) > 0) {
      $attrib_data = array_filter($attrib_data, function ($value) use ($limit) {
        $attr_code = $value->code ?: '';
        if (in_array($attr_code, $limit)) {
          return TRUE;
        }

        return FALSE;
      });
    }

    return $attrib_data;
  }

  /**
   * Loads every term listed in $term_names.
   *
   * @param string $vocab_name
   *   Vocabulary Name.
   * @param array $term_names
   *   Array of term names to load. Leave blank to load all terms in vocab.
   * @param callable $term_callback
   *   Callback which is fired for each term loaded.
   *   $term_callback($termEntity, $id).
   *
   * @return array
   *   An array of loaded term entities.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public static function loadTermsByNames(string $vocab_name, array $term_names = [], callable $term_callback = NULL) {
    $loaded_terms = NULL;

    if (count($term_names) === 0) {
      $loaded_terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vocab_name, 0, 1, TRUE);
    }
    else {
      foreach ($term_names as $term_name) {
        $_loaded = static::loadMultipleTaxonomyTermsByName($term_name, $vocab_name);
        if (isset($_loaded) && $_loaded) {
          foreach ($_loaded as $tid => $termEntity) {
            if ($termEntity instanceof Term) {
              $loaded_terms[$tid] = $termEntity;
            }
            else {
              $loaded_terms[$tid] = NULL;
            }
          }
        }
      }
    }

    if (is_callable($term_callback) &&
      is_array($loaded_terms) &&
      count($loaded_terms) > 0
    ) {
      foreach ($loaded_terms as $id => $termEntity) {
        if ($termEntity instanceof Term) {
          $term_callback($termEntity, $id);
        }
      }
    }

    return $loaded_terms;
  }

  /**
   * @codeCoverageIgnore
   */
  protected static function loadMultipleTaxonomyTermsByName($term_name, $vocab_name) {
    return taxonomy_term_load_multiple_by_name($term_name, $vocab_name);
  }

  /**
   * Populates the cottage_attributes taxonomy with data from TABS.
   *
   * @param array $attrib_data
   *   The response array retrieved from TABS.
   *
   * @return string
   *   A comma separated list of created/updated entries in the taxonomy.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\Entity\Exception\AmbiguousEntityClassException
   * @throws \Drupal\Core\Entity\Exception\NoCorrespondingEntityClassException
   */
  public function createAttributesFromTabs(array $attrib_data = []) {
    // Return value.
    $updatedAttrs = "";

    $save_status = FALSE;

    foreach ($attrib_data as $attr_key => $attribute) {

      $attr_array = [
        'field_attribute_code' => ['value' => $attribute->code],
        'field_attribute_brand' => ['value' => $attribute->brand],
        'field_attribute_labl' => ['value' => $attribute->label],
        'field_attribute_group' => ['value' => $attribute->group],
        'field_attribute_type' => ['value' => $attribute->type],
      ];

      // TODO: make this a configurable option or let the admin specify it.
      $attr_array['vid'] = 'cottage_attributes';
      // The name is needed by the Drupal Taxonomy API.
      $attr_array['name'] = $attribute->label;

      $term = Term::create($attr_array);

      // Modify terms of the same name if they already exist.
      $terms = self::loadTermsByNames('cottage_attributes', [$attr_array['name']], function (&$term) use ($attr_array, &$save_status) {
        $term->get('field_attribute_code')->setValue($attr_array['field_attribute_code']);
        $term->get('field_attribute_brand')->setValue($attr_array['field_attribute_brand']);
        $term->get('field_attribute_labl')->setValue($attr_array['field_attribute_labl']);
        $term->get('field_attribute_group')->setValue($attr_array['field_attribute_group']);
        $term->get('field_attribute_type')->setValue($attr_array['field_attribute_type']);

        $save_status = $term->save();
      });

      if (is_null($terms)) {
        // @codeCoverageIgnoreStart
        $save_status = $term->save();
        // @codeCoverageIgnoreEnd
      }

      if ($save_status) {
        $updatedAttrs .= $attr_array['field_attribute_code']['value'] . ", ";
      }
    }

    return $updatedAttrs;
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
      $nodes = $this->loadNodesFromPropref($propref) ?: [];

      if (count($nodes) > 0) {
        foreach ($nodes as $node) {
          $loadedNodes[$propref][] = $node;
        }
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
   *   An array of loaded property entities indexed by NID if $load = true
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
    $field_instance = static::getNodeField($node, $fieldName);
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
   * Retrieves a TID for a given term name. Rturns -1 if term couldn't be found.
   */
  public static function getTidFromTermName($term_name, $vocab_name) {
    $tid = -1;
    if ($terms = taxonomy_term_load_multiple_by_name($term_name, $vocab_name)) {
      $term = reset($terms);
      $tid = $term->id();
    }

    return $tid;
  }

  /*---------------------------------------------------------------------------------------*/

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
      $currentNodeField = static::getNodeFieldValue($nodeInstance, $updatedValueKey);

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
          // @codeCoverageIgnoreStart
          $comparisonUpdate = $nestedComparison;
          // @codeCoverageIgnoreEnd
        }

        // Sort both arrays so the equality check below evaluates correctly.
        sort($comparisonUpdate);
        sort($nodeFieldValue);

        // Compare the two field entries for differences.
        $difference = ($comparisonUpdate == $nodeFieldValue);

        if ($difference == 0) {
          // If a difference is found update the whole field entry.
          $fieldRef = static::getNodeField($nodeInstance, $updatedValueKey);
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

    $attr_build = [];

    $property_attr_array = (array) $data->attributes ?: [];
    $attr_keys = array_keys($property_attr_array);

    // Attributes.
    self::loadTermsByNames(
      'cottage_attributes',
      $attr_keys,
      function (&$term, $id) use ($property_attr_array, &$attr_build) {
        $attr_name = static::getNodeFieldValue($term, 'field_attribute_labl', 0);
        $attr_name_val = self::issetGet($property_attr_array, $attr_name);
        $attr_build[] = [
          'target_id' => (string) $id,
          'value' => json_encode($attr_name_val),
        ];
      }
    );

    $return_definition = [
      'type' => 'property',
      'body' => [],
      'promote' => '0',
      'title' => [
        'value' => "$data->name",
      ],
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
      'field_cottage_attributes' => $attr_build,
    ];

    // If this isn't for a node discard the Drupal node specific keys.
    if (!$is_node) {
      unset($return_definition['type']);
      unset($return_definition['promote']);
    }

    return $return_definition;
  }

}
