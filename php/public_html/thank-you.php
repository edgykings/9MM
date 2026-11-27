<?php
define('PUBLIC_ROOT', __DIR__);
require __DIR__ . '/boot.php';
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-Robots-Tag: noindex, nofollow');
$c = content(); $s = settings();
require PRIVATE_DIR . '/templates/thankyou.php';
