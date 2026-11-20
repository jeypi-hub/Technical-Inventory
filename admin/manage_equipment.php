<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Equipment.php';
require_once __DIR__ . '/../classes/BorrowRequest.php';
$user = User::requirePermission('equipment.manage');

$flash = $_GET['flash'] ?? '';
$search = trim($_GET['q'] ?? '');
$items = Equipment::all($search ?: null);
$catalogItems = $search ? Equipment::all() : $items;
$groups = [];
foreach ($items as $item) {
    $name = $item['name'];
    if (!isset($groups[$name])) {
        $groups[$name] = [
            'name' => $name,
            'image' => null,
            'quantity' => 0,
            'available' => 0,
            'pending' => 0,
            'borrowed' => 0,
            'overdue' => 0,
            'lost' => 0,
            'maintenance' => 0,
            'items' => [],
        ];
    }
    $groups[$name]['items'][] = $item;
}
foreach ($catalogItems as $item) {
    $name = $item['name'];
    if (isset($groups[$name])) {
        $groups[$name]['quantity']++;
        $st = $item['status'];
        if (isset($groups[$name][$st])) {
            $groups[$name][$st]++;
        }
        if (!$groups[$name]['image'] && !empty($item['image'])) {
            $groups[$name]['image'] = $item['image'];
        }
    }
}
$pageTitle = 'Manage Catalog';
?>
<!DOCTYPE html>
<html lang="<?= e(app_language_tag()) ?>" data-theme="<?= e(app_theme()) ?>" data-compact="<?= app_compact_mode() ? '1' : '0' ?>">
<head>
<meta charset="UTF-8">
<title><?= e(t('Manage Catalog')) ?> — ACLC</title>
<link rel="stylesheet" href="../assets/css/admin_dashboard.css?v=16">
<link rel="stylesheet" href="../assets/css/manage_equipment.css?v=8">
<?php include __DIR__ . '/../includes/pwa_head.php'; ?>
</head>
<body>
<div class="app-shell">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <main class="main">
    <?php include __DIR__ . '/includes/header.php'; ?>

    <?php if ($flash): ?><div class="flash"><?= e(t($flash)) ?></div><?php endif; ?>

    <section class="card catalog-card">
      <div class="card-head">
        <h2><?= e(t('Manage Equipment Catalog')) ?></h2>
        <div class="catalog-tools">
          <form method="get" class="searchbar">
            <input type="text" name="q" placeholder="<?= e(t('Search')) ?>" value="<?= e($search) ?>">
            <button type="submit">🔍</button>
          </form>
          <button type="button" class="btn-primary catalog-add-button" id="openAddEquipment">
            <span aria-hidden="true">+</span> <?= e(t('Add Equipment')) ?>
          </button>
        </div>
      </div>
      <?php if (empty($groups)): ?>
        <p class="catalog-empty"><?= e(t('No equipment yet.')) ?></p>
      <?php else: ?>
        <div class="equipment-grid">
          <?php foreach ($groups as $group): ?>
            <?php
              $popupData = [
                  'name' => $group['name'],
                  'image' => $group['image'],
                  'quantity' => $group['quantity'],
                  'available' => $group['available'],
                  'pending' => $group['pending'],
                  'borrowed' => $group['borrowed'],
                  'overdue' => $group['overdue'],
                  'lost' => $group['lost'],
                  'maintenance' => $group['maintenance'],
                  'items' => array_map(static function ($item) {
                      return [
                          'id' => (int) $item['id'],
                          'serial_number' => $item['serial_number'],
                          'status' => $item['status'],
                      ];
                  }, $group['items']),
              ];
            ?>
            <button type="button" class="equipment-tile"
                    data-group="<?= e(json_encode($popupData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>"
                    aria-label="<?= e(t('View details for')) ?> <?= e($group['name']) ?>">
              <span class="equipment-image">
                <?php if ($group['image']): ?>
                  <img src="../assets/upload/<?= e($group['image']) ?>" alt="">
                <?php else: ?>
                  <span class="equipment-image-empty" aria-hidden="true"></span>
                <?php endif; ?>
                <span class="equipment-image-shade" aria-hidden="true"></span>
                <span class="equipment-availability">
                  <?= e(str_replace('{count}', (string)$group['available'], t('{count} available'))) ?>
                </span>
                <span class="equipment-card-footer">
                  <strong class="equipment-name"><?= e($group['name']) ?></strong>
                  <span><?= e(t('Click to view')) ?></span>
                </span>
              </span>
            </button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <div class="equipment-add-backdrop" id="addEquipmentModal" aria-hidden="true">
      <section class="equipment-add-modal" role="dialog" aria-modal="true" aria-labelledby="addEquipmentTitle">
        <div class="equipment-add-heading">
          <div>
            <h2 id="addEquipmentTitle"><?= e(t('Add New Technical Equipment')) ?></h2>
            <p><?= e(t('Add equipment details to your catalog.')) ?></p>
          </div>
          <button type="button" class="catalog-modal-close" id="closeAddEquipment" aria-label="<?= e(t('Close')) ?>">×</button>
        </div>
        <form method="post" action="actions.php" enctype="multipart/form-data" class="equipment-add-form">
          <input type="hidden" name="do" value="add_equipment">
          <label for="equipmentName"><?= e(t('Equipment Name')) ?></label>
          <input type="text" id="equipmentName" name="name" required maxlength="120" placeholder="<?= e(t('e.g. HDMI Cable')) ?>">

          <label for="equipmentQuantity"><?= e(t('Quantity')) ?></label>
          <input type="number" id="equipmentQuantity" name="quantity" required min="1" step="1" value="1">
          <small class="form-hint"><?= e(t('Each item will receive its own serial number.')) ?></small>

          <label><?= e(t('Equipment Image')) ?></label>
          <label class="dropzone" id="dropzone">
            <input type="file" name="image" accept="image/*" id="imageInput" hidden>
            <span class="dz-inner">
              <span class="dz-prompt"><?= e(t('Choose File or Drag File')) ?></span>
              <span class="plus" aria-hidden="true">⊕</span>
              <small id="fileName"></small>
            </span>
          </label>
          <div class="equipment-add-actions">
            <button type="button" class="btn-secondary" id="cancelAddEquipment"><?= e(t('Cancel')) ?></button>
            <button type="submit" class="btn-primary"><?= e(t('Add Equipment')) ?></button>
          </div>
        </form>
      </section>
    </div>

    <div class="catalog-modal-backdrop" id="catalogModal" aria-hidden="true">
      <section class="catalog-modal" role="dialog" aria-modal="true" aria-labelledby="catalogModalTitle">
        <div class="catalog-modal-image" id="catalogModalImage"></div>
        <div class="catalog-modal-content">
          <div class="catalog-status-card">
            <div>
              <span class="catalog-detail-label"><?= e(t('Inventory status')) ?></span>
              <strong id="catalogModalStatus"></strong>
            </div>
            <button type="button" class="catalog-modal-close" id="catalogModalClose" aria-label="<?= e(t('Close')) ?>">×</button>
          </div>
          <div class="catalog-modal-items" id="catalogModalItems"></div>
        </div>
      </section>
    </div>
  </main>
</div>
<script src="../assets/js/admin.js?v=15"></script>
</body>
</html>
