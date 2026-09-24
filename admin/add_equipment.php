<?php
// add_equipment.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_equipment') {
    $name = $_POST['name'];
    $serial = 'SN-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
    $imageName = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../assets/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $imageName = time() . '_' . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $imageName);
    }

    $stmt = $pdo->prepare("INSERT INTO equipment (name, serial_number, status, image) VALUES (?, ?, 'Available', ?)");
    $stmt->execute([$name, $serial, $imageName]);
    $message = "New equipment added successfully with auto-generated serial number: $serial";
}
?>

<div class="bg-white p-6 rounded-xl shadow-sm border mb-6">
    <h3 class="text-base font-bold mb-3 text-indigo-900">Add New Technical Equipment</h3>
    
    <?php if($message): ?>
        <div class="bg-emerald-100 text-emerald-700 p-3 rounded mb-4 text-sm"><?= $message ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="grid md:grid-cols-2 gap-4 items-end">
        <input type="hidden" name="action" value="add_equipment">
        <div>
            <label class="block text-sm font-medium text-slate-700">Equipment Name</label>
            <input type="text" name="name" required class="w-full border p-2 rounded-lg mt-1 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Equipment Image</label>
            <input type="file" name="image" accept="image/*" class="w-full border p-1 rounded-lg mt-1 text-xs">
        </div>
        <div class="md:col-span-2">
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700 transition">Save Equipment to Catalog (Auto-Generate Serial)</button>
        </div>
    </form>
</div>