<?php
$secret = ""; // Add the GitHub secret if set
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE'] ?? '';
$payload = file_get_contents('php://input');
$hash = "sha1=" . hash_hmac('sha1', $payload, $secret);

if ($secret && !hash_equals($hash, $signature)) {
    http_response_code(403);
    die('Invalid signature');
}

exec('cd /var/www/html/zillionlinx-backend && git pull origin main');
http_response_code(200);
?>
