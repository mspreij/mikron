<?php
/*
app.inc.php:
- bunch of includes
- auth & settings
- open DB

*/

define('ROOT', $_SERVER['DOCUMENT_ROOT']);

require_once ROOT.'/vendor/autoload.php';
require_once __DIR__.'/mikron_functions.inc.php';
require_once __DIR__.'/functions.inc.php';

$auth_file     = __DIR__.'/auth.inc.php';
if (file_exists($auth_file)) {
    require_once $auth_file;
}else{
    echo "Auth file missing, copy and edit *.sample file in ".__DIR__."/. See comments in that file for more info.";
    die();
}

// default settings, override these in ./settings.inc.php as needed. Should this bunch also go into like, a default_settings.inc.php file?
$sitetitle   = "Mikron";
$dbfile      = "data/mikron.db";
$formats     = ['markdown'];
$stylesheets = [];
$users       = [ // this one should eventually move elsewhere..
    '127.0.0.1' => 'A. Utho',
];
date_default_timezone_set("UTC");

// customization:
$settings_file = __DIR__.'/settings.inc.php';
if (file_exists($settings_file))  require_once $settings_file;

if (! defined('TAB_LENGTH'))      define('TAB_LENGTH', 2);

// database
if (!($db = new SQLite3($dbfile))) {
    die($db->lastErrorMsg());
}
