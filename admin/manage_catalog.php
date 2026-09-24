<?php
// manage_catalog.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_equipment') {
    $equipment_id = $_POST['equipment_id'];
    $stmt = $pdo->prepare("DELETE FROM borrow_requests WHERE equipment_id = ?");
    $stmt->execute([$equipment_id]);
    $stmt = $pdo->prepare("DELETE FROM equipment WHERE id = ?");
    $stmt->execute([$equipment_id]);
    $message = "Equipment permanently deleted from catalog.";
}

$equipment_catalog = $pdo->query("SELECT * FROM equipment ORDER BY id DESC")->fetchAll();
?>

<div class="bg-white p-6 rounded-xl shadow-sm border mb-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-3">
        <h3 class="text-base font-bold text-indigo-900">Manage Equipment Catalog</h3>
        <input type="text" id="catalogSearch" placeholder="Search catalog..." class="border px-3.5 py-1.5 rounded-lg text-sm w-full sm:w-64 focus:outline-none focus:ring-2 focus:ring-indigo-500">
    </div>

    <?php if($message): ?>
        <div class="bg-emerald-100 text-emerald-700 p-3 rounded mb-4 text-sm"><?= $message ?></div>
    <?php endif; ?>

    <?php if(empty($equipment_catalog)): ?>
        <p class="text-sm text-slate-500">No equipment found in the catalog.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b text-slate-600">
                        <th class="p-2.5">ID</th>
                        <th class="p-2.5">Equipment Name</th>
                        <th class="p-2.5">Serial Number</th>
                        <th class="p-2.5">Status</th>
                        <th class="p-2.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="catalogTableBody">
                    <?php foreach($equipment_catalog as $eq): ?>
                        <tr class="catalog-row border-b hover:bg-slate-50">
                            <td class="p-2.5">#<?= $eq['id'] ?></td>
                            <td class="p-2.5 font-medium text-slate-800"><?= htmlspecialchars($eq['name']) ?></td>
                            <td class="p-2.5 text-slate-600 font-mono text-xs"><?= htmlspecialchars($eq['serial_number']) ?></td>
                            <td class="p-2.5">
                                <span class="px-2 py-0.5 text-xs font-bold rounded-full <?= $eq['status'] == 'Available' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' ?>">
                                    <?= $eq['status'] ?>
                                </span>
                            </td>
                            <td class="p-2.5 text-right">
                                <form method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this equipment?');" class="inline">
                                    <input type="hidden" name="action" value="delete_equipment">
                                    <input type="hidden" name="equipment_id" value="<?= $eq['id'] ?>">
                                    <button type="submit" class="bg-rose-600 text-white px-3 py-1 rounded-md text-xs hover:bg-rose-700 transition">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
    const catalogSearch = document.getElementById('catalogSearch');
    if (catalogSearch) {
        catalogSearch.addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.catalog-row');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
            });
        });
    }
</script>