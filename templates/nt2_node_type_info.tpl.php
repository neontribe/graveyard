<?php
/**
 * @file Template for rendering and address.
 */
?>

<div class="nt2_node_type_info">
  <h3>Detail Info</h3>
  <ul class="info-details">
	  <li> <h2>Title:</h2> <strong> <?php echo isset($title) ? $title : ''; ?> </strong></li>
	  <li> <h2>Reference:</h2> <strong> <?php echo isset($reference) ? $reference : ''; ?> </strong></li>
	  <li> <h2>Slug:</h2> <strong> <?php echo isset($slug) ? $slug : ''; ?> </strong></li>
	  <li> <h2>Name:</h2> <strong> <?php echo isset($name) ? $name : ''; ?> </strong></li>
  </ul>
</div>