<?php

function nt2_theme_preprocess_field(&$vars) { //Replace your theme name MYTHEME here.
  if ($node = menu_get_object()) {
    if ($node->type == 'cottage-entity') {//Use your product display name here.
    //  dpm($vars);
    }
  }
 //  dpm($vars);
}
