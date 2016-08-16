<?php

function nt2_theme_preprocess_field(&$vars) {
  // if ($node = menu_get_object()) {
  if ($node = $vars['element']['#object']) {
 	

    if ($node->type == 'cottage_entity') {
  		// dpm($vars['element']['#field_translatable']);
  		// dpm(gettype($vars['element']['#field_language']));
    	$view_mode = $vars['element']['#view_mode'];

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

			if($view_mode == 'teaser') {
				$node->cottage_images = array($node->cottage_images[0]);
			}


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
    		
    		if(isset($item_ref[0]['#markup'])) {
				$item_ref[0]['#markup'] = decode_entities($item_ref[0]['#markup']);
    		}

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
