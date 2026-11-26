<?php
define('PUBLIC_ROOT', __DIR__);
require __DIR__ . '/boot.php';

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: SAMEORIGIN');

$c = content();
$s = settings();
require PRIVATE_DIR . '/templates/site.php';
