<?php
/**
 * @file Template for rendering and address.
 */
?>

<div class="nt2_node_type_description">
  <h3>Property Description Info</h3>
  <ul class="description-details">
	  <li> <span>Description:</span> <strong> <?php echo isset($description) ? $description : ''; ?> </strong></li>
	  <li> <span>Short Description:</span> <strong> <?php echo isset($description_short) ? $description_short : ''; ?> </strong></li>
	  <li> <span>Teaser:</span> <strong> <?php echo isset($description_teaser) ? $description_teaser : ''; ?> </strong></li>
  </ul>
</div>