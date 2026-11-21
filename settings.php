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
    <div class="settings-header-top">
      <a class="settings-back-btn" href="<?= e($dashboardUrl) ?>" title="<?= e(t('Back to Dashboard')) ?>" aria-label="<?= e(t('Back to Dashboard')) ?>">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
          <line x1="19" y1="12" x2="5" y2="12"></line>
          <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
      </a>
      <div class="settings-header-titles">
        <h1><?= e(t('Settings')) ?></h1>
        <p><?= e(t('Settings and account preferences')) ?></p>
      </div>
    </div>
  </header>

  <div id="settingsToast" class="settings-toast" role="status" aria-live="polite"></div>

  <?php if (is_array($flash)): ?>
    <div class="settings-flash <?= e($flash['type'] ?? 'success') ?>" role="status">
      <?= e(t((string)($flash['message'] ?? 'Settings saved.'))) ?>
    </div>
  <?php endif; ?>

  <form id="preferencesForm" class="settings-card" method="post" action="settings_action.php">
    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
    <input type="hidden" name="action" value="preferences">
    <input type="hidden" name="ajax" value="1">
    
    <section class="settings-section">
      <div class="settings-section-heading">
        <span class="settings-icon language-icon" aria-hidden="true">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
        </span>
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
        <span class="settings-icon display-icon" aria-hidden="true">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
        </span>
        <div>
          <h2><?= e(t('Display & accessibility')) ?></h2>
        </div>
      </div>

      <label class="settings-option">
        <span class="settings-icon option-icon" aria-hidden="true">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
        </span>
        <span class="settings-option-copy">
          <strong><?= e(t('Dark mode')) ?></strong>
          <small><?= e(t('Adjust the appearance of ACLC Inventory to reduce glare and give your eyes a break.')) ?></small>
        </span>
        <span class="settings-switch">
          <input type="checkbox" name="dark_mode" value="1" <?= !empty($user['dark_mode']) ? 'checked' : '' ?>>
          <span class="settings-switch-track" aria-hidden="true"></span>
        </span>
      </label>
    </section>
  </form>

  <section class="settings-card privacy-card" id="privacy">
    <div class="settings-section">
      <div class="settings-section-heading">
        <span class="settings-icon privacy-icon" aria-hidden="true">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
        </span>
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
          <div class="password-input-wrap">
            <input type="password" id="currentPassword" name="current_password"
                   autocomplete="current-password" required>
            <button type="button" class="btn-toggle-pwd" tabindex="-1" title="Show password" aria-label="Show password">
              <svg class="eye-open" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="eye-closed" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </div>
        </label>
        <label class="settings-field" for="newPassword">
          <span><?= e(t('New password')) ?></span>
          <div class="password-input-wrap">
            <input type="password" id="newPassword" name="new_password"
                   autocomplete="new-password" minlength="8" required>
            <button type="button" class="btn-toggle-pwd" tabindex="-1" title="Show password" aria-label="Show password">
              <svg class="eye-open" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="eye-closed" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </div>
          <small><?= e(t('Use at least 8 characters.')) ?></small>
        </label>
        <label class="settings-field" for="confirmPassword">
          <span><?= e(t('Confirm new password')) ?></span>
          <div class="password-input-wrap">
            <input type="password" id="confirmPassword" name="confirm_password"
                   autocomplete="new-password" minlength="8" required>
            <button type="button" class="btn-toggle-pwd" tabindex="-1" title="Show password" aria-label="Show password">
              <svg class="eye-open" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="eye-closed" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </div>
        </label>
        <div class="settings-actions">
          <button type="submit" class="settings-primary"><?= e(t('Save password')) ?></button>
        </div>
      </form>
    </div>
  </section>
</main>

<script>
(function() {
  const form = document.getElementById('preferencesForm');
  const toast = document.getElementById('settingsToast');

  if (!form || !toast) return;

  let toastTimer = null;
  function showToast(msg, isError) {
    clearTimeout(toastTimer);
    toast.textContent = msg;
    toast.className = 'settings-toast is-visible' + (isError ? ' is-error' : '');
    toastTimer = setTimeout(() => {
      toast.className = 'settings-toast';
    }, 2400);
  }

  function savePreferences(triggerName) {
    const formData = new FormData(form);
    const isDark = form.elements['dark_mode'].checked;

    if (triggerName === 'dark_mode') {
      const themeVal = isDark ? 'dark' : 'light';
      document.documentElement.dataset.theme = themeVal;
      try { localStorage.setItem('aclc_theme', themeVal); } catch (e) {}
    }

    showToast(window.appTranslate ? window.appTranslate('Saving...') : 'Saving...', false);

    fetch(form.action, {
      method: 'POST',
      body: formData,
      headers: {
        'Accept': 'application/json'
      }
    })
    .then(async (res) => {
      const text = await res.text();
      let data = null;
      try {
        data = JSON.parse(text);
      } catch (err) {
        if (res.ok) {
          data = { status: 'success' };
        } else {
          throw new Error('Server returned ' + res.status);
        }
      }
      return data;
    })
    .then(data => {
      if (data && data.status === 'success') {
        showToast(window.appTranslate ? window.appTranslate('Settings saved.') : 'Settings saved.', false);
        if (triggerName === 'language') {
          setTimeout(() => window.location.reload(), 300);
        }
      } else {
        showToast((data && data.message) || 'Error saving settings.', true);
      }
    })
    .catch((err) => {
      console.error('Save preferences failed:', err);
      showToast('Error saving preferences to server.', true);
    });
  }

  form.elements['language'].addEventListener('change', () => savePreferences('language'));
  form.elements['dark_mode'].addEventListener('change', () => savePreferences('dark_mode'));

  document.querySelectorAll('.btn-toggle-pwd').forEach(btn => {
    btn.addEventListener('click', () => {
      const input = btn.previousElementSibling;
      if (!input) return;
      const isPwd = input.type === 'password';
      input.type = isPwd ? 'text' : 'password';
      const eyeOpen = btn.querySelector('.eye-open');
      const eyeClosed = btn.querySelector('.eye-closed');
      if (eyeOpen && eyeClosed) {
        eyeOpen.style.display = isPwd ? 'none' : 'block';
        eyeClosed.style.display = isPwd ? 'block' : 'none';
      }
      btn.title = isPwd ? 'Hide password' : 'Show password';
      btn.setAttribute('aria-label', btn.title);
    });
  });
})();
</script>
</body>
</html>
