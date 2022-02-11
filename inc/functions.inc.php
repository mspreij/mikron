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

// googlinks($links, $total, $return=0)
function googlinks($links, $total, $return=0) {
    $prev = 'previous';
    $next = 'next';
    $page = (int) @$_GET['page'];
    $skip = $links * $page;
    $out  = '';
    $b    = "style='font-weight: bold;'";
    parse_str($_SERVER['QUERY_STRING'], $query);
    unset($query['page']);
    if ($query = http_build_query($query)) $query .= '&';
    $pages = ceil($total/$links)-1;
    $items[] = ($page > 0) ? array("&laquo; $prev", "./?{$query}page=".($page-1)) : "<span style='color: gray;'>&laquo; $prev</span>";
    for($i=0;$i<=$pages;$i++) {
        $items[] = array($i+1, "./?{$query}page=$i");
    }
    $items[] = ($page < $pages) ? array("$next &raquo;", "./?{$query}page=".($page+1)) : "<span style='color: gray;'>$next &raquo;</span>";
    if (! $return) {
        foreach($items as $val) $out .= ' '. (is_array($val) ? "<a href='{$val[1]}' ".(($val[0] == ($page+1)) ? $b : '').">{$val[0]}</a>" : $val);
        $out = substr($out, 1);
    }elseif ($return == 1) {
        $out = array();
        foreach($items as $val) $out[] = is_array($val) ? "<a href='{$val[1]}' ".(($val[0] == ($page+1)) ? $b : '').">{$val[0]}</a>" : $val;
    }
    return "<span id='googlinks'>$out</span>";
}

// settings($key, $value='')
function settings($key=null, $value='')
{
    static $values = array();
    if (count($values) === 0) {
        $values = require ROOT.'inc/settings.inc.php';
    }
    if (! isset($key)) return $values;
    if (isset($value)) {
        $values[$key] = $value;
        return $value;
    }else{
        if (isset($values[$key])) {
            return $values[$key];
        }else{
            return null;
        }
    }
}
