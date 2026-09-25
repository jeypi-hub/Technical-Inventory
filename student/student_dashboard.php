<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Equipment.php';
require_once __DIR__ . '/../classes/BorrowRequest.php';
require_once __DIR__ . '/../classes/Notification.php';
$user = User::requireLogin('student');

$search = trim($_GET['q'] ?? '');
$items  = Equipment::all($search ?: null);
$notifs = Notification::forUser((int)$user['id'], 10);
$unread = Notification::unreadCount((int)$user['id']);
$flash = $_GET['flash'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Dashboard — ACLC</title>
<link rel="stylesheet" href="../assets/css/student_dashboard.css">
</head>
<body>
<header class="stud-topbar">
  <div class="stud-brand">
    <img src="../assets/images/aclc_logo.png" alt="ACLC" class="brand-logo">
    <div>
      <strong>ACLC COLLEGE</strong>
      <span>MANDAUE CITY</span>
    </div>
  </div>
  <div class="stud-right">
    <div class="notif">
      <button class="notif-btn" onclick="toggleNotif()">
        <!-- Bell Icon -->
        <svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
        <?php if ($unread): ?><span class="dot"></span><?php endif; ?>
      </button>
      <div class="notif-panel" id="notifPanel">
        <div class="notif-head">
          <strong>Notifications</strong>
          <a href="actions.php?do=notif_read" class="mark-read">Mark all read</a>
        </div>
        <?php if (empty($notifs)): ?>
          <div class="notif-empty">No notifications yet.</div>
        <?php else: foreach ($notifs as $n): ?>
          <div class="notif-item <?= $n['is_read']?'':'unread' ?>">
            <span class="ni-type <?= e($n['type']) ?>"></span>
            <div>
              <p><?= e($n['message']) ?></p>
              <small><?= e(date('M d, Y h:i A', strtotime($n['created_at']))) ?></small>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
    <div class="profile" onclick="toggleProfile()">
      <!-- User Avatar Icon -->
      <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
      <div class="profile-info">
        <strong>USER <?= e($user['name']) ?></strong>
        <span><?= e($user['course']) ?></span>
      </div>
      <div class="profile-panel" id="profilePanel">
        <div class="pp-head">
          <strong><?= e($user['name']) ?></strong>
          <span><?= e($user['course']) ?> · Student</span>
        </div>
        <a href="history.php" class="pp-item">
          <!-- Clock/History Icon -->
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
          Borrowing Request History ›
        </a>
        <a href="../auth/student_logout.php" class="pp-logout">
          <!-- Logout Icon -->
          <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
          Logout
        </a>
      </div>
    </div>
  </div>
</header>

<main class="stud-main">
  <?php if ($flash): ?><div class="flash"><?= e($flash) ?></div><?php endif; ?>
  <form method="get" class="searchbar wide">
    <input type="text" name="q" placeholder="Search" value="<?= e($search) ?>">
    <button type="submit">
      <!-- Search Magnifying Glass Icon -->
      <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
    </button>
  </form>

  <section class="equip-grid">
    <?php foreach ($items as $it):
      $isAvailable = $it['status'] === 'available'; ?>
      <article class="equip-card <?= $isAvailable ? '' : 'unavailable' ?>">
        <div class="equip-img">
          <?php if (!empty($it['image'])): ?>
            <img src="../assets/upload/<?= e($it['image']) ?>" alt="">
          <?php else: ?>
            <div class="placeholder"></div>
          <?php endif; ?>
        </div>
        <?php if ($isAvailable): ?>
          <h3><?= e($it['name']) ?></h3>
          <p class="serial">Serial: <?= e($it['serial_number']) ?></p>
          <span class="pill available">Available</span>
          <form method="post" action="actions.php" class="req-form">
            <input type="hidden" name="do" value="request">
            <input type="hidden" name="equipment_id" value="<?= (int)$it['id'] ?>">
            <input type="text" name="purpose"   placeholder="Purpose (e.g. Lab Work)" required>
            <input type="text" name="classroom" placeholder="Classroom (e.g. Lab 5)" required>
            <input type="text" name="teacher"   placeholder="Teacher's Name" required>
            <button type="submit" class="btn-primary">Request Item</button>
          </form>
        <?php else: ?>
          <div class="unavail-space"></div>
          <h3><?= e($it['name']) ?></h3>
          <p class="serial">Serial: <?= e($it['serial_number']) ?></p>
          <span class="pill borrowed">Borrowed</span>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
    <?php if (empty($items)): ?>
      <p class="empty">No equipment yet.</p>
    <?php endif; ?>
  </section>
</main>
<script src="../assets/js/student.js"></script>
</body>
</html>