<?php

/**
 * @file
 * Template for nt2_map.
 */
?>
<div id="neontabs-property-map" class="gmap" style="width: 100%; height: <?php echo $height; ?>"
     data-locations='<?php echo json_encode($mapdata, JSON_HEX_APOS + JSON_HEX_TAG + JSON_HEX_AMP + JSON_HEX_QUOT); ?>'
     data-geojsonurl='<?php echo $geojsonurl; ?>'
     ></div>
