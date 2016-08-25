<?php

function render_cottage_images($image_data, $view_mode) {
    $image_rndarray = array();
    $index = 0;

    if($view_mode == 'teaser') {
        $image_data = array($image_data['und'][0]);
        
        list($alt, $title, $url) = explode("\n", $image_data[0]['value']);
        
        $image_rndarray = array(
            'cottage-images' => _render_image($title, $url, $alt, 'medium'),
        );
    } else {

        $image_rndarray = array(
            'cottage-images' => array(
                '#prefix' => '<ul>',
                '#suffix' => '</ul>',
            ),
        );


        // TEMPORARY
        $image_data['und'] = array($image_data['und'][0]);

        foreach ($image_data['und'] as $image) {
            list($alt, $title, $url) = explode("\n", $image['value']);
        
            $_image = _render_image($title, $url, $alt, 'large', '<li>', '</li>');

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
     '#style_name' => $style_name,
     '#alt' => $alt,
     '#title' => $title,
    );

    return $_image_rndarray;
}

function _render_link($text, $url, $title, $prefix = '<span>', $suffix = '</span>') {
    return array(
        '#theme' => 'link',
        '#prefix' => $prefix,
        '#suffix' => $suffix,
        '#text' => $text,
        '#options' => array(
            'attributes' => array(
                'title' => $title,
            ),
            'html' => '',
        ),
        '#path' => $url,
    );
}

function render_cottage_name($field_data, $url, $view_mode) {
    $cottage_name = $field_data[0]['#markup'];
    
    $link_prefix = '<h1 id="cottage-name">';
    $link_suffix = '</h1>';

    $title_renderarray = array(
        0 => _render_link($cottage_name, $url, 'cottage-page-link', $link_prefix, $link_suffix),
    );

    return $title_renderarray;
}

function render_cottage_reference($field_data, $url, $view_mode) {

    $cottage_reference = $field_data[0]['#markup'];

    $link_prefix = '<h4 id="cottage-reference">';
    $link_suffix = '</h4>';

    $reference_renderarray = array(
        0 => _render_link('(' . $cottage_reference . ')', $url, 'cottage-page-link', $link_prefix, $link_suffix),
    );

    return $reference_renderarray;
}

function render_default_field($item_ref, $prefix = '<p>', $suffix = '</p>') {
    $item_ref = array(
        0 => array(
            '#prefix' => $prefix,
            '#suffix' => $suffix,
            'data' => $item_ref,
        ),
    );
    
    return $item_ref;
}

function render_cottage_info_field($prefix_text, $item_ref, $url, $view_mode, $prefix = NULL, $suffix = NULL) {
    $field_data = array(
        0 => array(
            '#prefix' => $prefix,
            '#suffix' => $suffix,
            'data' => array(
                0 => array(
                    '#prefix' => '<p>',
                    '#suffix' => '</p>',
                    '#markup' => $prefix_text,
                ),
                1 => render_default_field($item_ref, '<p><em>', '</em></p>'),
            ),
        )
    );
    return $field_data;
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
        case 'cottage_bedrooms':
            $item_ref = render_cottage_info_field('Bedrooms', $item_ref, $url, $view_mode);
            break;
        case 'cottage_pets':
            $item_ref[0]['#markup'] = ($item_ref[0]['#markup']) ? 'Yes' : 'No';
            $item_ref = render_cottage_info_field('Pets', $item_ref, $url, $view_mode);
            break;
        case 'cottage_changeoverday':
            $item_ref = render_cottage_info_field('Changeover Day', $item_ref, $url, $view_mode);
            break;
        case 'cottage_pricing':
            $pricing_parsed = json_decode(decode_entities($item_ref[0]['#markup']));
            $prices = $pricing_parsed->ranges->{'2016'};

            $item_ref[0] = array(
              '#markup' => '£' . $prices->low,
            );

            $item_ref[1] = array(
              '#markup' => ' - £' . $prices->high,
            );

            $item_ref = render_cottage_info_field('Price Range', $item_ref, $url, $view_mode);
            break;
        case 'cottage_location_reference':
          $item_ref[0]['#options']['attributes']['title'] = 'cottage-location-reference';
          $item_ref = render_cottage_info_field('Location', $item_ref, $url, $view_mode);
          break;
        default:
            $item_ref = render_default_field($item_ref);
      }
    }
  }
}
