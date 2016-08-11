<?php

// function nt2_theme_field($variables) {
// 	$output = "";

// 	//Render the label, if it's not hidden.
// 	// if (!$variables['label_hidden']) {
// 	//  $output .= '<div class="field-label"' . $variables['title_attributes'] . '>' . $variables['label'] . ':&nbsp;</div>';
// 	// }


// 	// Render the items.
// 	$output .= '<div class="field-items"' . $variables['content_attributes'] . '>';
// 	foreach ($variables['items'] as $delta => $item) {
// 		$classes = 'field-item ' . ($delta % 2 ? 'odd' : 'even');
// 		$output .= '<div class="' . $classes . '"' . $variables['item_attributes'][$delta] . '>' . drupal_render($item) . '</div>';
// 	}
// 	$output .= '</div>';

// 	// Render the top-level DIV.
// 	$output = '<div class="' . $variables['classes'] . '"' . $variables['attributes'] . '>' . $output . '</div>';

// 	return $output;
// }


function nt2_theme_preprocess_field(&$vars) {
  if ($node = menu_get_object()) {
   
    if ($node->type == 'cottage_entity') {
    	//kpr();
    	
    	// $element =& $vars['element'];

    	// $label = $vars['element']['#field_name'];
    
    	// kpr($vars['theme_hook_suggestions']);

    	// if($label == 'cottage_reference') {
    		
    	// 	// $item_ref = array(
    	// 	// 	0 => array(
    	// 	// 		'#prefix' => '<h2>',
    	// 	// 		'#suffix' => '</h2>',
    	// 	// 		'#markup' => "Foo Bar",
    	// 	// 	),
    	// 	// );
    	// }

    }

  }
}
