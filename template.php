<?php

function render_cottage_images($image_data, $view_mode) {
	$image_rndarray = array(
		'cottage-images' => array(
			'#prefix' => '<ul>',
			'#suffix' => '</ul>',
		),
	);

	$index = 0;

	if($view_mode == 'teaser') {
		$image_data = array($image_data[0]);

		list($alt, $title, $url) = explode("\n", $image_data[0]['value']);
		
		$image_rndarray = array(
			'image-highlight' => _render_image($title, $url, $alt),
		);
	} else {
		foreach ($image_data as $image) {
			list($alt, $title, $url) = explode("\n", $image['value']);
		
			$_image = _render_image($title, $url, $alt, '<li>', '</li>');

		 	$image_rndarray['cottage-images']['image' . sprintf('%02d', $index++)] = $_image;
		}
	}

	return $image_rndarray;
}

function _render_image($title, $path, $alt, $style_name, $prefix = '<span>', $suffix = '</span>') {

	$_image_rndarray = array(
	 '#prefix' => $prefix,
	 '#suffix' => $suffix,
	 '#theme' => 'imagecache_external',
	 '#path' => $path,
	 '#style_name' => 'medium',
	 '#alt' => $alt,
	 '#title' => $title,
	);

	return $_image_rndarray;
}

function _render_link($text, $url, $prefix = '<span>', $suffix = '</span>') {
	return array(
		'#theme' => 'link',
		'#prefix' => $prefix,
		'#suffix' => $suffix,
		'#text' => $text,
		'#options' => array(
			'attributes' => array(
				'title' => $text,
			),
			'html' => '',
		),
		'#path' => $url,
	);
}

function render_default_field($field_data) {
	$field_data = array(
		0 => array(
			'#prefix' => '<h2>',
			'#suffix' => '</h2>',
			'data' => $field_data,
		),
	);
	
	return $field_data;
}

function render_cottage_name($field_data, $url, $view_mode) {
	$cottage_name = $field_data[0]['#markup'];
	
	$link_prefix = '<h1 id="cottage-name">';
	$link_suffix = '</h1>';

	$title_renderarray = array(
		0 => _render_link($cottage_name, $url, $link_prefix, $link_suffix),
	);


	return $title_renderarray;
}

function render_cottage_reference($field_data, $url, $view_mode) {

	$cottage_reference = $field_data[0]['#markup'];

	$link_prefix = '<h4 id="cottage-reference">';
	$link_suffix = '</h4>';

	$reference_renderarray = array(
		0 => _render_link($cottage_reference, $url, $link_prefix, $link_suffix),
	);

	return $reference_renderarray;
}


function nt2_theme_preprocess_field(&$vars) {
  if ($node = $vars['element']['#object']) {
    if ($node->type == 'cottage_entity') {
    	$view_mode = $vars['element']['#view_mode'];

    	$item_ref =& $vars['items'];
    	$label = $vars['element']['#field_name'];

    	// Figure out the absolute path to the current node.
    	$options = array('absolute' => TRUE);
		$nid = $node->nid;
		$url = url('node/' . $nid, $options);
    
    	switch($label) {
    		case 'cottage_images':
    			$item_ref = render_cottage_images($node->cottage_images, $view_mode);
    			break;
    		case 'cottage_name':
    			$item_ref = render_cottage_name($item_ref, $url, $view_mode);
    			break;
    		case 'cottage_reference':
    			$item_ref = render_cottage_reference($item_ref, $url, $view_mode);
    			break;
       		default:
    			$item_ref = render_default_field($item_ref);

    	}

 	// if(isset($item_ref[0]['#markup'])) {
		// 	$item_ref[0]['#markup'] = decode_entities($item_ref[0]['#markup']);
 	// 	}

//  		
    }

  }
}
