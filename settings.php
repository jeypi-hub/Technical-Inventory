<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/User.php';
$user = User::requireAnyLogin();

$flash = $_SESSION['settings_flash'] ?? null;
unset($_SESSION['settings_flash']);
$csrfToken = $_SESSION['settings_csrf'] ??= bin2hex(random_bytes(32));
$language = $user['language'] ?? 'en';
$dashboardUrl = in_array($user['role'], ['admin', 'staff'], true)
    ? 'admin/admin_dashboard.php'
    : 'student/student_dashboard.php';
?>
<!DOCTYPE html>
<html lang="<?= e(app_language_tag()) ?>" data-theme="<?= e(app_theme()) ?>"
      data-compact="<?= app_compact_mode() ? '1' : '0' ?>">
<head>
  <meta charset="UTF-8">
  <title><?= e(t('Settings')) ?> — ACLC</title>
  <?php include __DIR__ . '/includes/pwa_head.php'; ?>
</head>
<body>
<main class="settings-main">
  <header class="settings-header">
    <a class="settings-back" href="<?= e($dashboardUrl) ?>">← <?= e(t('Back to Dashboard')) ?></a>
    <h1><?= e(t('Settings')) ?></h1>
    <p><?= e(t('Settings and account preferences')) ?></p>
  </header>

  <?php if (is_array($flash)): ?>
    <div class="settings-flash <?= e($flash['type'] ?? 'success') ?>" role="status">
      <?= e(t((string)($flash['message'] ?? 'Settings saved.'))) ?>
    </div>
  <?php endif; ?>

  <form class="settings-card" method="post" action="settings_action.php">
    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
    <input type="hidden" name="action" value="preferences">
    <section class="settings-section">
      <div class="settings-section-heading">
        <span class="settings-icon language-icon" aria-hidden="true">文</span>
        <div>
          <h2><?= e(t('Language')) ?></h2>
          <p><?= e(t('Choose the language used across the system.')) ?></p>
        </div>
      </div>
      <label class="settings-field" for="language">
        <span><?= e(t('Language')) ?></span>
        <select id="language" name="language">
          <option value="en" <?= $language === 'en' ? 'selected' : '' ?>>English</option>
          <option value="tl" <?= $language === 'tl' ? 'selected' : '' ?>>Tagalog</option>
          <option value="ceb" <?= $language === 'ceb' ? 'selected' : '' ?>>Bisaya</option>
        </select>
      </label>
    </section>

    <section class="settings-section">
      <div class="settings-section-heading">
        <span class="settings-icon display-icon" aria-hidden="true">◐</span>
        <div>
          <h2><?= e(t('Display & accessibility')) ?></h2>
        </div>
      </div>
      <label class="settings-option">
        <span class="settings-icon option-icon" aria-hidden="true">◐</span>
        <span class="settings-option-copy">
          <strong><?= e(t('Dark mode')) ?></strong>
          <small><?= e(t('Adjust the appearance of ACLC Inventory to reduce glare and give your eyes a break.')) ?></small>
        </span>
        <span class="settings-switch">
          <input type="checkbox" name="dark_mode" value="1" <?= !empty($user['dark_mode']) ? 'checked' : '' ?>>
          <span class="settings-switch-track" aria-hidden="true"></span>
        </span>
      </label>
      <label class="settings-option">
        <span class="settings-icon option-icon" aria-hidden="true">Aa</span>
        <span class="settings-option-copy">
          <strong><?= e(t('Compact mode')) ?></strong>
          <small><?= e(t('Make your font size smaller so more content can fit on the screen.')) ?></small>
        </span>
        <span class="settings-switch">
          <input type="checkbox" name="compact_mode" value="1" <?= !empty($user['compact_mode']) ? 'checked' : '' ?>>
          <span class="settings-switch-track" aria-hidden="true"></span>
        </span>
      </label>
    </section>

    <div class="settings-actions">
      <button type="submit" class="settings-primary"><?= e(t('Save preferences')) ?></button>
    </div>
  </form>

  <section class="settings-card privacy-card" id="privacy">
    <div class="settings-section">
      <div class="settings-section-heading">
        <span class="settings-icon privacy-icon" aria-hidden="true">♙</span>
        <div>
          <h2><?= e(t('Privacy')) ?></h2>
          <p><?= e(t('Change password')) ?></p>
        </div>
      </div>
      <form method="post" action="settings_action.php" class="password-form">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
        <input type="hidden" name="action" value="password">
        <label class="settings-field" for="currentPassword">
          <span><?= e(t('Current password')) ?></span>
          <input type="password" id="currentPassword" name="current_password"
                 autocomplete="current-password" required>
        </label>
        <label class="settings-field" for="newPassword">
          <span><?= e(t('New password')) ?></span>
          <input type="password" id="newPassword" name="new_password"
                 autocomplete="new-password" minlength="8" required>
          <small><?= e(t('Use at least 8 characters.')) ?></small>
        </label>
        <label class="settings-field" for="confirmPassword">
          <span><?= e(t('Confirm new password')) ?></span>
          <input type="password" id="confirmPassword" name="confirm_password"
                 autocomplete="new-password" minlength="8" required>
        </label>
        <div class="settings-actions">
          <button type="submit" class="settings-primary"><?= e(t('Save password')) ?></button>
        </div>
      </form>
    </div>
  </section>
</main>
</body>
</html>
