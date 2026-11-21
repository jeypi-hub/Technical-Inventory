<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/User.php';

$user = User::requireAnyLogin();
$profile = User::profileForUser((int)$user['id']);
if ($profile === null) {
    http_response_code(404);
    exit(e(t('Profile could not be loaded. Please try again.')));
}
$dashboardUrl = in_array($user['role'], ['admin', 'staff'], true)
    ? 'admin/admin_dashboard.php'
    : 'student/student_dashboard.php';
$idLabel = $user['role'] === 'student'
    ? t('Student ID')
    : ($user['role'] === 'faculty' ? t('Faculty ID') : t('User ID'));

$csrfToken = $_SESSION['profile_csrf'] ??= bin2hex(random_bytes(32));
$successMessage = $_SESSION['profile_flash'] ?? null;
unset($_SESSION['profile_flash']);
$errorMessage = null;
$name = (string)$profile['name'];
$email = (string)($profile['email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? null;
    if (!is_string($submittedToken) || !hash_equals($csrfToken, $submittedToken)) {
        $errorMessage = t('Your session expired. Reload the page and try again.');
    }

    $submittedName = $_POST['name'] ?? null;
    if (is_string($submittedName)) {
        $name = trim($submittedName);
    } elseif ($errorMessage === null) {
        $errorMessage = t('Enter a valid name.');
    }

    $submittedEmail = $_POST['email'] ?? null;
    if (is_string($submittedEmail)) {
        $email = trim($submittedEmail);
    } elseif ($errorMessage === null) {
        $errorMessage = t('Enter a valid email address.');
    }

    $nameLength = preg_match_all('/./us', $name);
    if ($errorMessage === null && ($name === '' || $nameLength === false)) {
        $errorMessage = t('Enter a valid name.');
    } elseif ($errorMessage === null && $nameLength > 120) {
        $errorMessage = t('Name must be 120 characters or fewer.');
    }

    if ($errorMessage === null && $email !== '' && strlen($email) > 160) {
        $errorMessage = t('Email must be 160 characters or fewer.');
    } elseif ($errorMessage === null && $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $errorMessage = t('Enter a valid email address.');
    }

    $newProfilePicture = null;
    $newProfilePicturePath = null;
    $upload = $_FILES['profile_picture'] ?? null;
    if ($errorMessage === null && $upload !== null) {
        if (!is_array($upload) || !isset($upload['error']) || !is_int($upload['error'])) {
            $errorMessage = t('Profile picture upload failed. Please try again.');
        } elseif ($upload['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($upload['error'] !== UPLOAD_ERR_OK) {
                $errorMessage = $upload['error'] === UPLOAD_ERR_INI_SIZE || $upload['error'] === UPLOAD_ERR_FORM_SIZE
                    ? t('Choose a JPG, PNG, or WebP image no larger than 5 MB.')
                    : t('Profile picture upload failed. Please try again.');
            } else {
                $temporaryPath = $upload['tmp_name'] ?? null;
                $actualSize = is_string($temporaryPath) && is_uploaded_file($temporaryPath)
                    ? filesize($temporaryPath)
                    : false;
                if (!is_string($temporaryPath) || !is_uploaded_file($temporaryPath)
                    || $actualSize === false || $actualSize < 1 || $actualSize > 5 * 1024 * 1024) {
                    $errorMessage = t('Choose a JPG, PNG, or WebP image no larger than 5 MB.');
                } else {
                    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
                    $mimeType = $fileInfo->file($temporaryPath);
                    $imageInfo = @getimagesize($temporaryPath);
                    $extensions = [
                        'image/jpeg' => 'jpg',
                        'image/png' => 'png',
                        'image/webp' => 'webp',
                    ];
                    if (!is_string($mimeType) || !isset($extensions[$mimeType])
                        || $imageInfo === false || ($imageInfo['mime'] ?? null) !== $mimeType) {
                        $errorMessage = t('Choose a JPG, PNG, or WebP image no larger than 5 MB.');
                    } else {
                        $uploadDirectory = __DIR__ . '/assets/upload';
                        if (!is_dir($uploadDirectory) || !is_writable($uploadDirectory)) {
                            $errorMessage = t('Profile picture upload failed. Please try again.');
                        } else {
                            $newProfilePicture = 'profile_' . bin2hex(random_bytes(16)) . '.' . $extensions[$mimeType];
                            $newProfilePicturePath = $uploadDirectory . '/' . $newProfilePicture;
                            if (!move_uploaded_file($temporaryPath, $newProfilePicturePath)) {
                                $newProfilePicturePath = null;
                                $errorMessage = t('Profile picture upload failed. Please try again.');
                            }
                        }
                    }
                }
            }
        }
    }

    if ($errorMessage === null) {
        $updated = false;
        try {
            $updated = User::updateProfile(
                (int)$user['id'],
                $name,
                $email === '' ? null : $email,
                $newProfilePicture
            );
        } catch (PDOException $exception) {
            error_log('Account profile update failed: ' . $exception->getMessage());
            $errorMessage = t('Profile could not be saved. Please try again.');
        }

        if ($updated) {
            if ($newProfilePicture !== null) {
                $oldProfilePicture = (string)($profile['profile_picture'] ?? '');
                if (preg_match('/\Aprofile_[a-f0-9]{32}\.(?:jpg|png|webp)\z/', $oldProfilePicture)) {
                    $oldPicturePath = __DIR__ . '/assets/upload/' . $oldProfilePicture;
                    if (is_file($oldPicturePath) && !unlink($oldPicturePath)) {
                        error_log('Could not remove replaced account profile picture: ' . $oldPicturePath);
                    }
                }
            }
            $_SESSION['profile_flash'] = t('Profile updated.');
            header('Location: profile.php');
            exit;
        }

        if ($errorMessage === null) {
            $errorMessage = t('Profile could not be saved. Please try again.');
        }
    }

    if ($errorMessage !== null && $newProfilePicturePath !== null && is_file($newProfilePicturePath)) {
        if (!unlink($newProfilePicturePath)) {
            error_log('Could not remove an unsaved account profile picture: ' . $newProfilePicturePath);
        }
    }
}

$storedProfilePicture = (string)($profile['profile_picture'] ?? '');
$profilePicturePath = __DIR__ . '/assets/upload/' . $storedProfilePicture;
$profilePictureUrl = preg_match('/\Aprofile_[a-f0-9]{32}\.(?:jpg|png|webp)\z/', $storedProfilePicture)
    && is_file($profilePicturePath)
    ? 'assets/upload/' . rawurlencode($storedProfilePicture)
    : null;
?>
<!DOCTYPE html>
<html lang="<?= e(app_language_tag()) ?>" data-theme="<?= e(app_theme()) ?>"
      data-compact="<?= app_compact_mode() ? '1' : '0' ?>">
<head>
  <meta charset="UTF-8">
  <title><?= e(t('Profile')) ?> — ACLC</title>
  <?php include __DIR__ . '/includes/pwa_head.php'; ?>
  <link rel="stylesheet" href="assets/css/student_profile.css?v=1">
</head>
<body>
<main class="settings-main student-profile-main">
  <header class="settings-header">
    <a class="settings-back" href="<?= e($dashboardUrl) ?>">← <?= e(t('Back to Dashboard')) ?></a>
    <h1><?= e(t('Profile')) ?></h1>
    <p><?= e(t('Manage your profile details.')) ?></p>
  </header>

  <?php if (is_string($successMessage)): ?>
    <div class="settings-flash success" role="status"><?= e($successMessage) ?></div>
  <?php endif; ?>
  <?php if (is_string($errorMessage)): ?>
    <div class="settings-flash" role="alert"><?= e($errorMessage) ?></div>
  <?php endif; ?>

  <form class="settings-card student-profile-form" method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
    <section class="settings-section">
      <div class="settings-section-heading">
        <span class="settings-icon profile-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"></circle><path d="M5 21a7 7 0 0 1 14 0"></path></svg>
        </span>
        <div>
          <h2><?= e(t('Profile information')) ?></h2>
        </div>
      </div>

      <div class="student-profile-photo-row">
        <?php if ($profilePictureUrl !== null): ?>
          <img class="student-profile-photo" src="<?= e($profilePictureUrl) ?>" alt="<?= e(t('Profile picture')) ?>">
        <?php else: ?>
          <span class="student-profile-photo student-profile-photo-empty" aria-hidden="true">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"></circle><path d="M5 21a7 7 0 0 1 14 0"></path></svg>
          </span>
        <?php endif; ?>
        <div class="student-profile-upload">
          <label class="settings-field" for="profilePicture">
            <span><?= e(t('Profile picture')) ?></span>
            <input id="profilePicture" name="profile_picture" type="file" accept="image/jpeg,image/png,image/webp">
          </label>
          <small><?= e(t('JPG, PNG, or WebP. Maximum file size: 5 MB.')) ?></small>
        </div>
      </div>

      <label class="settings-field" for="profileName">
        <span><?= e(t('Name')) ?></span>
        <input id="profileName" name="name" type="text" value="<?= e($name) ?>"
               autocomplete="name" maxlength="120" required>
      </label>

      <label class="settings-field" for="profileEmail">
        <span><?= e(t('Email')) ?></span>
        <input id="profileEmail" name="email" type="email" value="<?= e($email) ?>"
               autocomplete="email" maxlength="160">
        <small><?= e(t('Email is optional.')) ?></small>
      </label>

      <div class="student-profile-id">
        <span><?= e($idLabel) ?></span>
        <strong><?= e((string)$profile['user_id']) ?></strong>
        <small><?= e(t('This ID cannot be changed here.')) ?></small>
      </div>
    </section>

    <div class="settings-actions">
      <button class="settings-primary" type="submit"><?= e(t('Save profile')) ?></button>
    </div>
  </form>
</main>
</body>
</html>
