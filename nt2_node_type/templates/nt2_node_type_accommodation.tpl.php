<?php
/**
 * @file Template for rendering and address.
 */
?>

<div class="nt2_node_type_accommodation">
  <?php /* echo implode("<br />\n", array_reverse(array_filter($address))); */ ?>
  <h3>Accommodation Info</h3>
  <ul class="stacked accommodation-details">
	  <li> <span>Sleeps:</span> <strong> <?php echo isset($accommodates) ? $accommodates : ''; ?> </strong></li>
	  <li> <span>Bedrooms:</span> <strong> <?php echo isset($bedrooms) ? $bedrooms : ''; ?> </strong></li>
	  <li> <span>Pets:</span> <strong> <?php echo isset($pets) ? $pets : ''; ?> </strong></li>
	  <li> <span>Changeover Day:</span> <strong> <?php echo isset($changeover) ? $changeover : ''; ?> </strong></li>
  </ul>

</div>