
<div class="property-field">

<?php 

foreach ($variables['items'] as $delta => $item) {
	
	$rendered_item = drupal_render($item);

	$output = '<div class="field-item-' . $delta . '">' . $rendered_item . '</div>';

	print $output;
} 

?>

</div>
