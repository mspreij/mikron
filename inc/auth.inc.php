<?php
$allowed_ips = array('127.0.0.1',
										 '192.168.1.10',
										 '192.168.1.16',
										 '192.168.1.12');
$client_ip = $_SERVER['REMOTE_ADDR'];

// If this page is behind a reverse-proxy thing, 'REMOTE_ADDR' will be the public server's address, and may be in the allowed list.
// The proxy *should* *add* any given 'HTTP_X_FORWARDED_FOR' IP addresses to its own IP address, preventing spoofing it.
// So uncomment the next line if it's reverse-proxied. If not, leave it commented.
// if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) $client_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];

// The normal way:
// if (! in_array($client_ip, $allowed_ips)) die("Access denied: ". $client_ip);

// The Derp! way:
if (! strstr($client_ip, '192.168.1.')) {
	@header('HTTP/1.0 404 Not Found'); // suppressed error in case of headers already sent
	die("<strong>404 - File Not Found</strong>\n");
	die("<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">
<html><head>
<title>404 Not Found</title>
</head><body>
<h1>Not Found</h1>
<p>The requested URL {$_SERVER['REQUEST_URI']} was not found on this server.</p>
<hr>
<address>{$_SERVER['SERVER_SOFTWARE']} Server at {$_SERVER['HTTP_HOST']} Port {$_SERVER['SERVER_PORT']}</address>
</body></html>");
}
