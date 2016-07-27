<?php
/**
 * @file Template for rendering and address.
 */
?>

<div class="nt2_note_type_address">
  <?php /* echo implode("<br />\n", array_reverse(array_filter($address))); */ ?>
  <h3>Address</h3>
  <?php echo isset($addr1) ? $addr1 . '</br>' : ''; ?>
  <?php echo isset($addr2) ? $addr2 . '</br>' : ''; ?>
  <?php echo isset($county) ? $county . '</br>' : ''; ?>
  <?php echo isset($postcode) ? $postcode . '</br>' : ''; ?>
</div>