<?php
/*
app.inc.php:
- bunch of includes
- auth & settings
- open DB

*/

error_reporting(-1);
ini_set('display_errors', '1');

define('ROOT', __DIR__.'/../');

require_once ROOT.'/vendor/autoload.php';
require_once __DIR__.'/mikron_functions.inc.php';
require_once __DIR__.'/functions.inc.php';
add_autoloader_path('class/');

$auth_file     = __DIR__.'/auth.inc.php';
if (file_exists($auth_file)) {
    require_once $auth_file;
}else{
    echo "Auth file missing, copy and edit *.sample file in ".__DIR__."/. See comments in that file for more info.";
    die();
}

// default settings
extract(require(__DIR__.'/default_settings.inc.php'));

// customization:
$settings_file = __DIR__.'/settings.inc.php';
if (file_exists($settings_file))  require $settings_file;

if (! defined('TAB_LENGTH'))      define('TAB_LENGTH', 2);

// database object. $dbfile is defined in [default_]settings
if (!($db = new SQLite3($dbfile))) {
    die($db->lastErrorMsg());
}
