<?php
// this is a crude but effective way to limit access to the wiki.
// To disable it altogether, you can remove it's content, stop /index.php from requiring it, or simply put 'return;' after the opening <?php tag at the top.
// Orrrr, you could put some fancy code here that checks a user table, or rely on a .htaccess, or configure your server for it, etc.

$allowed_ips = array(
    '127.0.0.1',
    '192.168.1.10',
);
$client_ip = $_SERVER['REMOTE_ADDR'];

// If this page is behind a reverse-proxy thing, 'REMOTE_ADDR' will be the public server's address, and may be in the allowed list.
// The proxy *should* *add* any given 'HTTP_X_FORWARDED_FOR' IP addresses to its own IP address, preventing spoofing it.
// So uncomment the next line if it's reverse-proxied. If not, leave it commented.
// if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) $client_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];

if (! in_array($client_ip, $allowed_ips)) {
    echo "Access denied: ". $client_ip."<br>\n";
    die();
}
