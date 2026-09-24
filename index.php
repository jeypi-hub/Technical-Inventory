<?php
// index.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ACLC Student Portal Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl p-8 border border-slate-100">
        
        <!-- Header -->
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-indigo-950">Student Portal</h1>
            <p class="text-sm text-slate-500 mt-1">Sign in to request and borrow equipment</p>
        </div>

        <!-- Student Login Form -->
        <form action="auth/login.php" method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Student ID / Email</label>
                <input type="text" name="username" required placeholder="Enter your student ID" 
                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-800 outline-none focus:border-indigo-600 focus:bg-white transition-all">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Password</label>
                <input type="password" name="password" required placeholder="••••••••" 
                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-800 outline-none focus:border-indigo-600 focus:bg-white transition-all">
            </div>

            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-lg shadow-md transition-all duration-200 cursor-pointer text-sm mt-2">
                Sign In
            </button>
        </form>

        <!-- Subtle Helper Note -->
        <div class="mt-6 text-center border-t border-slate-100 pt-4">
            <p class="text-[11px] text-slate-400">
                Authorized ACLC Personnel? Press <kbd class="px-1.5 py-0.5 bg-slate-100 border border-slate-200 rounded text-slate-600 font-mono">Ctrl</kbd> + <kbd class="px-1.5 py-0.5 bg-slate-100 border border-slate-200 rounded text-slate-600 font-mono">A</kbd> to open Admin login.
            </p>
        </div>

    </div>

    <!-- Script to handle Ctrl + A shortcut -->
    <script>
        document.addEventListener('keydown', function(event) {
            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'a') {
                event.preventDefault(); // Prevent standard browser "Select All"
                window.location.href = 'auth/admin_login.php'; // Redirect to Admin Login page
            }
        });
    </script>
</body>
</html>