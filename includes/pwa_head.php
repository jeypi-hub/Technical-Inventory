<?php
$appBasePath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
$appBasePath = preg_replace('~/(admin|student|auth)$~', '', $appBasePath);
$appBasePath = rtrim($appBasePath, '/');
$appBasePath = htmlspecialchars($appBasePath, ENT_QUOTES, 'UTF-8');
?>
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#1a1cb2">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="ACLC Inventory">
<link rel="manifest" href="<?= $appBasePath ?>/manifest.webmanifest">
<link rel="icon" type="image/png" sizes="192x192" href="<?= $appBasePath ?>/assets/images/pwa-192.png">
<link rel="apple-touch-icon" href="<?= $appBasePath ?>/assets/images/pwa-192.png">
<link rel="stylesheet" href="<?= $appBasePath ?>/assets/css/pwa.css">
<script defer src="<?= $appBasePath ?>/assets/js/pwa.js"></script>
