<?php
require_once 'inc/app.inc.php'; // include lib/functions, auth & settings, db

$url = "//".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'];

if ($url{strlen($url)-1} == '?') $url = substr($url, 0, strlen($url)-1);

$action = getparam("a", "view");
$page   = getparam("p", "Welcome");
$html   = "";

$wiki = new Wiki($db, strtoupper($page), $action);

$wiki->handle_action();

$wiki->output();
