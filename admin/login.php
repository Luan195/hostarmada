<?php
// Proper session initialization
require_once '../includes/session.php';
require_once '../includes/functions.php';

// Redirect if already logged in
if (isAdmin()) {
    redirect('index.php');
}

$error = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $result = loginAdmin($username, $password);
    
    if ($result['success']) {
        redirect('index.php');
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - HSHOP Analytics</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 min-h-screen flex items-center justify-center">

    <div class="w-full max-w-md px-4">
        
        <!-- Login Card -->
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            
            <!-- Logo/Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-red-500 to-purple-600 rounded-2xl mb-4 shadow-lg">
                    <i class="fa-solid fa-user-shield text-4xl text-white"></i>
                </div>
                <h1 class="text-3xl font-black text-slate-900 mb-2">Admin Panel</h1>
                <p class="text-slate-500 text-sm">HSHOP Analytics Management</p>
            </div>

            <!-- Error Message -->
            <?php if (!empty($error)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg">
                <div class="flex items-center">
                    <i class="fa-solid fa-exclamation-circle mr-2"></i>
                    <p class="text-sm font-bold"><?php echo $error; ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" class="space-y-5">
                
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">
                        <i class="fa-solid fa-user mr-2 text-slate-400"></i>
                        Tài khoản Admin
                    </label>
                    <input type="text" 
                           name="username" 
                           required 
                           autofocus
                           placeholder="Nhập tài khoản admin"
                           class="w-full px-4 py-3 border-2 border-slate-200 rounded-xl focus:border-blue-500 focus:outline-none transition">
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">
                        <i class="fa-solid fa-lock mr-2 text-slate-400"></i>
                        Mật khẩu
                    </label>
                    <div class="relative">
                        <input type="password" 
                               name="password" 
                               id="password"
                               required 
                               placeholder="Nhập mật khẩu"
                               class="w-full px-4 py-3 border-2 border-slate-200 rounded-xl focus:border-blue-500 focus:outline-none transition pr-12">
                        <button type="button" 
                                onclick="togglePassword()"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                            <i class="fa-solid fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" 
                        name="login"
                        class="w-full bg-gradient-to-r from-red-600 to-purple-600 hover:from-red-700 hover:to-purple-700 text-white font-black py-4 rounded-xl shadow-lg hover:shadow-xl transition transform hover:scale-105">
                    <i class="fa-solid fa-sign-in mr-2"></i>
                    Đăng Nhập
                </button>

            </form>

            <!-- Footer Info -->
            <div class="mt-8 pt-6 border-t border-slate-200 text-center">
                <p class="text-xs text-slate-500">
                    <i class="fa-solid fa-shield-halved mr-1"></i>
                    Khu vực quản trị - Chỉ dành cho Admin
                </p>
                <p class="text-xs text-slate-400 mt-2">
                    © <?php echo date('Y'); ?> HSHOP Media Việt Nam
                </p>
            </div>

        </div>

        <!-- Back to Site Link -->
        <div class="text-center mt-6">
            <a href="../index.php" class="text-white hover:text-slate-300 text-sm font-bold inline-flex items-center gap-2">
                <i class="fa-solid fa-arrow-left"></i>
                Về trang chủ
            </a>
        </div>

    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Auto-focus on username field
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('input[name="username"]').focus();
        });
    </script>

</body>
</html>
