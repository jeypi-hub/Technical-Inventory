<?php
// history_log.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

$filter = $_GET['history_filter'] ?? 'this_month';
$current_year = date('Y');

// Updated query to include u.course
$query_str = "SELECT br.*, u.name as borrower_name, u.student_id, u.course, e.name as equipment_name, e.serial_number FROM borrow_requests br JOIN users u ON br.user_id = u.id JOIN equipment e ON br.equipment_id = e.id";

if ($filter === 'this_month') {
    $current_month_prefix = date('Y-m');
    $stmt_hist = $pdo->prepare("$query_str WHERE DATE_FORMAT(br.created_at, '%Y-%m') = ? ORDER BY br.created_at DESC");
    $stmt_hist->execute([$current_month_prefix]);
    $history_logs = $stmt_hist->fetchAll();
    $filter_label = "This_Month";
} elseif ($filter === 'this_year') {
    $stmt_hist = $pdo->prepare("$query_str WHERE YEAR(br.created_at) = ? ORDER BY br.created_at DESC");
    $stmt_hist->execute([$current_year]);
    $history_logs = $stmt_hist->fetchAll();
    $filter_label = "This_Year";
} else {
    $history_logs = $pdo->query("$query_str ORDER BY br.created_at DESC")->fetchAll();
    $filter_label = "All_Time";
}
?>

<div style="background: white; padding: 24px; border-radius: 12px; border: 1px solid #e2e8f0;">
    <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; margin-bottom: 16px; gap: 12px;">
        <h3 style="font-size: 16px; font-weight: bold; color: #312e81; margin: 0;">Complete Borrowing History Log</h3>
        
        <!-- Controls: Search, Filter, and CSV Download -->
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <!-- Search Bar -->
            <div style="position: relative;">
                <input type="text" id="historySearch" placeholder="Search history..." style="border: 1px solid #cbd5e1; padding: 6px 12px 6px 32px; border-radius: 20px; font-size: 13px; outline: none; width: 180px;" onkeyup="filterHistoryTable()">
                <svg style="width: 14px; height: 14px; color: #94a3b8; position: absolute; left: 11px; top: 10px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>

            <!-- Filter Dropdown -->
            <form method="GET" action="dashboard.php" style="margin: 0;">
                <input type="hidden" name="tab" value="history">
                <select name="history_filter" onchange="this.form.submit()" style="border: 1px solid #cbd5e1; color: #6b21a8; background: #faf5ff; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 500; cursor: pointer; outline: none;">
                    <option value="this_month" <?= $filter === 'this_month' ? 'selected' : '' ?>>This Month</option>
                    <option value="this_year" <?= $filter === 'this_year' ? 'selected' : '' ?>>This Year</option>
                    <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All Time</option>
                </select>
            </form>

            <!-- CSV Export Button -->
            <button onclick="exportHistoryCSV('Borrowing_History_<?= $filter_label ?>')" style="background: #059669; color: white; border: none; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 500; cursor: pointer; display: flex; align-items: center; gap: 6px;" title="Download filtered view as CSV">
                <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Export Excel
            </button>
        </div>
    </div>

    <?php if(empty($history_logs)): ?>
        <p style="font-size: 14px; color: #64748b; margin: 0;">No history records found for this period.</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table id="historyTable" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
                <thead style="background: #f8fafc; color: #475569;">
                    <tr>
                        <th style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0;">ID</th>
                        <th style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0;">Date & Time</th>
                        <th style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0;">Student / ID / Course</th>
                        <th style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0;">Equipment & Serial No.</th>
                        <th style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0;">Classroom</th>
                        <th style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0;">Teacher</th>
                        <th style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0;">Purpose</th>
                        <th style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0;">Status</th>
                    </tr>
                </thead>
                <tbody id="historyTableBody">
                    <?php foreach($history_logs as $log): ?>
                        <tr class="history-row" style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 10px 12px; color: #334155;">#<?= $log['id'] ?></td>
                            <td style="padding: 10px 12px; color: #64748b; white-space: nowrap;">
                                <?= isset($log['created_at']) ? date('M d, Y h:i A', strtotime($log['created_at'])) : 'N/A' ?>
                            </td>
                            <td style="padding: 10px 12px; color: #1e293b;">
                                <div style="font-weight: 600; color: #0f172a;"><?= htmlspecialchars($log['borrower_name']) ?></div>
                                <div style="font-size: 11px; font-weight: 600; color: #4338ca; margin-top: 1px;"><?= htmlspecialchars($log['course'] ?? 'No Course Specified') ?></div>
                                <div style="font-size: 11px; color: #64748b; font-family: monospace;">(<?= htmlspecialchars($log['student_id']) ?>)</div>
                            </td>
                            <td style="padding: 10px 12px;">
                                <?= htmlspecialchars($log['equipment_name']) ?> [SN: <?= htmlspecialchars($log['serial_number']) ?>]
                            </td>
                            <td style="padding: 10px 12px; color: #475569;"><?= htmlspecialchars($log['classroom']) ?></td>
                            <td style="padding: 10px 12px; color: #475569;"><?= htmlspecialchars($log['teacher_name']) ?></td>
                            <td style="padding: 10px 12px; color: #475569;"><?= htmlspecialchars($log['purpose']) ?></td>
                            <td style="padding: 10px 12px;">
                                <span style="padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; background: <?= $log['status'] === 'Returned' ? '#d1fae5' : ($log['status'] === 'Approved' ? '#dbeafe' : ($log['status'] === 'Rejected' ? '#ffe4e6' : '#fef3c7')) ?>; color: <?= $log['status'] === 'Returned' ? '#065f46' : ($log['status'] === 'Approved' ? '#1e40af' : ($log['status'] === 'Rejected' ? '#9f1239' : '#92400e')) ?>;">
                                    <?= $log['status'] ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
    function filterHistoryTable() {
        const input = document.getElementById('historySearch');
        const filter = input.value.toLowerCase();
        const rows = document.querySelectorAll('.history-row');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    }

    function exportHistoryCSV(filename) {
        let csv = [];
        let rows = document.querySelectorAll("#historyTable tr");
        
        for (let i = 0; i < rows.length; i++) {
            if (rows[i].style.display === 'none') continue;

            let row = [], cols = rows[i].querySelectorAll("td, th");
            for (let j = 0; j < cols.length; j++) {
                let text = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, " ").trim();
                text = text.replace(/"/g, '""'); 
                row.push('"' + text + '"');
            }
            csv.push(row.join(","));
        }

        let csvFile = new Blob(["\ufeff" + csv.join("\n")], { type: "text/csv;charset=utf-8;" });
        let downloadLink = document.createElement("a");
        downloadLink.download = filename + ".csv";
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.style.display = "none";
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
    }
</script>