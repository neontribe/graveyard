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
 * @author oliver
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

  public function createNodeInstanceFromData(\stdClass $data, $deleteExisting = FALSE) {
    $brandcode = $data->brandCode;
    $brandcode_info = $data->brands->{$brandcode};

    $address = $data->address;

    $pricing = json_encode(
      $brandcode_info->pricing
    );

    if($deleteExisting) {
      $nodeQuery = $this->entityQuery->get('node');
      $nodeStorage = $this->entityTypeManager->getStorage('node');
      $nids = $nodeQuery->condition('field_cottage_reference_code.value', $data->propertyRef, '=')->execute();

      // Delete any existing property entities which have already been loaded in.
      $nodes = $nodeStorage->loadMultiple($nids);
      $nodeStorage->delete($nodes);
    }

    // Use the entity manager.
    $node = $nodeStorage->create(
      array(
        'type' => 'property',
        'title' => "$data->name",
        'field_cottage_name' => $data->name,
        'field_cottage_brandcode' => $brandcode,
        'field_cottage_slug' => $data->slug,
        'field_cottage_ownercode' => $data->ownerCode,
        'field_cottage_url' => $data->url,
        'field_cottage_teaser_description' => $brandcode_info->teaser,
        'field_cottage_reference_code' => $data->propertyRef,
        'field_cottage_booking' => $data->booking,
        'field_cottage_accommodates' => $data->accommodates,
        'field_cottage_pets' => $data->pets,
        'field_cottage_bedrooms' => $data->bedrooms,
        'field_cottage_promote' => $data->promote,
        'field_cottage_rating' => $data->rating,
        'field_cottage_changeover_day' => $data->changeOverDay,
        'field_cottage_pricing' => $pricing,
        'field_cottage_coordinates' => [
          $data->coordinates->latitude,
          $data->coordinates->longitude,
        ],
        'field_cottage_address' => [
          'address_line1' => $address->addr1,
          'address_line2' => $address->addr2,
          'locality' => $address->town,
          'administrative_area' => $address->county,
          'postal_code' => $address->postcode,
          'country_code' => $address->country,
        ]
      )
    );
    $node->enforceIsNew();
    $node->save();
  }

}