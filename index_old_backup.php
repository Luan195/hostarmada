<?php
// Proper session initialization
require_once 'includes/session.php';
require_once 'includes/functions.php';

// Redirect to scanner if logged in
if (isLoggedIn()) {
    redirect('scanner.php');
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HSHOP Analytics - YouTube Niche Scanner Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .gradient-text { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    </style>
</head>
<body class="bg-slate-50">

    <!-- Hero Section -->
    <header class="bg-gradient-to-r from-slate-900 via-purple-900 to-slate-900 text-white py-20">
        <div class="max-w-6xl mx-auto px-4 text-center">
            <div class="inline-block bg-red-600 text-white p-4 rounded-2xl mb-6">
                <i class="fa-brands fa-youtube text-6xl"></i>
            </div>
            <h1 class="text-5xl md:text-7xl font-black mb-6">
                HSHOP <span class="text-red-500">Analytics</span>
            </h1>
            <p class="text-xl md:text-2xl text-slate-300 mb-8 max-w-3xl mx-auto">
                Công cụ phân tích YouTube chuyên nghiệp<br>Tìm ngách tiềm năng • Phân tích đối thủ • Dự đoán doanh thu
            </p>
            <div class="flex gap-4 justify-center flex-wrap">
                <a href="login.php?mode=register" class="bg-red-600 hover:bg-red-700 text-white font-bold px-8 py-4 rounded-xl shadow-lg transition transform hover:scale-105">
                    <i class="fa-solid fa-rocket mr-2"></i> Dùng Thử Miễn Phí
                </a>
                <a href="login.php" class="bg-white hover:bg-slate-100 text-slate-900 font-bold px-8 py-4 rounded-xl shadow-lg transition">
                    <i class="fa-solid fa-sign-in-alt mr-2"></i> Đăng Nhập
                </a>
            </div>
        </div>
    </header>

    <!-- Features Section -->
    <section class="py-20 bg-white">
        <div class="max-w-6xl mx-auto px-4">
            <h2 class="text-4xl font-black text-center mb-4">Tính Năng Nổi Bật</h2>
            <p class="text-center text-slate-600 mb-12">Hệ thống phân tích toàn diện dành cho Creator chuyên nghiệp</p>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-slate-50 p-6 rounded-xl border border-slate-200 hover:shadow-lg transition">
                    <div class="bg-red-100 w-14 h-14 rounded-full flex items-center justify-center mb-4">
                        <i class="fa-solid fa-radar text-red-600 text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-xl mb-2">Scanner Ngách</h3>
                    <p class="text-slate-600">Quét và phân tích hàng trăm video, tìm ngách tiềm năng với tỷ lệ View/Sub cao.</p>
                </div>

                <div class="bg-slate-50 p-6 rounded-xl border border-slate-200 hover:shadow-lg transition">
                    <div class="bg-blue-100 w-14 h-14 rounded-full flex items-center justify-center mb-4">
                        <i class="fa-solid fa-chart-line text-blue-600 text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-xl mb-2">Dự Báo Doanh Thu</h3>
                    <p class="text-slate-600">Tính toán RPM theo ngách, dự đoán thu nhập tiềm năng từ mỗi video.</p>
                </div>

                <div class="bg-slate-50 p-6 rounded-xl border border-slate-200 hover:shadow-lg transition">
                    <div class="bg-purple-100 w-14 h-14 rounded-full flex items-center justify-center mb-4">
                        <i class="fa-solid fa-crown text-purple-600 text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-xl mb-2">Phân Tích Đối Thủ</h3>
                    <p class="text-slate-600">Theo dõi kênh đối thủ, phân tích chiến lược, tag và thời gian đăng tối ưu.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Teaser -->
    <section class="py-20 bg-gradient-to-br from-slate-900 to-purple-900 text-white">
        <div class="max-w-6xl mx-auto px-4 text-center">
            <h2 class="text-4xl font-black mb-4">Bắt Đầu Miễn Phí</h2>
            <p class="text-xl text-slate-300 mb-8">Nâng cấp khi bạn cần thêm sức mạnh</p>
            <div class="flex gap-6 justify-center flex-wrap">
                <div class="bg-white/10 backdrop-blur-md p-8 rounded-2xl border border-white/20 max-w-xs">
                    <h3 class="font-bold text-2xl mb-2">FREE</h3>
                    <p class="text-4xl font-black mb-4">$0<span class="text-lg font-normal">/tháng</span></p>
                    <ul class="text-left space-y-2 text-sm">
                        <li><i class="fa-solid fa-check text-green-400 mr-2"></i> 5 lượt tìm kiếm/ngày</li>
                        <li><i class="fa-solid fa-check text-green-400 mr-2"></i> Xem 2 kết quả đầu</li>
                        <li><i class="fa-solid fa-check text-green-400 mr-2"></i> Hỗ trợ cộng đồng</li>
                    </ul>
                </div>

                <div class="bg-gradient-to-br from-yellow-400 to-orange-500 p-8 rounded-2xl shadow-2xl max-w-xs transform scale-105">
                    <div class="bg-white/20 px-3 py-1 rounded-full inline-block mb-2 text-xs font-bold">PHỔ BIẾN</div>
                    <h3 class="font-bold text-2xl mb-2">VIP</h3>
                    <p class="text-4xl font-black mb-4">$29.99<span class="text-lg font-normal">/tháng</span></p>
                    <ul class="text-left space-y-2 text-sm">
                        <li><i class="fa-solid fa-check text-white mr-2"></i> Không giới hạn tìm kiếm</li>
                        <li><i class="fa-solid fa-check text-white mr-2"></i> Xem toàn bộ kết quả</li>
                        <li><i class="fa-solid fa-check text-white mr-2"></i> Xuất CSV</li>
                        <li><i class="fa-solid fa-check text-white mr-2"></i> Hoa hồng 20% Affiliate</li>
                        <li><i class="fa-solid fa-check text-white mr-2"></i> Hỗ trợ ưu tiên</li>
                    </ul>
                </div>
            </div>
            <a href="login.php?mode=register" class="inline-block mt-8 bg-red-600 hover:bg-red-700 text-white font-bold px-8 py-4 rounded-xl shadow-lg transition">
                Đăng Ký Ngay <i class="fa-solid fa-arrow-right ml-2"></i>
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-slate-900 text-slate-400 py-8">
        <div class="max-w-6xl mx-auto px-4 text-center">
            <p class="mb-2">© <?php echo date('Y'); ?> <strong class="text-white">HSHOP Media Việt Nam</strong>. All rights reserved.</p>
            <p class="text-sm">Hotline: <strong class="text-red-500"><?php echo SUPPORT_HOTLINE; ?></strong></p>
        </div>
    </footer>

</body>
</html>
