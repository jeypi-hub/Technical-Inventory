<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$tab = $_GET['tab'] ?? 'overview';

// Handle action from notification drawer if submitted there
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'admin_action') {
    $request_id = $_POST['request_id'];
    $req_status = $_POST['req_status']; 
    $equipment_id = $_POST['equipment_id'];

    $stmt = $pdo->prepare("UPDATE borrow_requests SET status = ? WHERE id = ?");
    $stmt->execute([$req_status, $request_id]);

    if ($req_status === 'Approved') {
        $stmt = $pdo->prepare("UPDATE equipment SET status = 'Borrowed' WHERE id = ?");
        $stmt->execute([$equipment_id]);
    }
    header("Location: dashboard.php?tab=" . $tab);
    exit;
}

// Fetch pending requests for the notification badge count and dropdown list
$pending_requests = $pdo->query("SELECT br.*, u.name as borrower_name, u.student_id, e.name as equipment_name, e.serial_number FROM borrow_requests br JOIN users u ON br.user_id = u.id JOIN equipment e ON br.equipment_id = e.id WHERE br.status = 'Pending'")->fetchAll();
$pending_count = count($pending_requests);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ACLC Admin Dashboard - CardLayout</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background-color: #f1f5f9; margin: 0; display: flex; min-height: 100vh; }
        
        .sidebar { 
            width: 17rem; 
            background-color: #0b2545; 
            color: white; 
            display: flex; 
            flex-direction: column; 
            position: sticky; 
            top: 0; 
            height: 100vh; 
            box-shadow: 4px 0 10px rgba(0,0,0,0.1); 
            z-index: 20; 
            transition: width 0.3s ease;
            overflow: hidden;
        }

        /* Collapsed Sidebar State */
        .sidebar.collapsed {
            width: 5rem;
        }
        .sidebar.collapsed .sidebar-text,
        .sidebar.collapsed .sidebar-header-text,
        .sidebar.collapsed .sidebar-title {
            display: none;
        }
        .sidebar.collapsed .sidebar-brand {
            padding: 20px 12px;
            justify-content: center;
        }
        .sidebar.collapsed .sidebar-logo {
            width: 40px;
            height: 40px;
        }
        .sidebar.collapsed nav a {
            justify-content: center;
            padding: 12px;
        }

        .main-content { flex: 1; display: flex; flex-direction: column; min-width: 0; }
    </style>
</head>
<body class="bg-slate-100 min-h-screen flex">

    <!-- Collapsible Sidebar -->
    <aside id="appSidebar" class="sidebar md:flex">
        <!-- Clickable Brand / Logo to Toggle Sidebar -->
        <div onclick="toggleSidebar()" class="sidebar-brand p-5 border-b border-blue-900 flex items-center gap-3" style="cursor: pointer;" title="Toggle Sidebar">
            <img src="../assets/aclc_logo.png" alt="ACLC Logo" class="sidebar-logo" style="width: 40px; height: 40px; object-fit: contain; background: transparent; flex-shrink: 0;">
            <div class="sidebar-title">
                <h1 class="font-bold text-base tracking-wide text-white leading-tight">ACLC COLLEGE</h1>
                <p class="sidebar-header-text text-[10px] text-blue-200 tracking-wider uppercase mt-0.5 font-medium">Mandaue City</p>
            </div>
        </div>
        
        <nav class="p-4 space-y-1.5 text-sm flex-1">
            <!-- Dashboard Overview Link -->
            <a href="dashboard.php?tab=overview" title="Dashboard Overview" onclick="preserveSidebarState()" style="display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: 8px; text-decoration: none; margin-bottom: 6px; color: <?= $tab === 'overview' ? '#fff' : '#cbd5e1' ?>; background: <?= $tab === 'overview' ? '#134074' : 'transparent' ?>;">
                <svg style="width: 20px; height: 20px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                <span class="sidebar-text">Dashboard Overview</span>
            </a>
            
            <!-- Manage Catalog Link -->
            <a href="dashboard.php?tab=catalog" title="Manage Catalog" onclick="preserveSidebarState()" style="display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: 8px; text-decoration: none; margin-bottom: 6px; color: <?= $tab === 'catalog' ? '#fff' : '#cbd5e1' ?>; background: <?= $tab === 'catalog' ? '#134074' : 'transparent' ?>;">
                <svg style="width: 20px; height: 20px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2h0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                <span class="sidebar-text">Manage Catalog</span>
            </a>

            <!-- Add New Equipment Link -->
            <a href="dashboard.php?tab=add_equipment" title="Add New Equipment" onclick="preserveSidebarState()" style="display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: 8px; text-decoration: none; margin-bottom: 6px; color: <?= $tab === 'add_equipment' ? '#fff' : '#cbd5e1' ?>; background: <?= $tab === 'add_equipment' ? '#134074' : 'transparent' ?>;">
                <svg style="width: 20px; height: 20px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span class="sidebar-text">Add New Equipment</span>
            </a>

            <!-- History Log Link -->
            <a href="dashboard.php?tab=history" title="History Log" onclick="preserveSidebarState()" style="display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: 8px; text-decoration: none; margin-bottom: 6px; color: <?= $tab === 'history' ? '#fff' : '#cbd5e1' ?>; background: <?= $tab === 'history' ? '#134074' : 'transparent' ?>;">
                <svg style="width: 20px; height: 20px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span class="sidebar-text">History Log</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content Area -->
    <div class="main-content">
        <!-- Top Header -->
        <header style="background: white; border-bottom: 1px solid #e2e8f0; padding: 12px 24px; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 16px;">
                <h2 style="margin: 0; font-size: 1.25rem; font-weight: bold; color: #1e293b;">Module: <?= ucfirst(str_replace('_', ' ', $tab)) ?></h2>
            </div>
            
            <div style="display: flex; align-items: center; gap: 20px;">
                <!-- Notification Bell Widget -->
                <div style="position: relative;">
                    <button onclick="toggleNotificationMenu(event)" style="width: 40px; height: 40px; border-radius: 50%; background: #f8fafc; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; cursor: pointer; position: relative;" title="Pending Requests">
                        <svg style="width: 20px; height: 20px; color: #475569;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                        <?php if($pending_count > 0): ?>
                            <span style="position: absolute; top: 6px; right: 6px; width: 10px; height: 10px; background: #ef4444; border: 2px solid white; border-radius: 50%;"></span>
                        <?php endif; ?>
                    </button>

                    <!-- Notification Dropdown Drawer -->
                    <div id="notificationDropdown" style="position: absolute; right: 0; top: 120%; width: 340px; background: white; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); padding: 16px; display: none; z-index: 40;">
                        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px; margin-bottom: 12px;">
                            <h4 style="font-size: 14px; font-weight: bold; color: #1e293b; margin: 0;">Borrow Notifications</h4>
                            <span style="background: #fee2e2; color: #991b1b; font-size: 11px; font-weight: bold; padding: 2px 8px; border-radius: 10px;"><?= $pending_count ?> New</span>
                        </div>

                        <?php if(empty($pending_requests)): ?>
                            <p style="font-size: 13px; color: #64748b; text-align: center; margin: 20px 0;">No pending borrow requests.</p>
                        <?php else: ?>
                            <div style="max-height: 280px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px;">
                                <?php foreach($pending_requests as $req): ?>
                                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 10px; border-radius: 8px;">
                                        <p style="font-size: 12px; font-weight: bold; color: #1e293b; margin: 0 0 4px 0;"><?= htmlspecialchars($req['borrower_name']) ?> wants <b><?= htmlspecialchars($req['equipment_name']) ?></b></p>
                                        <p style="font-size: 11px; color: #64748b; margin: 0 0 8px 0;">Room: <?= htmlspecialchars($req['classroom']) ?> | Purpose: <?= htmlspecialchars($req['purpose']) ?></p>
                                        <form method="POST" style="display: flex; gap: 6px; margin: 0;">
                                            <input type="hidden" name="action" value="admin_action">
                                            <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                            <input type="hidden" name="equipment_id" value="<?= $req['equipment_id'] ?>">
                                            <button type="submit" name="req_status" value="Approved" style="flex: 1; background: #059669; color: white; padding: 4px; border-radius: 4px; font-size: 11px; border: none; cursor: pointer; font-weight: 500;">Approve</button>
                                            <button type="submit" name="req_status" value="Rejected" style="flex: 1; background: #e11d48; color: white; padding: 4px; border-radius: 4px; font-size: 11px; border: none; cursor: pointer; font-weight: 500;">Reject</button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Profile Widget -->
                <div style="position: relative;">
                    <button onclick="toggleProfileMenu(event)" style="display: flex; align-items: center; gap: 12px; background: none; border: none; cursor: pointer; padding: 4px;">
                        <div style="position: relative;">
                            <div style="width: 38px; height: 38px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; color: #475569; font-weight: bold; border: 1px solid #cbd5e1; overflow: hidden;">
                                <svg style="width: 22px; height: 22px; color: #94a3b8; margin-top: 4px;" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                            </div>
                            <span style="position: absolute; bottom: 0; right: 0; width: 10px; height: 10px; background: #10b981; border: 2px solid white; border-radius: 50%;"></span>
                        </div>
                        <div style="text-align: left;">
                            <p style="font-size: 13px; font-weight: bold; color: #1e293b; margin: 0; line-height: 1.2;"><?= htmlspecialchars($_SESSION['name']) ?></p>
                            <p style="font-size: 11px; color: #94a3b8; margin: 0; font-weight: 500;">Admin</p>
                        </div>
                    </button>

                    <!-- Profile Dropdown Menu -->
                    <div id="profileDropdown" style="position: absolute; right: 0; top: 115%; width: 180px; background: white; border: 1px solid #e2e8f0; border-radius: 10px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); padding: 8px 0; display: none; z-index: 40;">
                        <div style="padding: 8px 16px; border-bottom: 1px solid #f1f5f9; margin-bottom: 4px;">
                            <p style="font-size: 13px; font-weight: bold; color: #1e293b; margin: 0;"><?= htmlspecialchars($_SESSION['name']) ?></p>
                            <p style="font-size: 11px; color: #64748b; margin: 0;">Administrator</p>
                        </div>
                        <a href="../auth/admin_logout.php" style="display: flex; align-items: center; gap: 8px; padding: 8px 16px; font-size: 13px; color: #e11d48; text-decoration: none; font-weight: 500;">
                            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <main style="padding: 24px; max-width: 1200px; margin: 0 auto; width: 100%; box-sizing: border-box;">
            <?php
            switch ($tab) {
                case 'catalog':
                    include 'manage_catalog.php';
                    break;
                case 'add_equipment':
                    include 'add_equipment.php';
                    break;
                case 'history':
                    include 'history_log.php';
                    break;
                case 'overview':
                default:
                    include 'overview.php';
                    break;
            }
            ?>
        </main>
    </div>

    <!-- JavaScript for Persistent Sidebar Toggle State -->
    <script>
        // Apply saved sidebar state on page load immediately
        document.addEventListener("DOMContentLoaded", function() {
            if (localStorage.getItem('sidebarCollapsed') === 'true') {
                document.getElementById('appSidebar').classList.add('collapsed');
            }
        });

        function toggleSidebar() {
            const sidebar = document.getElementById('appSidebar');
            sidebar.classList.toggle('collapsed');
            // Save state to localStorage
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        }

        function preserveSidebarState() {
            // State is already preserved via localStorage, this ensures smooth transition when switching tabs
        }

        function toggleNotificationMenu(event) {
            event.stopPropagation();
            const notifDropdown = document.getElementById('notificationDropdown');
            const profileDropdown = document.getElementById('profileDropdown');
            profileDropdown.style.display = 'none';
            notifDropdown.style.display = notifDropdown.style.display === 'block' ? 'none' : 'block';
        }

        function toggleProfileMenu(event) {
            event.stopPropagation();
            const profileDropdown = document.getElementById('profileDropdown');
            const notifDropdown = document.getElementById('notificationDropdown');
            notifDropdown.style.display = 'none';
            profileDropdown.style.display = profileDropdown.style.display === 'block' ? 'none' : 'block';
        }

        window.addEventListener('click', function() {
            document.getElementById('notificationDropdown').style.display = 'none';
            document.getElementById('profileDropdown').style.display = 'none';
        });
    </script>
</body>
</html>