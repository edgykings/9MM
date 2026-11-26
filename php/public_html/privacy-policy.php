<?php
define('PUBLIC_ROOT', __DIR__);
require __DIR__ . '/boot.php';
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
$c = content(); $s = settings();
$legalTitle = 'Privacy policy';
$legalText = $c['privacy_text'];
$legalTodo = 'PRIVACY POLICY: from Legal (Seasons policy adapted for 9 Minnutes), paste in admin → Content';
require PRIVATE_DIR . '/templates/legal.php';
