<?php
session_set_cookie_params(0, '/auth/');
session_start();
require_once __DIR__ . '/../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        // Check by student_id or email, ensuring role is admin
        $stmt = $pdo->prepare("SELECT * FROM users WHERE (student_id = ? OR email = ?) AND role = 'admin'");
        $stmt->execute([$username, $username]);
        $admin = $stmt->fetch();

        if ($admin && (password_verify($password, $admin['password']) || $password === $admin['password'])) {
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['name'] = $admin['name'];
            $_SESSION['role'] = 'admin';
            header("Location: ../admin/dashboard.php");
            exit;
        } else {
            $error = 'Invalid Admin Username or Password.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Portal - ACLC College</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(rgba(11, 37, 69, 0.85), rgba(11, 37, 69, 0.85)), url('../assets/computer_lab_bg.jpg') center/cover no-repeat;
        }
    </style>
</head>
<body>

    <div style="width: 850px; max-width: 95%; background: white; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.3); display: flex; overflow: hidden; min-height: 480px;">
        
        <!-- Left Side: Branding Panel -->
        <div style="flex: 1; background: linear-gradient(135deg, #0b2545 0%, #134074 100%); color: white; padding: 40px; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center;">
            <img src="../assets/aclc_logo.png" alt="ACLC Logo" style="width: 90px; height: 90px; object-fit: contain; margin-bottom: 20px; background: transparent;">
            <h1 style="font-size: 26px; font-weight: bold; letter-spacing: 1px; margin: 0 0 5px 0;">ACLC COLLEGE</h1>
            <p style="font-size: 11px; letter-spacing: 2px; text-transform: uppercase; color: #94a3b8; margin: 0 0 25px 0; font-weight: 600;">Mandaue City</p>
            <div style="width: 120px; height: 2px; background: rgba(255,255,255,0.2); margin-bottom: 25px;"></div>
            <p style="font-size: 13px; color: #cbd5e1; margin: 0; line-height: 1.5;">Technical Equipment Inventory System</p>
        </div>

        <!-- Right Side: Admin Form Panel -->
        <div style="flex: 1; padding: 50px 40px; display: flex; flex-direction: column; justify-content: center; background: white;">
            <h2 style="font-size: 28px; font-weight: bold; color: #1e293b; margin: 0 0 8px 0;">Admin Portal</h2>
            <p style="font-size: 13px; color: #64748b; margin: 0 0 25px 0;">Custodian & Administrator Access Only</p>

            <?php if (!empty($error)): ?>
                <div style="background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 20px;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; margin-bottom: 6px;">Admin Username / Email</label>
                    <input type="text" name="username" required placeholder="Enter username or email" style="width: 100%; padding: 12px 0; border: none; border-bottom: 2px solid #cbd5e1; outline: none; font-size: 14px; color: #1e293b; background: transparent; transition: border-color 0.2s;" onfocus="this.style.borderBottomColor='#134074'" onblur="this.style.borderBottomColor='#cbd5e1'">
                </div>

                <div style="margin-bottom: 30px;">
                    <label style="display: block; font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; margin-bottom: 6px;">Password</label>
                    <input type="password" name="password" required placeholder="Enter admin password" style="width: 100%; padding: 12px 0; border: none; border-bottom: 2px solid #cbd5e1; outline: none; font-size: 14px; color: #1e293b; background: transparent; transition: border-color 0.2s;" onfocus="this.style.borderBottomColor='#134074'" onblur="this.style.borderBottomColor='#cbd5e1'">
                </div>

                <button type="submit" style="width: 100%; background: #134074; color: white; border: none; padding: 14px; border-radius: 8px; font-size: 15px; font-weight: bold; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='#0b2545'" onmouseout="this.style.background='#134074'">
                    Admin Login
                </button>
            </form>
        </div>

    </div>

</body>
</html>