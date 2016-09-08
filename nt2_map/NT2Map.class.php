<?php

/**
 * @file
 * Contains the class NT2Map.
 */

/**
 * NT2Map contains functions critical to the initalisation of neonmap for a property node.
 */
class NT2Map {

  /**
   * Initialises neonmap for use by the nt2_map module.
   *
   * @param array $properties
   *    Used to initialise Neonmap markers.
   *
   * @return array
   *    The data required to successfully initialise Neonmap with the requisite data.
   */
  public static function initMap($properties) {
    if (!is_array($properties)) {
      $properties = array($properties);
    }

    $map_settings = variable_get('nt2_map_defaults');
    $mapdata = self::getMapTiles($map_settings['tiles']);

    $mapdata['image_path'] = self::getMapImagePath();

    $mapdata['initial_zoom'] = $map_settings['zoom'];
    $mapdata['center'] = array($map_settings['lat'], $map_settings['lon']);

    $mapdata['geojson'] = self::getFeatures($properties, 'FeatureCollection', 'Feature', 'Point');

    $mapdata['popups'] = array(
      'template' => '<a href={{url}}><h3>{{title}}</h3></a> <img width=250 height=250 src={{imagetag}}></img>',
      'options' => array(
        'className' => 'neonmap-popup',
      ),
    );

    drupal_add_js(
      drupal_get_path('module', 'nt2_map') . '/neonmap/dist/neonmap.js',
      array(
        'type' => 'file',
        'scope' => 'footer',
        'weight' => 100,
      )
    );

    drupal_add_css(
      drupal_get_path('module', 'nt2_map') . '/neonmap/dist/neonmap.css',
      array(
        'type' => 'file',
        'scope' => 'footer',
      )
    );

    return $mapdata;
  }

  /**
   * If address is not empty, add it to the features array.
   *
   * @param array $properties
   *   Address as set in the theme.
   * @param string $geojsontype
   *   Geojson type.
   *
   * @return array
   *   The Geojson.
   */
  public static function getFeatures($properties, $geojsontype) {
    $geojson = array(
      'type' => $geojsontype,
      'features' => array(),
    );

    foreach ($properties as $property) {
      $propLat = $property['cottage_coordinates'][0]['#markup'];
      $propLng = $property['cottage_coordinates'][1]['#markup'];

      if (isset($propLng) && isset($propLat)) {
        $propObj = $property['body']['#object'];

        // Figure out the absolute path to the current node.
        $options = array('absolute' => TRUE);
        $nid = $propObj->nid;
        $url = url('node/' . $nid, $options);

        $geojson['features'][] = array(
          'id' => $property['cottage_reference'][0]['#markup'],
          'type' => 'Feature',
          'geometry' => array(
            'type' => 'Point',
            'coordinates' => array(
              $propLng,
              $propLat,
            ),
          ),
          'properties' => array(
            'title' => $property['cottage_name'][0]['#markup'],
            'sleeps' => $property['cottage_accomodates'][0]['#markup'],
            'bedrooms'  => $property['cottage_bedrooms'][0]['#markup'],
            'pricerange' => $property['cottage_pricing'][0]['#markup'],
            'propurl' => $url,
            'imagetag' => explode("\n", $property["cottage_images"][0]['#markup'])[2],
          ),
        );
      }
    }

    return $geojson;
  }

  /**
   * Get neonmap image path.
   *
   * @return string
   *   URL for the images folder.
   */
  public static function getMapImagePath() {
    return url(drupal_get_path('module', 'nt2_map') . '/neonmap/dist/images');
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
  public static function getMapTiles($tiles) {
    switch ($tiles) {
      case 'stamen':
        $mapdata['tile_url'] = 'https://stamen-tiles-{s}.a.ssl.fastly.net/toner/{z}/{x}/{y}.png';
        $mapdata['attribution'] = 'Map tiles by <a href="http://stamen.com">Stamen Design</a>, <a href="http://creativecommons.org/licenses/by/3.0">CC BY 3.0</a> &mdash; Map data &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>';
        break;

      case 'osmmap':
        $mapdata['tile_url'] = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
        $mapdata['attribution'] = 'Map data © <a href="http://openstreetmap.org">OpenStreetMap</a> contributors';
      default:
        break;
    }
    return $mapdata;
  }

}
