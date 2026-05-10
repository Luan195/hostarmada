<?php
// Proper session initialization
require_once 'includes/session.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('scanner.php');
}

$error = '';
$success = '';
$mode = $_GET['mode'] ?? 'login'; // login or register

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember_me']) && $_POST['remember_me'] === '1';
    
    // Validate inputs
    if (empty($username) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu';
    } else {
        $result = loginUser($username, $password, $rememberMe);
        
        if ($result['success']) {
            redirect('scanner.php');
        } else {
            $error = $result['message'];
        }
    }
}

// Handle Register
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $email = sanitize($_POST['email'] ?? '');
    $refCode = sanitize($_POST['ref_code'] ?? '');
    
    // Validate inputs
    if (empty($username) || empty($password) || empty($email)) {
        $error = 'Vui lòng nhập đầy đủ thông tin đăng ký';
    } else {
        $result = registerUser($username, $password, $email, $refCode);
        
        if ($result['success']) {
            $success = $result['message'] . ' - Vui lòng đăng nhập!';
            $mode = 'login';
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $mode === 'login' ? 'Đăng Nhập' : 'Đăng Ký'; ?> - HSHOP Analytics</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        /* Smooth animations */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Focus styles for accessibility */
        input:focus, button:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
        }
        
        /* Better mobile experience */
        @media (max-width: 640px) {
            body {
                padding: 1rem;
            }
            
            .glass-effect {
                padding: 1.5rem !important;
            }
            
            h1 {
                font-size: 1.5rem !important;
            }
        }
        
        /* Loading state */
        button[type="submit"]:active {
            transform: scale(0.98);
        }
        
        /* Error message animation */
        .alert-message {
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from { 
                opacity: 0; 
                transform: translateY(-10px); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0); 
            }
        }
    </style>
</head>
<body class="gradient-bg">
    
    <div class="max-w-md w-full glass-effect rounded-2xl shadow-2xl p-8 fade-in">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="inline-block bg-gradient-to-br from-red-600 to-red-700 text-white p-4 rounded-2xl shadow-lg mb-4">
                <i class="fa-brands fa-youtube text-5xl"></i>
            </div>
            <h1 class="text-3xl font-black text-slate-800">HSHOP <span class="text-red-600">Analytics</span></h1>
            <p class="text-sm text-slate-600 mt-2">YouTube Niche Scanner Pro</p>
        </div>

        <!-- Tab Switcher -->
        <div class="flex gap-2 mb-6 bg-slate-100 p-1 rounded-lg">
            <a href="?mode=login" class="flex-1 text-center py-2 rounded-md font-bold transition <?php echo $mode === 'login' ? 'bg-white text-red-600 shadow-sm' : 'text-slate-600 hover:text-slate-800'; ?>">
                <i class="fa-solid fa-sign-in-alt mr-1"></i> Đăng Nhập
            </a>
            <a href="?mode=register" class="flex-1 text-center py-2 rounded-md font-bold transition <?php echo $mode === 'register' ? 'bg-white text-red-600 shadow-sm' : 'text-slate-600 hover:text-slate-800'; ?>">
                <i class="fa-solid fa-user-plus mr-1"></i> Đăng Ký
            </a>
        </div>

        <!-- Error/Success Messages -->
        <?php if ($error): ?>
        <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-3 rounded alert-message">
            <p class="text-red-700 text-sm font-semibold"><i class="fa-solid fa-exclamation-circle mr-1"></i> <?php echo htmlspecialchars($error); ?></p>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="mb-4 bg-green-50 border-l-4 border-green-500 p-3 rounded alert-message">
            <p class="text-green-700 text-sm font-semibold"><i class="fa-solid fa-check-circle mr-1"></i> <?php echo htmlspecialchars($success); ?></p>
        </div>
        <?php endif; ?>

        <!-- Login Form -->
        <?php if ($mode === 'login'): ?>
        <form method="POST" class="space-y-4" id="loginForm">
            <input type="hidden" name="action" value="login">
            
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2" for="login-username">
                    Tên đăng nhập
                </label>
                <div class="relative">
                    <span class="absolute left-3 top-3 text-slate-400"><i class="fa-solid fa-user"></i></span>
                    <input type="text" name="username" id="login-username" required 
                           autocomplete="username"
                           class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition"
                           placeholder="Nhập tên đăng nhập">
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2" for="login-password">
                    Mật khẩu
                </label>
                <div class="relative">
                    <span class="absolute left-3 top-3 text-slate-400"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="password" id="login-password" required 
                           autocomplete="current-password"
                           class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition"
                           placeholder="Nhập mật khẩu">
                </div>
            </div>

            <!-- Remember Me Checkbox -->
            <div class="flex items-center justify-between">
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" name="remember_me" value="1" 
                           class="w-4 h-4 text-red-600 border-slate-300 rounded focus:ring-red-500">
                    <span class="ml-2 text-sm text-slate-700">
                        <i class="fa-solid fa-clock-rotate-left mr-1 text-slate-500"></i>
                        Ghi nhớ đăng nhập (30 ngày)
                    </span>
                </label>
                <a href="#" class="text-sm text-red-600 hover:text-red-700 font-semibold">
                    Quên mật khẩu?
                </a>
            </div>

            <button type="submit" class="w-full bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold py-3 rounded-lg shadow-md transition transform hover:shadow-lg active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="fa-solid fa-sign-in-alt mr-2"></i> Đăng Nhập
            </button>
        </form>
        <?php endif; ?>

        <!-- Register Form -->
        <?php if ($mode === 'register'): ?>
        <form method="POST" class="space-y-4" id="registerForm">
            <input type="hidden" name="action" value="register">
            
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2" for="reg-username">
                    Tên đăng nhập
                </label>
                <div class="relative">
                    <span class="absolute left-3 top-3 text-slate-400"><i class="fa-solid fa-user"></i></span>
                    <input type="text" name="username" id="reg-username" required minlength="3" maxlength="20" pattern="[a-zA-Z0-9]+"
                           autocomplete="username"
                           class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition"
                           placeholder="3-20 ký tự (chữ + số)"
                           title="Tên đăng nhập chỉ được chứa chữ cái và số, không chứa ký tự đặc biệt">
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2" for="reg-email">
                    Email
                </label>
                <div class="relative">
                    <span class="absolute left-3 top-3 text-slate-400"><i class="fa-solid fa-envelope"></i></span>
                    <input type="email" name="email" id="reg-email" required
                           autocomplete="email"
                           class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition"
                           placeholder="email@gmail.com"
                           pattern="[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*"
                           title="Nhập địa chỉ email hợp lệ (VD: email@gmail.com)">
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2" for="reg-password">
                    Mật khẩu
                </label>
                <div class="relative">
                    <span class="absolute left-3 top-3 text-slate-400"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="password" id="reg-password" required minlength="6"
                           autocomplete="new-password"
                           class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition"
                           placeholder="Tối thiểu 6 ký tự">
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2" for="reg-refcode">
                    Mã giới thiệu <span class="text-slate-400 font-normal">(Tùy chọn)</span>
                </label>
                <div class="relative">
                    <span class="absolute left-3 top-3 text-slate-400"><i class="fa-solid fa-gift"></i></span>
                    <input type="text" name="ref_code" id="reg-refcode"
                           class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition"
                           placeholder="Nhập nếu có">
                </div>
            </div>

            <button type="submit" class="w-full bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold py-3 rounded-lg shadow-md transition transform hover:shadow-lg active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="fa-solid fa-user-plus mr-2"></i> Đăng Ký Miễn Phí
            </button>

            <p class="text-xs text-slate-500 text-center mt-2">

            </p>
        </form>
        <script>
        // Client-side username validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const usernameInput = document.getElementById('reg-username');
            const username = usernameInput.value.trim();
            
            // Check if username contains only letters and numbers
            const usernamePattern = /^[a-zA-Z0-9]+$/;
            if (!usernamePattern.test(username)) {
                e.preventDefault();
                alert('Tên đăng nhập chỉ được chứa chữ cái và số, không chứa ký tự đặc biệt!');
                usernameInput.focus();
                return false;
            }
        });
        </script>
        <?php endif; ?>

        <!-- Additional Links -->
        <div class="mt-6 pt-6 border-t border-slate-200">
            <p class="text-center text-sm text-slate-600">
                <i class="fa-solid fa-phone text-red-600 mr-1"></i> Hỗ trợ: <strong><?php echo SUPPORT_HOTLINE; ?></strong>
            </p>
        </div>
    </div>

</body>
</html>
