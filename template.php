<?php

function nt2_theme_preprocess_field(&$vars) {
  // if ($node = menu_get_object()) {
  if ($node = $vars['element']['#object']) {

    if ($node->type == 'cottage_entity') {
    	$item_ref =& $vars['items'];
    	$label = $vars['element']['#field_name'];

    	
    
    	if($label == 'cottage_images') {
    		$image_rndarray = array(
				'cottage-images' => array(
					'#prefix' => '<ul>',
					'#suffix' => '</ul>',
				),
			);

			$index = 0;

			foreach ($node->cottage_images as $image) {
				list($alt, $title, $url) = explode("\n", $image['value']);

				$_image = array(
				 '#prefix' => '<li>',
				 '#suffix' => '</li>',
				 '#theme' => 'imagecache_external',
				 '#path' => $url,
				 '#style_name' => 'thumbnail',
				 '#alt' => $alt,
				 '#title' => $title,
				);

			 	$image_rndarray['cottage-images']['image' . sprintf('%02d', $index++)] = $_image;
			}

    		$item_ref = $image_rndarray;
    	
    		
    	} else {
    		$item_ref = array(
				0 => array(
					'#prefix' => '<h2>',
					'#suffix' => '</h2>',
					'data' => $item_ref,
				),
			);
    	}
		
	

    }

  }
}
