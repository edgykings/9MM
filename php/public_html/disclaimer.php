<?php
define('PUBLIC_ROOT', __DIR__);
require __DIR__ . '/boot.php';
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
$c = content(); $s = settings();
$legalTitle = 'Disclaimer';
$legalText = $c['disclaimer'];
$legalTodo = 'DISCLAIMER: legal-approved text, paste in admin → Content';
require PRIVATE_DIR . '/templates/legal.php';
