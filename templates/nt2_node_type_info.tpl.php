<?php
/**
 * @file Template for rendering and address.
 */
?>

<div class="nt2_node_type_info">
  <h3>Detail Info</h3>
  <ul class="info-details">
	  <li> <span>Title:</span> <strong> <?php echo isset($title) ? $title : ''; ?> </strong></li>
	  <li> <span>Reference:</span> <strong> <?php echo isset($reference) ? $reference : ''; ?> </strong></li>
	  <li> <span>Slug:</span> <strong> <?php echo isset($slug) ? $slug : ''; ?> </strong></li>
	  <li> <span>Name:</span> <strong> <?php echo isset($name) ? $name : ''; ?> </strong></li>
  </ul>
</div>