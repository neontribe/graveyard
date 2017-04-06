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

  /**
   * Initialises the map data assoc array needed to bootstrap neontabs_map.
   */
  public function initMap($properties = []) {
    $config = \Drupal::config('nt8map.config');
    $map_settings = $config->get('map-settings');

    $mapdata = self::getMapTiles($map_settings['tiles']);
    $mapdata['image_path'] = self::getMapImagePath();
    $mapdata['initial_zoom'] = $map_settings['zoom'];
    $mapdata['center'] = [$map_settings['lat'], $map_settings['lon']];
    $mapdata['geojson'] = self::getFeatures($properties, 'FeatureCollection', 'Feature', 'Point');

    $mapdata['popups'] = [
      'template' => "
        <a target=_blank href={{url}}>
          <h3>{{title}}</h3>
        </a>
        <ul>
          <li>Sleeps: {{sleeps}}</li>
          <li>Bedrooms: {{bedrooms}}</li>
        </ul>
        <img width=100 height=75 src={{imagetag}}></img>",
      'options' => [
        'className' => 'neonmap-popup',
      ],
    ];

    return $mapdata;
  }

  /**
   * Gets necessary fields from each node to populate map preview tiles.
   */
  public static function getFeatures($properties, $geojsontype, $featureType, $geometryType) {
    $geojson = [
      'type' => $geojsontype,
      'features' => [],
    ];

    foreach ($properties as $property) {
      $property_node = (isset($property[0])) ? $property[0] : NULL;

      if (isset($property_node)) {
        $propertyLat = self::getNodeFieldValue($property_node, 'field_cottage_coordinates', 1);
        $propertyLng = self::getNodeFieldValue($property_node, 'field_cottage_coordinates', 0);

        if (isset($propertyLat, $propertyLng)) {
          $propertyReferenceCode = self::getNodeFieldValue($property_node, 'field_cottage_reference_code', 0);
          $propertyTitle         = self::getNodeFieldValue($property_node, 'field_cottage_name', 0);
          $propertySleeps        = self::getNodeFieldValue($property_node, 'field_cottage_accommodates', 0);
          $propertyBedrooms      = self::getNodeFieldValue($property_node, 'field_cottage_bedrooms', 0);
          $propertyPricing       = self::getNodeFieldValue($property_node, 'field_cottage_pricing', 0);
          $propertyFeaturedImage = self::getNodeFieldValue($property_node, 'field_cottage_featured_image', 0, 'uri');
          $propertyLink          = $property_node->toUrl()->setAbsolute()->toString();

          // Setup an array containing the geojson to send to Neonmap.
          $geojson['features'][] = [
            'id' => $propertyReferenceCode,
            'type' => $featureType,
            'geometry' => [
              'type' => $geometryType,
              'coordinates' => [
                $propertyLat,
                $propertyLng,
              ],
            ],
            'properties' => [
              'title' => $propertyTitle,
              'sleeps' => $propertySleeps,
              'bedrooms'  => $propertyBedrooms,
              'pricerange' => $propertyPricing,
              'url' => $propertyLink,
              'imagetag' => $propertyFeaturedImage,
            ],
          ];
        }

      }
    }

    return $geojson;
  }

  /**
   * Returns the value of a specified field on a node.
   */
  public static function getNodeFieldValue($node, $fieldName, $index = -1, $keyname = 'value') {
    $field_instance = $node->get($fieldName)->getValue();
    $field_value = $field_instance;

    if ($index > -1) {
      $field_value = $field_instance[$index][$keyname];
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
