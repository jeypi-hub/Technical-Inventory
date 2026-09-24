<?php
session_set_cookie_params(0, '/');
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Equipment.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'borrower') {
    header("Location: ../auth/login.php");
    exit;
}

$eqObj = new Equipment($pdo);
$equipment_list = $eqObj->getAllEquipment();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_item'])) {
    $equipment_id = $_POST['equipment_id'];
    $purpose = $_POST['purpose'];
    $classroom = $_POST['classroom'];
    $teacher_name = $_POST['teacher_name'];

    $stmt = $pdo->prepare("INSERT INTO borrow_requests (user_id, equipment_id, purpose, classroom, teacher_name, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
    $stmt->execute([$_SESSION['user_id'], $equipment_id, $purpose, $classroom, $teacher_name]);
    $message = "Borrow request submitted successfully!";
}

$stmt = $pdo->prepare("SELECT br.*, e.name as equipment_name, e.serial_number FROM borrow_requests br JOIN equipment e ON br.equipment_id = e.id WHERE br.user_id = ? ORDER BY br.id DESC");
$stmt->execute([$_SESSION['user_id']]);
$my_requests = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Borrower Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen">
    <nav class="bg-indigo-900 text-white px-6 py-3 flex justify-between items-center shadow-md">
        <!-- Logo and Title -->
        <div class="flex items-center space-x-3">
            <img src="../assets/aclc_logo.png" alt="ACLC Logo" class="w-9 h-9 object-contain">
            <div>
                <span class="font-bold text-sm tracking-wide block leading-tight">ACLC COLLEGE</span>
                <span class="text-[10px] text-indigo-200 tracking-wider uppercase font-semibold">Mandaue City</span>
            </div>
        </div>
        
        <!-- Profile Dropdown Container -->
        <div class="relative inline-block text-left" id="profileDropdownContainer">
            <!-- Trigger Button -->
            <button onclick="toggleProfileDropdown()" class="flex items-center space-x-3 focus:outline-none bg-indigo-800/60 px-3 py-1.5 rounded-full hover:bg-indigo-800 transition border border-indigo-700/50">
                <!-- Avatar Circle with Online Status Dot -->
                <div class="relative">
                    <div class="w-9 h-9 rounded-full bg-slate-200 flex items-center justify-center text-slate-600 font-bold overflow-hidden">
                        <svg class="w-5 h-5 text-slate-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                        </svg>
                    </div>
                    <!-- Green Online Dot -->
                    <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-emerald-500 border-2 border-indigo-900 rounded-full"></span>
                </div>
                
                <!-- Name, Course, and Role text -->
                <div class="text-left pr-1 hidden sm:block">
                    <div class="text-sm font-bold text-white leading-tight">
                        <?= htmlspecialchars($_SESSION['name'] ?? 'Student User') ?>
                    </div>
                    <div class="text-xs text-indigo-200 font-medium truncate max-w-[180px]">
                        <?= htmlspecialchars($_SESSION['course'] ?? 'Course not specified') ?>
                    </div>
                </div>
            </button>

            <!-- Dropdown Card Menu -->
            <div id="profileDropdownMenu" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-2xl shadow-xl border border-slate-100 py-3 z-50 transform opacity-0 scale-95 transition-all duration-150 ease-out text-slate-800 overflow-hidden">
                
                <!-- CARD 1: Main Profile Info & Menu Options -->
                <div id="dropdownCardMain" class="transition-all duration-200">
                    <!-- User Info Header -->
                    <div class="px-5 py-2 border-b border-slate-100">
                        <div class="text-base font-bold text-slate-900 truncate">
                            <?= htmlspecialchars($_SESSION['name'] ?? 'Student User') ?>
                        </div>
                        <div class="text-xs text-indigo-700 font-semibold mt-0.5">
                            <?= htmlspecialchars($_SESSION['course'] ?? 'Course not specified') ?>
                        </div>
                        <div class="text-[11px] text-slate-400 font-medium">Student Borrower</div>
                    </div>

                    <!-- Interactive Link to Switch Card -->
                    <div class="py-1">
                        <button onclick="switchDropdownCard('history')" class="w-full flex items-center justify-between px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-3 text-indigo-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                My Borrow Request Status History
                            </div>
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Logout Option -->
                    <div class="pt-1 border-t border-slate-100">
                        <a href="../auth/logout.php" class="flex items-center px-5 py-2.5 text-sm font-semibold text-rose-600 hover:bg-rose-50 transition">
                            <svg class="w-5 h-5 mr-3 text-rose-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            Logout
                        </a>
                    </div>
                </div>

                <!-- CARD 2: Request Status History View -->
                <div id="dropdownCardHistory" class="hidden transition-all duration-200 px-4">
                    <!-- Header with Back Button -->
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-2">
                        <button onclick="switchDropdownCard('main')" class="flex items-center text-xs font-bold text-indigo-600 hover:text-indigo-800 transition">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Back to Menu
                        </button>
                        <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Request Status</span>
                    </div>

                    <!-- History Items List Card -->
                    <div class="max-h-64 overflow-y-auto space-y-2 pr-1">
                        <?php if(empty($my_requests)): ?>
                            <p class="text-xs text-slate-400 text-center py-4">No requests made yet.</p>
                        <?php else: ?>
                            <?php foreach($my_requests as $req): ?>
                                <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-100 flex justify-between items-center text-xs">
                                    <div class="truncate pr-2">
                                        <span class="font-bold text-slate-800 block truncate"><?= htmlspecialchars($req['equipment_name']) ?></span>
                                        <span class="text-[10px] text-slate-500 block truncate">Purpose: <?= htmlspecialchars($req['purpose']) ?></span>
                                    </div>
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded shrink-0 
                                        <?php 
                                            if($req['status'] == 'Pending') echo 'bg-yellow-100 text-yellow-800';
                                            elseif($req['status'] == 'Approved') echo 'bg-emerald-100 text-emerald-800';
                                            elseif($req['status'] == 'Rejected') echo 'bg-rose-100 text-rose-800';
                                            else echo 'bg-blue-100 text-blue-800';
                                        ?>">
                                        <?= $req['status'] ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </nav>

    <div class="container mx-auto p-6 max-w-5xl">
        <?php if($message): ?>
            <div class="bg-emerald-100 text-emerald-700 p-3 rounded mb-4"><?= $message ?></div>
        <?php endif; ?>

        <!-- Equipment Catalog -->
        <h2 class="text-xl font-bold mb-4 text-slate-800">Available Technical Equipment</h2>
        <div class="grid md:grid-cols-3 gap-4 mb-8">
            <?php foreach($equipment_list as $eq): ?>
                <div class="bg-white rounded shadow border overflow-hidden flex flex-col justify-between">
                    <?php if(!empty($eq['image'])): ?>
                        <img src="../assets/uploads/<?= htmlspecialchars($eq['image']) ?>" alt="Equipment Image" class="w-full h-36 object-cover">
                    <?php else: ?>
                        <div class="w-full h-36 bg-slate-200 flex items-center justify-center text-slate-400 text-sm">No Image Available</div>
                    <?php endif; ?>
                    <div class="p-4">
                        <h3 class="font-bold text-lg"><?= htmlspecialchars($eq['name']) ?></h3>
                        <p class="text-xs text-slate-500 mb-2">Serial: <?= htmlspecialchars($eq['serial_number']) ?></p>
                        <span class="px-2 py-1 text-xs rounded font-semibold 
                            <?= $eq['status'] == 'Available' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' ?>">
                            <?= $eq['status'] ?>
                        </span>
                    </div>
                    <?php if($eq['status'] === 'Available'): ?>
                        <div class="p-4 pt-0">
                            <form method="POST" class="space-y-2">
                                <input type="hidden" name="equipment_id" value="<?= $eq['id'] ?>">
                                <input type="text" name="purpose" placeholder="Purpose (e.g. Lab work)" required class="w-full text-sm border p-1.5 rounded">
                                <input type="text" name="classroom" placeholder="Classroom (e.g. Lab 302)" required class="w-full text-sm border p-1.5 rounded">
                                <input type="text" name="teacher_name" placeholder="Teacher Name" required class="w-full text-sm border p-1.5 rounded">
                                <button type="submit" name="request_item" class="w-full bg-indigo-600 text-white text-sm py-1.5 rounded hover:bg-indigo-700">Request Item</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

    <!-- Profile Dropdown Toggle & Switcher Script -->
    <script>
    function toggleProfileDropdown() {
        const menu = document.getElementById('profileDropdownMenu');
        if (menu.classList.contains('hidden')) {
            menu.classList.remove('hidden');
            setTimeout(() => {
                menu.classList.remove('opacity-0', 'scale-95');
                menu.classList.add('opacity-100', 'scale-100');
            }, 10);
        } else {
            closeDropdown();
        }
    }

    function closeDropdown() {
        const menu = document.getElementById('profileDropdownMenu');
        menu.classList.remove('opacity-100', 'scale-100');
        menu.classList.add('opacity-0', 'scale-95');
        setTimeout(() => {
            menu.classList.add('hidden');
            switchDropdownCard('main');
        }, 150);
    }

    function switchDropdownCard(target) {
        const mainCard = document.getElementById('dropdownCardMain');
        const historyCard = document.getElementById('dropdownCardHistory');

        if (target === 'history') {
            mainCard.classList.add('hidden');
            historyCard.classList.remove('hidden');
        } else {
            historyCard.classList.add('hidden');
            mainCard.classList.remove('hidden');
        }
    }

    window.addEventListener('click', function(e) {
        const container = document.getElementById('profileDropdownContainer');
        if (!container.contains(e.target)) {
            closeDropdown();
        }
    });
    </script>
</body>
</html>