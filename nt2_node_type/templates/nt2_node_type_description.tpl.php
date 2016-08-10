<?php
/**
 * @file Template for rendering and address.
 */
?>

<p class="nt2_node_type_description">
  <div class="description-details">
	  <span><strong> <?php echo isset($description) ? $description : ''; ?> </strong></span>
	  <span><strong> <?php echo isset($description_short) ? $description_short : ''; ?> </strong></span>
	  <span><?php echo isset($description_teaser) ? $description_teaser : ''; ?> </strong></span>
  </div>
</p>