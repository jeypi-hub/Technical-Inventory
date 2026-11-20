<?php
require_once __DIR__ . '/i18n.php';
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
<script>
document.documentElement.lang = <?= json_encode(app_language_tag()) ?>;
document.documentElement.dataset.theme = <?= json_encode(app_theme()) ?>;
document.documentElement.dataset.compact = <?= json_encode(app_compact_mode() ? '1' : '0') ?>;
window.APP_I18N = <?= json_encode(app_translations(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
window.appTranslate = (key, values = {}) => {
  let text = window.APP_I18N[key] || key;
  for (const [name, value] of Object.entries(values)) {
    text = text.replaceAll(`{${name}}`, value);
  }
  return text;
};
</script>
<link rel="manifest" href="<?= $appBasePath ?>/manifest.webmanifest">
<link rel="icon" type="image/png" sizes="192x192" href="<?= $appBasePath ?>/assets/images/pwa-192.png">
<link rel="apple-touch-icon" href="<?= $appBasePath ?>/assets/images/pwa-192.png">
<link rel="stylesheet" href="<?= $appBasePath ?>/assets/css/pwa.css?v=14">
<link rel="stylesheet" href="<?= $appBasePath ?>/assets/css/settings.css?v=19">
<script defer src="<?= $appBasePath ?>/assets/js/pwa.js?v=7"></script>
