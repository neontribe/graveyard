<div class=<?php print $variables['field_name_css']; ?> >

<?php

foreach ($variables['items'] as $delta => $item) {
	$rendered_item = drupal_render($item);

	$output = $rendered_item;

	print $output;
}

?>

</div>
