<?php

namespace Drupal\nt8map\Service;

/**
 * Class NT8MapService.
 *
 * @package Drupal\nt8map
 */
class NT8MapService {

  /**
   * Constructor.
   */
  public function __construct() {

  }

  public function initMap($properties = []) {
    $config = \Drupal::config('nt8map.config');
    $map_settings = $config->get('map-settings');
    
    $mapdata = self::getMapTiles($map_settings['tiles']);
    $mapdata['image_path'] = self::getMapImagePath();
    $mapdata['initial_zoom'] = $map_settings['zoom'];
    $mapdata['center'] = [$map_settings['lat'], $map_settings['lon']];
    $mapdata['geojson'] = self::getFeatures($properties, 'FeatureCollection', 'Feature', 'Point');

    return $mapdata;
  }

  public static function getFeatures($properties, $geojsontype, $featureType, $geometryType) {
    $geojson = [
      'type' => $geojsontype,
      'features' => [],
    ];

    foreach ($properties as $property) {
      $property_node = (isset($property[0])) ? $property[0] : NULL;

      if(isset($property_node)) {
        $propertyLat = self::getNodeFieldValue($property_node, 'field_cottage_coordinates', 1);
        $propertyLng = self::getNodeFieldValue($property_node, 'field_cottage_coordinates', 0);

        if(isset($propertyLat, $propertyLng)) {
          $propertyReferenceCode = self::getNodeFieldValue($property_node, 'field_cottage_reference_code', 0);
          $propertyTitle         = self::getNodeFieldValue($property_node, 'field_cottage_name',           0);
          $propertySleeps        = self::getNodeFieldValue($property_node, 'field_cottage_accommodates',   0);
          $propertyBedrooms      = self::getNodeFieldValue($property_node, 'field_cottage_bedrooms',       0);
          $propertyPricing       = self::getNodeFieldValue($property_node, 'field_cottage_pricing',        0);

          // Setup an array containing the geojson to send to Neonmap.
          $geojson['features'][] = array(
            'id' => $propertyReferenceCode,
            'type' => $featureType,
            'geometry' => array(
              'type' => $geometryType,
              'coordinates' => array(
                $propertyLat,
                $propertyLng,
              ),
            ),
            'properties' => array(
              'title' => $propertyTitle,
              'sleeps' => $propertySleeps,
              'bedrooms'  => $propertyBedrooms,
              'pricerange' => $propertyPricing,
            ),
          );
        }

      }
    }

    return $geojson;
  }

  public static function getNodeFieldValue($node, $fieldName, $index = -1) {
    $field_instance = $node->get($fieldName)->getValue();
    $field_value = $field_instance;

    if($index > -1) {
      $field_value = $field_instance[$index]['value'];
    }

    return $field_value;
  }

  /**
   * Get neonmap image path.
   *
   * @return string
   *   URL for the images folder.
   */
  public static function getMapImagePath() {
    return drupal_get_path('module', 'nt8map') . '/vendor/neonmap/dist/images';
  }

  /**
   * Get the tile set.
   *
   * @param string $tiles
   *   Tile set.
   *
   * @return array
   *   The tile set.
   */
  protected static function getMapTiles($tiles) {
    $mapdata = [];

    switch ($tiles) {
      case 'stamen':
        $mapdata['tile_url'] = 'https://stamen-tiles-{s}.a.ssl.fastly.net/toner/{z}/{x}/{y}.png';
        $mapdata['attribution'] = 'Map tiles by <a href="http://stamen.com">Stamen Design</a>, <a href="http://creativecommons.org/licenses/by/3.0">CC BY 3.0</a> &mdash; Map data &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>';
        break;
      case 'osmmap':
        $mapdata['tile_url'] = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
        $mapdata['attribution'] = 'Map data © <a href="http://openstreetmap.org">OpenStreetMap</a> contributors';
        break;
      default:
        break;
    }
    return $mapdata;
  }

}
