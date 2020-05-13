<?php

/** -- Functions -------------------------
 * 
 * selectlist($name, $list, $selected, $usekeys = 1, $extra='')    
 * add_autoloader_path($path)                                      
 * 
 * 
**/

// selectlist($name, $list, $selected, $usekeys, $extra='')
function selectlist($name, $list, $selected, $usekeys = 1, $extra='')
{
    if (is_array($usekeys)) {
        $defaults = ['usekeys'=>1, 'extra'=>'', 'return'=>0];
        $options = array_intersect_key(array_merge($defaults, $usekeys), $defaults);
        extract($options);
    }
    $html = "<select name='$name' $extra>\n";
    foreach($list as $key => $value) {
        $h_key   = htmlspecialchars($key, ENT_QUOTES);
        $h_value = htmlspecialchars($value, ENT_QUOTES);
        if ($usekeys) {
            $html .= "<option value='$h_key' " . (( (string) $key == (string) $selected)?'selected="selected"':'') . ">$h_value</option>\n";
        }else{
            $html .= "<option value='$h_value' " . (( (string) $value == (string) $selected)?'selected="selected"':'') . ">$h_value</option>\n";
        }
    }
    $html .= "</select>\n";
    if ($return) return $html;
    echo $html;
}


// add_autoloader_path($path)
function add_autoloader_path($path) {
    spl_autoload_register(function($class) use($path) {
        $file = $path.strtolower($class).".class.php";
        if (file_exists($file)) require $file;
    });
}

//____________________
// htmlents($string) /
function htmlents($string) {
  return htmlspecialchars($string, ENT_QUOTES);
}
