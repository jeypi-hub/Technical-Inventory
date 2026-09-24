<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'return_item') {
    $request_id = $_POST['request_id'];
    $equipment_id = $_POST['equipment_id'];

    $stmt = $pdo->prepare("UPDATE borrow_requests SET status = 'Returned', condition_on_return = 'Good' WHERE id = ?");
    $stmt->execute([$request_id]);

    $stmt = $pdo->prepare("UPDATE equipment SET status = 'Available' WHERE id = ?");
    $stmt->execute([$equipment_id]);

    $message = "Item successfully returned and set back to Available!";
}

// Fetch Stats Counts
$total_equipment = $pdo->query("SELECT COUNT(*) FROM equipment")->fetchColumn();
$available_equipment = $pdo->query("SELECT COUNT(*) FROM equipment WHERE status = 'Available'")->fetchColumn();
$pending_count = $pdo->query("SELECT COUNT(*) FROM borrow_requests WHERE status = 'Pending'")->fetchColumn();
$borrowing_count = $pdo->query("SELECT COUNT(*) FROM borrow_requests WHERE status = 'Approved'")->fetchColumn();
$returned_count = $pdo->query("SELECT COUNT(*) FROM borrow_requests WHERE status = 'Returned'")->fetchColumn();

// Fetch Monthly Bar Chart Data (Current Year)
$current_year = date('Y');
$monthly_stats = [];
for ($m = 1; $m <= 12; $m++) {
    $month_str = str_pad($m, 2, '0', STR_PAD_LEFT);
    $stmt_b = $pdo->prepare("SELECT COUNT(*) FROM borrow_requests WHERE status IN ('Approved', 'Returned') AND DATE_FORMAT(created_at, '%Y-%m') = ?");
    $stmt_b->execute(["$current_year-$month_str"]);
    $borrowed_val = $stmt_b->fetchColumn();

    $stmt_r = $pdo->prepare("SELECT COUNT(*) FROM borrow_requests WHERE status = 'Returned' AND DATE_FORMAT(created_at, '%Y-%m') = ?");
    $stmt_r->execute(["$current_year-$month_str"]);
    $returned_val = $stmt_r->fetchColumn();

    $monthly_stats[] = [
        'month' => date('M', mktime(0, 0, 0, $m, 10)),
        'borrowed' => (int)$borrowed_val,
        'returned' => (int)$returned_val
    ];
}

$max_chart_val = 10;
foreach ($monthly_stats as $stat) {
    if ($stat['borrowed'] > $max_chart_val) $max_chart_val = $stat['borrowed'];
    if ($stat['returned'] > $max_chart_val) $max_chart_val = $stat['returned'];
}

$active_borrowed = $pdo->query("SELECT br.*, u.name as borrower_name, u.student_id, e.name as equipment_name, e.serial_number FROM borrow_requests br JOIN users u ON br.user_id = u.id JOIN equipment e ON br.equipment_id = e.id WHERE br.status = 'Approved'")->fetchAll();
?>

<?php if($message): ?>
    <div style="background: #d1fae5; color: #065f46; padding: 12px; border-radius: 8px; margin-bottom: 24px; font-size: 14px;"><?= $message ?></div>
<?php endif; ?>

<!-- Statistics Overview Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div style="background: white; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
        <p style="font-size: 12px; color: #64748b; margin: 0 0 4px 0;">Total Equipment</p>
        <p style="font-size: 24px; font-weight: bold; color: #312e81; margin: 0;"><?= $total_equipment ?></p>
    </div>
    <div style="background: white; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
        <p style="font-size: 12px; color: #64748b; margin: 0 0 4px 0;">Available</p>
        <p style="font-size: 24px; font-weight: bold; color: #059669; margin: 0;"><?= $available_equipment ?></p>
    </div>
    <div style="background: white; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
        <p style="font-size: 12px; color: #64748b; margin: 0 0 4px 0;">Pending</p>
        <p style="font-size: 24px; font-weight: bold; color: #d97706; margin: 0;"><?= $pending_count ?></p>
    </div>
    <div style="background: white; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
        <p style="font-size: 12px; color: #64748b; margin: 0 0 4px 0;">Borrowing</p>
        <p style="font-size: 24px; font-weight: bold; color: #2563eb; margin: 0;"><?= $borrowing_count ?></p>
    </div>
    <div style="background: white; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
        <p style="font-size: 12px; color: #64748b; margin: 0 0 4px 0;">Returned</p>
        <p style="font-size: 24px; font-weight: bold; color: #475569; margin: 0;"><?= $returned_count ?></p>
    </div>
</div>

<!-- Monthly Activity Chart -->
<div style="background: white; padding: 24px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h3 style="font-size: 16px; font-weight: bold; color: #312e81; margin: 0;">Monthly Equipment Activity (<?= $current_year ?>)</h3>
            <p style="font-size: 12px; color: #64748b; margin: 4px 0 0 0;">Overview of item checkouts and returns per month</p>
        </div>
        <div style="display: flex; gap: 16px; font-size: 12px; font-weight: 500;">
            <span style="display: flex; align-items: center; gap: 6px;"><span style="width: 12px; height: 12px; border-radius: 3px; background: #6366f1; display: inline-block;"></span> Borrowed</span>
            <span style="display: flex; align-items: center; gap: 6px;"><span style="width: 12px; height: 12px; border-radius: 3px; background: #10b981; display: inline-block;"></span> Returned</span>
        </div>
    </div>

    <div style="height: 200px; display: flex; align-items: flex-end; gap: 12px; padding-top: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;">
        <?php foreach($monthly_stats as $stat): 
            $b_height = ($max_chart_val > 0) ? ($stat['borrowed'] / $max_chart_val) * 100 : 0;
            $r_height = ($max_chart_val > 0) ? ($stat['returned'] / $max_chart_val) * 100 : 0;
            $b_display_h = ($stat['borrowed'] > 0 && $b_height < 8) ? 8 : $b_height;
            $r_display_h = ($stat['returned'] > 0 && $r_height < 8) ? 8 : $r_height;
        ?>
            <div style="flex: 1; display: flex; flex-direction: column; align-items: center; height: 100%; justify-content: flex-end;">
                <div style="width: 100%; display: flex; align-items: flex-end; justify-content: center; gap: 2px; height: 100%;">
                    <div style="height: <?= $b_display_h ?>%; width: 45%; background: #6366f1; border-top-left-radius: 4px; border-top-right-radius: 4px; position: relative;">
                        <?php if($stat['borrowed'] > 0): ?>
                            <span style="position: absolute; top: -18px; left: 50%; transform: translateX(-50%); font-size: 10px; font-weight: bold; color: #4f46e5;"><?= $stat['borrowed'] ?></span>
                        <?php endif; ?>
                    </div>
                    <div style="height: <?= $r_display_h ?>%; width: 45%; background: #10b981; border-top-left-radius: 4px; border-top-right-radius: 4px; position: relative;">
                        <?php if($stat['returned'] > 0): ?>
                            <span style="position: absolute; top: -18px; left: 50%; transform: translateX(-50%); font-size: 10px; font-weight: bold; color: #059669;"><?= $stat['returned'] ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <span style="font-size: 12px; color: #475569; font-weight: 500; margin-top: 8px;"><?= $stat['month'] ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Active Checkouts Section with Search Bar -->
<div style="background: white; padding: 24px; border-radius: 12px; border: 1px solid #e2e8f0;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
        <h3 style="font-size: 16px; font-weight: bold; color: #312e81; margin: 0;">Active Checkouts</h3>
        
        <!-- Search Bar for Active Checkouts -->
        <div style="position: relative;">
            <input type="text" id="activeSearch" placeholder="Search active checkouts..." style="border: 1px solid #cbd5e1; padding: 6px 12px 6px 32px; border-radius: 20px; font-size: 13px; outline: none; width: 220px;" onkeyup="filterActiveCheckouts()">
            <svg style="width: 14px; height: 14px; color: #94a3b8; position: absolute; left: 11px; top: 10px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        </div>
    </div>

    <?php if(empty($active_borrowed)): ?>
        <p style="font-size: 14px; color: #64748b; margin: 0;">No items currently checked out.</p>
    <?php else: ?>
        <div id="activeCheckoutsList" style="display: flex; flex-direction: column; gap: 12px;">
            <?php foreach($active_borrowed as $act): ?>
                <div class="checkout-item" style="border: 1px solid #fde68a; padding: 14px; border-radius: 10px; display: flex; justify-content: space-between; align-items: center; background: #fffbeb;">
                    <div>
                        <p style="font-weight: bold; color: #1e293b; font-size: 14px; margin: 0 0 4px 0;"><?= htmlspecialchars($act['equipment_name']) ?> <span style="font-family: monospace; font-size: 12px; color: #64748b;">(SN: <?= htmlspecialchars($act['serial_number']) ?>)</span></p>
                        <p style="font-size: 12px; color: #475569; margin: 0;">Borrowed by: <b><?= htmlspecialchars($act['borrower_name']) ?></b> (ID: <?= $act['student_id'] ?>) | Room: <?= htmlspecialchars($act['classroom']) ?></p>
                    </div>
                    <form method="POST" style="margin: 0;">
                        <input type="hidden" name="action" value="return_item">
                        <input type="hidden" name="request_id" value="<?= $act['id'] ?>">
                        <input type="hidden" name="equipment_id" value="<?= $act['equipment_id'] ?>">
                        <button type="submit" style="background: #2563eb; color: white; padding: 6px 12px; border-radius: 6px; font-size: 12px; border: none; cursor: pointer;">Mark as Returned</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    function filterActiveCheckouts() {
        const input = document.getElementById('activeSearch');
        const filter = input.value.toLowerCase();
        const items = document.querySelectorAll('.checkout-item');

        items.forEach(item => {
            const text = item.textContent.toLowerCase();
            item.style.display = text.includes(filter) ? 'flex' : 'none';
        });
    }
</script>