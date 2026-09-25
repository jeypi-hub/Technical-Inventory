<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Equipment.php';
$user = User::requireLogin('admin');

$flash = $_GET['flash'] ?? '';
$search = trim($_GET['q'] ?? '');
$items = Equipment::all($search ?: null);
$pageTitle = 'Manage Catalog';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Catalog — ACLC</title>
<link rel="stylesheet" href="../assets/css/admin_dashboard.css">
<link rel="stylesheet" href="../assets/css/manage_equipment.css">
</head>
<body>
<div class="app-shell">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <main class="main">
    <?php include __DIR__ . '/includes/header.php'; ?>

    <?php if ($flash): ?><div class="flash"><?= e($flash) ?></div><?php endif; ?>

    <section class="card add-card">
      <div class="add-form">
        <div class="add-left">
          <h2>Add New Technical Equipment</h2>
          <form method="post" action="actions.php" enctype="multipart/form-data">
            <input type="hidden" name="do" value="add_equipment">
            <label>Equipment Name</label>
            <input type="text" name="name" required maxlength="120" placeholder="e.g. HDMI Cable">
            <button type="submit" class="btn-primary">Add Equipment</button>
        </div>
        <div class="add-right">
          <label>Equipment Image</label>
          <label class="dropzone" id="dropzone">
            <input type="file" name="image" accept="image/*" id="imageInput" hidden>
            <div class="dz-inner">
              <p>Choose File or Drag File</p>
              <span class="plus">⊕</span>
              <small id="fileName"></small>
            </div>
          </label>
          </form>
        </div>
      </div>
    </section>

    <section class="card table-card">
      <div class="card-head">
        <h2>Manage Equipment Catalog</h2>
        <form method="get" class="searchbar">
          <input type="text" name="q" placeholder="Search" value="<?= e($search) ?>">
          <button type="submit">🔍</button>
        </form>
      </div>
      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr><th>No</th><th>Equipment Name</th><th>Serial Number</th><th>Status</th><th>Actions</th></tr>
          </thead>
          <tbody>
          <?php foreach ($items as $i => $it): ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td>
                <?php if (!empty($it['image'])): ?>
                  <img src="../assets/upload/<?= e($it['image']) ?>" class="thumb" alt="">
                <?php endif; ?>
                <?= e($it['name']) ?>
              </td>
              <td><?= e($it['serial_number']) ?></td>
              <td><span class="status <?= e($it['status']) ?>"><?= ucfirst(e($it['status'])) ?></span></td>
              <td>
                <a class="link-del" href="actions.php?do=delete_equipment&id=<?= (int)$it['id'] ?>"
                   onclick="return confirm('Delete this equipment?')">Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($items)): ?>
            <tr><td colspan="5" class="empty">No equipment yet.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</div>
<script src="../assets/js/admin.js"></script>
</body>
</html>
