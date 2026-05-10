<?php
// 🎯 LANDING PAGE - HSHOP Analytics
// Modern, Mobile-First, Conversion-Optimized

require_once 'includes/session.php';
require_once 'includes/functions.php';

// Nếu đã login → vào scanner
if (isLoggedIn()) {
    header('Location: scanner.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HUNGNIWACO SHOP - Tìm đúng ngách - Bứt phá nhanh</title>
    <meta name="description" content="HUNGNIWACO SHOP - Tìm đúng ngách - Bứt phá nhanh - Tìm video viral, phân tích đối thủ, tạo kịch bản AI. FREE tools + Scanner chuyên nghiệp. Bắt đầu với 39K/3 ngày.">
    <meta property="og:image" content="https://hungniwaco.shop/logovip.png" />
    <link rel="icon" type="image/png" href="/logovip.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css?v=2">
    <!-- CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
        
        * { font-family: 'Inter', sans-serif; }
        
        /* Gradient Text */
        .gradient-text {
            background: linear-gradient(135deg, #f43f5e 0%, #ec4899 50%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Animated Gradient Background */
        .gradient-bg {
            background: linear-gradient(-45deg, #667eea, #764ba2, #f093fb, #f5576c);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
        }
        
        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        /* Glass Card */
        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        /* Hover Effects */
        .tool-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .tool-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        /* Mobile Menu Animation */
        .mobile-menu {
            transform: translateX(100%);
            transition: transform 0.3s ease-in-out;
        }
        .mobile-menu.active {
            transform: translateX(0);
        }
        
        /* Pulse Animation */
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.8; transform: scale(1.05); }
        }
        .pulse-badge {
            animation: pulse 2s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 overflow-x-hidden">

    <!-- 📱 MOBILE MENU OVERLAY -->
    <div id="mobileMenuOverlay" class="fixed inset-0 bg-black/50 z-40 hidden md:hidden" onclick="toggleMobileMenu()"></div>
    
    <!-- 📱 MOBILE MENU -->
    <div id="mobileMenu" class="mobile-menu fixed top-0 right-0 bottom-0 w-80 bg-white z-50 shadow-2xl md:hidden">
        <div class="p-6">
            <div class="flex items-center justify-between mb-8">
                <h3 class="text-lg font-black">Menu</h3>
                <button onclick="toggleMobileMenu()" class="text-slate-400 hover:text-slate-900">
                    <i class="fa-solid fa-times text-2xl"></i>
                </button>
            </div>
            <nav class="space-y-3">
                <a href="#features" onclick="toggleMobileMenu()" class="block px-4 py-3 rounded-lg hover:bg-slate-100 transition font-medium">
                    <i class="fa-solid fa-star mr-2 text-yellow-500"></i> Tính Năng
                </a>
                <a href="#pricing" onclick="toggleMobileMenu()" class="block px-4 py-3 rounded-lg hover:bg-slate-100 transition font-medium">
                    <i class="fa-solid fa-tag mr-2 text-green-500"></i> Bảng Giá
                </a>
                <a href="login.php" onclick="toggleMobileMenu()" class="block px-4 py-3 rounded-lg hover:bg-slate-100 transition font-medium">
                    Đăng nhập
                </a>
                <a href="login.php?mode=register" onclick="toggleMobileMenu()" class="block px-4 py-3 rounded-lg bg-slate-900 text-white font-bold text-center">
                    Đăng ký ngay
                </a>
            </nav>
        </div>
    </div>

    <!-- 🎨 HEADER -->
    <header class="bg-white/80 backdrop-blur-md border-b border-slate-200 sticky top-0 z-30 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="flex items-center justify-between h-16 sm:h-20">
                <!-- Logo -->
                <div class="flex items-center gap-2 sm:gap-3">
                    <div class="bg-gradient-to-br from-red-600 to-red-700 text-white p-2 sm:p-3 rounded-xl shadow-lg">
                        <i class="fa-brands fa-youtube text-xl sm:text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-base sm:text-xl font-black text-slate-900">
                            HSHOP <span class="text-red-600">Analytics</span>
                        </h1>
                        <p class="text-[8px] sm:text-[10px] text-slate-500 font-medium hidden sm:block">YouTube Viral Intelligence</p>
                    </div>
                </div>
                
                <!-- Desktop Nav -->
                <nav class="hidden md:flex items-center gap-6">
                    <a href="#features" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition">Tính Năng</a>
                    <a href="#pricing" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition">Bảng Giá</a>
                    <!-- <a href="affiliate.php" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition">
                        <i class="fa-solid fa-dollar-sign mr-1 text-green-600"></i> Kiếm Tiền
                    </a> -->
                    <a href="https://visora.net" target="_blank" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition">
                        <i class="fa-solid fa-microphone mr-1 text-purple-600"></i> Voice Clone
                    </a>
                    <a href="login.php" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition">Đăng nhập</a>
                    <a href="login.php?mode=register" class="bg-slate-900 hover:bg-black text-white font-bold px-5 py-2 rounded-lg transition text-sm">
                        Đăng ký
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <!-- 🚀 HERO SECTION -->
    <section class="relative gradient-bg py-16 sm:py-24 overflow-hidden">
        <div class="absolute inset-0 bg-black/20"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 text-center">
            <!-- Badge -->
            <div class="inline-flex items-center gap-2 bg-white/90 backdrop-blur-sm px-4 py-2 rounded-full mb-6 pulse-badge">
                <span class="text-xs sm:text-sm font-black text-transparent bg-clip-text bg-gradient-to-r from-red-600 to-pink-600">
                    ⚡ #1 YouTube Analytics Tool 
                </span>
            </div>
            
            <!-- Headline -->
            <h1 class="text-3xl sm:text-5xl md:text-7xl font-black text-white mb-4 sm:mb-6 leading-tight">
                Tìm Video Viral<br>
                <span class="text-yellow-300">Trước Khi Đối Thủ Biết</span>
            </h1>
            
            <!-- Subheadline -->
            <p class="text-base sm:text-xl text-white/90 max-w-3xl mx-auto mb-8 sm:mb-12 px-4">
                Phát hiện cơ hội vàng từ kênh nhỏ • Phân tích đối thủ chuyên sâu • Tạo kịch bản AI trong 60 giây
            </p>
            
            <!-- CTA Buttons -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-4 px-4">
                <a href="login.php?mode=register" class="w-full sm:w-auto bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-black px-8 py-4 rounded-xl transition shadow-2xl text-base sm:text-lg group">
                    <i class="fa-solid fa-wand-magic-sparkles mr-2 group-hover:animate-spin"></i>
                    Dùng Thử Scanner (39K/3 ngày)
                    <div class="text-xs font-normal mt-1">Không cần đăng ký • Miễn phí mãi mãi</div>
                </a>
            </div>
            
            <!-- Trust Badges -->
            <div class="flex flex-wrap items-center justify-center gap-6 sm:gap-8 mt-8 sm:mt-12 text-white/80 text-xs sm:text-sm px-4">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-check-circle text-green-400"></i>
                    <span></span>
                </div>
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-shield-check text-blue-400"></i>
                    <span>Bảo mật SSL 256-bit</span>
                </div>
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-users text-yellow-400"></i>
                    <span>1000+ Creators</span>
                </div>
            </div>
        </div>
    </section>

    <!-- ✨ FEATURES SECTION - REDESIGNED FOR MAX CONVERSION -->
    <section id="features" class="py-16 sm:py-24 bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <!-- Section Header -->
            <div class="text-center mb-12 sm:mb-16">
                <div class="inline-flex items-center gap-2 bg-purple-100 text-purple-700 px-5 py-2.5 rounded-full text-sm font-bold mb-6 shadow-md">
                    <i class="fa-solid fa-rocket"></i>
                    <span>CÔNG CỤ TOÀN DIỆN</span>
                </div>
                <h2 class="text-3xl sm:text-5xl font-black text-slate-900 mb-4 sm:mb-6 leading-tight">
                    5 Công Cụ <span class="gradient-text">Tạo Nên Sự Khác Biệt</span>
                </h2>
                <p class="text-base sm:text-xl text-slate-600 max-w-3xl mx-auto leading-relaxed">
                    Từ tìm ngách viral đến tạo nội dung AI hoàn chỉnh - Tất cả trong một nền tảng
                </p>
            </div>
            
            <!-- Core Tool Highlight (Full Width) -->
            <div class="mb-8 sm:mb-12">
                <div class="relative bg-gradient-to-r from-red-600 via-pink-600 to-red-600 rounded-3xl p-8 sm:p-10 lg:p-12 overflow-hidden shadow-2xl">
                    <!-- Decorative Background -->
                    <div class="absolute inset-0 bg-black/10"></div>
                    <div class="absolute top-0 right-0 w-96 h-96 bg-white/10 rounded-full -translate-y-48 translate-x-48"></div>
                    <div class="absolute bottom-0 left-0 w-64 h-64 bg-white/10 rounded-full translate-y-32 -translate-x-32"></div>
                    
                    <div class="relative z-10 grid grid-cols-1 lg:grid-cols-3 gap-8 items-center">
                        <!-- Left: Icon + Badge -->
                        <div class="text-center lg:text-left">
                            <div class="inline-flex items-center justify-center w-20 h-20 sm:w-24 sm:h-24 bg-white/20 backdrop-blur-sm rounded-3xl mb-4 shadow-2xl">
                                <i class="fa-solid fa-compass text-5xl sm:text-6xl text-white"></i>
                            </div>
                            <div class="inline-block bg-yellow-400 text-red-900 px-4 py-1.5 rounded-full text-xs sm:text-sm font-black shadow-lg">
                                🔥 CORE TOOL #1
                            </div>
                        </div>
                        
                        <!-- Middle: Description -->
                        <div class="lg:col-span-2 text-white">
                            <h3 class="text-2xl sm:text-3xl lg:text-4xl font-black mb-3 sm:mb-4">
                                YouTube Viral Scanner
                            </h3>
                            <p class="text-base sm:text-lg lg:text-xl mb-4 sm:mb-6 text-white/90 leading-relaxed">
                                Quét hàng triệu video để tìm <strong class="text-yellow-300">cơ hội vàng</strong> từ kênh nhỏ chưa bão hòa. 
                                AI phân tích chiến lược nội dung & đưa ra insights chuyên sâu.
                            </p>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4 mb-6">
                                <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3 sm:p-4 border border-white/30">
                                    <div class="flex items-start gap-2">
                                        <i class="fa-solid fa-bullseye text-yellow-300 text-xl mt-1"></i>
                                        <div>
                                            <div class="font-bold text-sm sm:text-base">Tìm Ngách Viral</div>
                                            <div class="text-xs text-white/80">Traffic cao, cạnh tranh thấp</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3 sm:p-4 border border-white/30">
                                    <div class="flex items-start gap-2">
                                        <i class="fa-solid fa-brain text-yellow-300 text-xl mt-1"></i>
                                        <div>
                                            <div class="font-bold text-sm sm:text-base">AI Deep Dive</div>
                                            <div class="text-xs text-white/80">Phân tích tone, style, cấu trúc</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3 sm:p-4 border border-white/30">
                                    <div class="flex items-start gap-2">
                                        <i class="fa-solid fa-vault text-yellow-300 text-xl mt-1"></i>
                                        <div>
                                            <div class="font-bold text-sm sm:text-base">Vault Storage</div>
                                            <div class="text-xs text-white/80">Lưu ý tưởng tiềm năng</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex flex-col sm:flex-row gap-3 sm:gap-4">
                                <a href="login.php?mode=register" class="inline-flex items-center justify-center gap-2 bg-white hover:bg-gray-50 text-red-600 font-black px-6 sm:px-8 py-3 sm:py-4 rounded-xl transition shadow-2xl text-sm sm:text-base group">
                                    <i class="fa-solid fa-rocket group-hover:scale-110 transition-transform"></i>
                                    <span>Dùng Thử 39K/3 Ngày</span>
                                </a>
                                <a href="#pricing" class="inline-flex items-center justify-center gap-2 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white font-bold px-6 sm:px-8 py-3 sm:py-4 rounded-xl transition border-2 border-white/30 text-sm sm:text-base">
                                    <span>Xem Bảng Giá</span>
                                    <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Secondary Tools Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8">
                
                <!-- Tool 2: AI Script Pro -->
                <div class="bg-white rounded-2xl sm:rounded-3xl p-6 sm:p-8 border-2 border-purple-200 shadow-lg hover:shadow-2xl transition-all hover:-translate-y-2">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-14 h-14 sm:w-16 sm:h-16 bg-gradient-to-br from-purple-600 to-pink-600 rounded-2xl flex items-center justify-center shadow-lg">
                            <i class="fa-solid fa-wand-magic-sparkles text-2xl sm:text-3xl text-white"></i>
                        </div>
                        <div class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-black">
                            ✨ FREE
                        </div>
                    </div>
                    <h3 class="text-lg sm:text-xl font-black text-slate-900 mb-2">AI Kịch Bản Video</h3>
                    <p class="text-sm text-slate-600 mb-4 leading-relaxed">
                        Chuyển kịch bản thành <strong>Video/Image Prompts</strong> + SEO metadata trong 60s.
                    </p>
                    <ul class="space-y-2 mb-5 text-xs text-slate-700">
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-purple-600 mt-0.5"></i>
                            <span>Scene-by-scene prompts</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-purple-600 mt-0.5"></i>
                            <span>SEO title + description</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-purple-600 mt-0.5"></i>
                            <span>Thumbnail prompt CTR 40%+</span>
                        </li>
                    </ul>
                    <a href="scriptpro.php" target="_blank" class="inline-flex items-center justify-center w-full bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold px-5 py-3 rounded-xl transition shadow-lg text-sm group">
                        <span>Dùng Ngay</span>
                        <i class="fa-solid fa-external-link-alt ml-2 text-xs group-hover:translate-x-1 transition-transform"></i>
                    </a>
                </div>
                
                <!-- Tool 3: AI Thumbnail Generator -->
                <div class="bg-white rounded-2xl sm:rounded-3xl p-6 sm:p-8 border-2 border-orange-200 shadow-lg hover:shadow-2xl transition-all hover:-translate-y-2">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-14 h-14 sm:w-16 sm:h-16 bg-gradient-to-br from-yellow-500 to-orange-600 rounded-2xl flex items-center justify-center shadow-lg">
                            <i class="fa-solid fa-image text-2xl sm:text-3xl text-white"></i>
                        </div>
                        <div class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-black">
                            ✨ FREE
                        </div>
                    </div>
                    <h3 class="text-lg sm:text-xl font-black text-slate-900 mb-2">AI Thumbnail Maker</h3>
                    <p class="text-sm text-slate-600 mb-4 leading-relaxed">
                        Tạo thumbnail viral với <strong>AI prompt chuyên nghiệp</strong>. Hỗ trợ nhiều style.
                    </p>
                    <ul class="space-y-2 mb-5 text-xs text-slate-700">
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-orange-600 mt-0.5"></i>
                            <span>10+ visual styles</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-orange-600 mt-0.5"></i>
                            <span>CTR optimization</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-orange-600 mt-0.5"></i>
                            <span>Export high-resolution</span>
                        </li>
                    </ul>
                    <a href="free-tools.php#thumbnail" class="inline-flex items-center justify-center w-full bg-gradient-to-r from-yellow-500 to-orange-600 hover:from-yellow-600 hover:to-orange-700 text-white font-bold px-5 py-3 rounded-xl transition shadow-lg text-sm group">
                        <span>Dùng Ngay</span>
                        <i class="fa-solid fa-external-link-alt ml-2 text-xs group-hover:translate-x-1 transition-transform"></i>
                    </a>
                </div>
                
                <!-- Tool 4: Affiliate Program -->
                <div class="bg-white rounded-2xl sm:rounded-3xl p-6 sm:p-8 border-2 border-blue-200 shadow-lg hover:shadow-2xl transition-all hover:-translate-y-2">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-14 h-14 sm:w-16 sm:h-16 bg-gradient-to-br from-blue-600 to-cyan-600 rounded-2xl flex items-center justify-center shadow-lg">
                            <i class="fa-solid fa-hand-holding-dollar text-2xl sm:text-3xl text-white"></i>
                        </div>
                        <div class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-xs font-black">
                            💰 20-50%
                        </div>
                    </div>
                    <h3 class="text-lg sm:text-xl font-black text-slate-900 mb-2">Affiliate Partner</h3>
                    <p class="text-sm text-slate-600 mb-4 leading-relaxed">
                        Giới thiệu tool và nhận <strong>20%-50% hoa hồng</strong> trọn đời từ mỗi người dùng.
                    </p>
                    <ul class="space-y-2 mb-5 text-xs text-slate-700">
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-blue-600 mt-0.5"></i>
                            <span>20%-50% recurring commission</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-blue-600 mt-0.5"></i>
                            <span>Thanh toán ngày 5-30 hàng tháng</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-blue-600 mt-0.5"></i>
                            <span>Auto payout to bank</span>
                        </li>
                    </ul>
                    <a href="affiliate.php" class="inline-flex items-center justify-center w-full bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white font-bold px-5 py-3 rounded-xl transition shadow-lg text-sm group">
                        <span>Tham Gia Ngay</span>
                        <i class="fa-solid fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                    </a>
                </div>
                
                <!-- Tool 5: Voice Cloning -->
                <div class="bg-white rounded-2xl sm:rounded-3xl p-6 sm:p-8 border-2 border-indigo-200 shadow-lg hover:shadow-2xl transition-all hover:-translate-y-2">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-14 h-14 sm:w-16 sm:h-16 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-2xl flex items-center justify-center shadow-lg">
                            <i class="fa-solid fa-microphone text-2xl sm:text-3xl text-white"></i>
                        </div>
                        <div class="bg-indigo-100 text-indigo-700 px-3 py-1 rounded-full text-xs font-black">
                            🎤 Partner
                        </div>
                    </div>
                    <h3 class="text-lg sm:text-xl font-black text-slate-900 mb-2">Voice Cloning</h3>
                    <p class="text-sm text-slate-600 mb-4 leading-relaxed">
                        Clone giọng nói với <strong>AI chất lượng cao</strong>. Giá rẻ nhất thị trường.
                    </p>
                    <ul class="space-y-2 mb-5 text-xs text-slate-700">
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-indigo-600 mt-0.5"></i>
                            <span>AI Voice Clone chuyên nghiệp</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-indigo-600 mt-0.5"></i>
                            <span>Giá rẻ nhất thị trường</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-indigo-600 mt-0.5"></i>
                            <span>Multiple voice models</span>
                        </li>
                    </ul>
                    <a href="https://visora.net" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center w-full bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold px-5 py-3 rounded-xl transition shadow-lg text-sm group">
                        <span>Truy Cập Visora.net</span>
                        <i class="fa-solid fa-external-link-alt ml-2 text-xs group-hover:translate-x-1 transition-transform"></i>
                    </a>
                </div>
                
            </div>
        </div>
    </section>

    <!-- 📊 HOW IT WORKS -->
    <section class="py-16 sm:py-24 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="text-center mb-12 sm:mb-16">
                <h2 class="text-3xl sm:text-5xl font-black text-slate-900 mb-3 sm:mb-4">
                    Chỉ Cần <span class="gradient-text">3 Bước</span>
                </h2>
                <p class="text-base sm:text-xl text-slate-600">Từ tìm ý tưởng đến có video viral</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 sm:gap-8">
                <!-- Step 1 -->
                <div class="relative">
                    <div class="absolute -top-4 -left-4 w-12 h-12 bg-gradient-to-br from-red-500 to-pink-600 rounded-full flex items-center justify-center shadow-lg z-10">
                        <span class="text-xl font-black text-white">1</span>
                    </div>
                    <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-200 h-full">
                        <div class="w-16 h-16 bg-red-100 rounded-xl flex items-center justify-center mb-4">
                            <i class="fa-solid fa-magnifying-glass text-2xl text-red-600"></i>
                        </div>
                        <h3 class="text-lg sm:text-xl font-bold text-slate-900 mb-3">Tìm Cơ Hội Vàng</h3>
                        <p class="text-sm text-slate-600">
                            Dùng <strong>YouTube Viral Scanner</strong> để tìm keyword chưa bão hòa + video tiềm năng từ kênh nhỏ.
                        </p>
                    </div>
                </div>
                
                <!-- Step 2 -->
                <div class="relative">
                    <div class="absolute -top-4 -left-4 w-12 h-12 bg-gradient-to-br from-purple-600 to-pink-600 rounded-full flex items-center justify-center shadow-lg z-10">
                        <span class="text-xl font-black text-white">2</span>
                    </div>
                    <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-200 h-full">
                        <div class="w-16 h-16 bg-purple-100 rounded-xl flex items-center justify-center mb-4">
                            <i class="fa-solid fa-wand-magic-sparkles text-2xl text-purple-600"></i>
                        </div>
                        <h3 class="text-lg sm:text-xl font-bold text-slate-900 mb-3">Tạo Kịch Bản AI</h3>
                        <p class="text-sm text-slate-600">
                            Dùng <strong>AI Script Pro</strong> (FREE) để chuyển ý tưởng thành video/image prompts + SEO đầy đủ.
                        </p>
                    </div>
                </div>
                
                <!-- Step 3 -->
                <div class="relative">
                    <div class="absolute -top-4 -left-4 w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center shadow-lg z-10">
                        <span class="text-xl font-black text-white">3</span>
                    </div>
                    <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-200 h-full">
                        <div class="w-16 h-16 bg-green-100 rounded-xl flex items-center justify-center mb-4">
                            <i class="fa-solid fa-rocket text-2xl text-green-600"></i>
                        </div>
                        <h3 class="text-lg sm:text-xl font-bold text-slate-900 mb-3">Đăng & Viral</h3>
                        <p class="text-sm text-slate-600">
                            Dùng prompts để tạo video (AI tools), thumbnail CTR cao → Upload → Theo dõi analytics.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 💰 PRICING SECTION - Updated to match official pricing_data.php -->
    <section id="pricing" class="py-16 sm:py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="text-center mb-8 sm:mb-12">
                <h2 class="text-3xl sm:text-5xl font-black text-slate-900 mb-3 sm:mb-4">
                    Bảng Giá <span class="gradient-text">Minh Bạch</span>
                </h2>
                <p class="text-base sm:text-xl text-slate-600 mb-4">Chọn gói phù hợp với bạn</p>
                
                <!-- SOCIAL PROOF MINI -->
                <div class="flex flex-wrap justify-center gap-4 sm:gap-6 mb-6">
                    <div class="bg-gradient-to-r from-blue-50 to-blue-100 border border-blue-300 rounded-xl px-4 py-2">
                        <div class="text-lg sm:text-xl font-black text-blue-600">2.5K+</div>
                        <div class="text-xs text-blue-700">Người dùng</div>
                    </div>
                    <div class="bg-gradient-to-r from-green-50 to-green-100 border border-green-300 rounded-xl px-4 py-2">
                        <div class="text-lg sm:text-xl font-black text-green-600">50K+</div>
                        <div class="text-xs text-green-700">Video phân tích</div>
                    </div>
                    <div class="bg-gradient-to-r from-yellow-50 to-yellow-100 border border-yellow-300 rounded-xl px-4 py-2">
                        <div class="text-lg sm:text-xl font-black text-yellow-600">4.9/5</div>
                        <div class="text-xs text-yellow-700">Đánh giá</div>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 sm:gap-8 max-w-5xl mx-auto">
                
                <!-- TRIAL Tier - 39K/3 ngày -->
                <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-2xl p-6 sm:p-8 border-2 border-green-500 shadow-lg">
                    <div class="text-center mb-6">
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fa-solid fa-rocket text-3xl text-green-600"></i>
                        </div>
                        <h3 class="text-xl sm:text-2xl font-black text-slate-900 mb-2">Trial Scanner</h3>
                        <div class="text-3xl sm:text-4xl font-black text-green-600 mb-2">39.000đ</div>
                        <p class="text-xs sm:text-sm text-slate-600">3 ngày / ♾️ Không giới hạn tìm kiếm</p>
                    </div>
                    <ul class="space-y-3 mb-6 text-sm">
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-green-500 mt-1"></i>
                            <span><strong>YouTube Viral Scanner</strong></span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-green-500 mt-1"></i>
                            <span>AI Deep Dive Analysis</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-green-500 mt-1"></i>
                            <span>Vault lưu ý tưởng</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-green-500 mt-1"></i>
                            <span>Tất cả FREE tools</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-green-500 mt-1"></i>
                            <span>Tự gắn YouTube API key</span>
                        </li>
                    </ul>
                    <a href="login.php?mode=register" class="block w-full bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-bold px-6 py-3 rounded-xl transition text-center shadow-lg">
                        Dùng Thử Ngay
                    </a>
                </div>
                
                <!-- BASIC Tier - 99K/tháng -->
                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl p-6 sm:p-8 border-2 border-blue-500 shadow-lg relative">
                    <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-xs font-black px-4 py-1 rounded-full">
                        🔥 GIÁ SỐC 67% OFF
                    </div>
                    <div class="text-center mb-6 mt-2">
                        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fa-solid fa-bolt text-3xl text-blue-600"></i>
                        </div>
                        <h3 class="text-xl sm:text-2xl font-black text-slate-900 mb-2">Basic</h3>
                        <div class="text-3xl sm:text-4xl font-black text-blue-600 mb-2">99.000đ</div>
                        <p class="text-xs sm:text-sm text-slate-600">1 tháng / ~3.300đ/ngày</p>
                        <p class="text-xs text-red-600 font-bold mt-1 line-through">Giá gốc: 299.000đ</p>
                    </div>
                    <ul class="space-y-3 mb-6 text-sm">
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-blue-500 mt-1"></i>
                            <span><strong>♾️ Không giới hạn tìm kiếm</strong></span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-blue-500 mt-1"></i>
                            <span>Xuất CSV không giới hạn</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-blue-500 mt-1"></i>
                            <span>Phân tích thumbnail AI</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-blue-500 mt-1"></i>
                            <span>Hỗ trợ ưu tiên</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-blue-500 mt-1"></i>
                            <span>Hoa hồng CTV 20%</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-blue-500 mt-1"></i>
                            <span>Tự gắn YouTube API key</span>
                        </li>
                    </ul>
                    <a href="pricing.php?plan=1m" class="block w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold px-6 py-3 rounded-xl transition text-center shadow-lg">
                        Mua Ngay
                    </a>
                </div>
                
                <!-- PREMIUM Tier - 653K/năm -->
                <div class="bg-gradient-to-br from-yellow-50 to-amber-50 rounded-2xl p-6 sm:p-8 border-2 border-yellow-600 shadow-2xl relative">
                    <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-gradient-to-r from-yellow-600 to-amber-600 text-white text-xs font-black px-4 py-1 rounded-full">
                        👑 GIÁ TỐT NHẤT - 45% OFF
                    </div>
                    <div class="text-center mb-6 mt-2">
                        <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fa-solid fa-crown text-3xl text-yellow-600"></i>
                        </div>
                        <h3 class="text-xl sm:text-2xl font-black text-slate-900 mb-2">Premium</h3>
                        <div class="text-3xl sm:text-4xl font-black text-yellow-600 mb-2">653.400đ</div>
                        <p class="text-xs sm:text-sm text-slate-600">12 tháng / ~1.790đ/ngày</p>
                        <p class="text-xs text-red-600 font-bold mt-1 line-through">Giá gốc: 1.188.000đ</p>
                        <p class="text-xs text-green-600 font-bold mt-1">💰 Tiết kiệm 534.600đ</p>
                    </div>
                    <ul class="space-y-3 mb-6 text-sm">
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-yellow-600 mt-1"></i>
                            <span><strong>♾️ Không giới hạn tìm kiếm</strong></span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-yellow-600 mt-1"></i>
                            <span>Xuất CSV không giới hạn</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-yellow-600 mt-1"></i>
                            <span>🧠 Phân tích Deep AI (Gemini)</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-yellow-600 mt-1"></i>
                            <span>Phân tích thumbnail AI</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-yellow-600 mt-1"></i>
                            <span>Hỗ trợ VIP ưu tiên</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-yellow-600 mt-1"></i>
                            <span>Hoa hồng CTV 20%-50%</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-yellow-600 mt-1"></i>
                            <span>Tự gắn YouTube API key</span>
                        </li>
                    </ul>
                    <a href="pricing.php?plan=12m" class="block w-full bg-gradient-to-r from-yellow-600 to-amber-600 hover:from-yellow-700 hover:to-amber-700 text-white font-bold px-6 py-3 rounded-xl transition text-center shadow-lg">
                        Mua Ngay - Giá Tốt Nhất
                    </a>
                </div>
                
            </div>
            
            <!-- CTA to Full Pricing -->
            <div class="mt-12 text-center">
                <div class="bg-gradient-to-r from-slate-900 to-blue-900 rounded-3xl p-8 sm:p-12 shadow-2xl">
                    <h3 class="text-2xl sm:text-3xl font-black text-white mb-3">
                        🎯 Muốn Xem Thêm Gói?
                    </h3>
                    <p class="text-lg text-slate-200 mb-6">
                        Xem đầy đủ 5 gói với <strong class="text-yellow-400">giảm giá đến 45%</strong> cho gói dài hạn!
                    </p>
                    <a href="pricing.php" class="inline-block bg-gradient-to-r from-yellow-400 to-orange-500 hover:from-yellow-500 hover:to-orange-600 text-slate-900 font-black px-10 py-4 rounded-xl transition shadow-xl text-lg">
                        Xem Đầy Đủ Bảng Giá <i class="fa-solid fa-arrow-right ml-2"></i>
                    </a>
                </div>
            </div>
            
            <!-- Trust Badge -->
            <div class="text-center mt-12 sm:mt-16">
                <p class="text-sm text-slate-600 mb-4">
                    <i class="fa-solid fa-lock mr-2 text-green-600"></i>
                    Thanh toán an toàn 100% • Bảo mật dữ liệu tuyệt đối
                </p>
                <div class="flex items-center justify-center gap-6 text-xs text-slate-500">
                    <span>✓ HTTPS 256-bit SSL</span>
                    
                    <span>✓ Made in Vietnam</span>
                </div>
            </div>
        </div>
    </section>

    <!-- 🎉 FINAL CTA -->
    <section class="py-16 sm:py-24 gradient-bg relative">
        <div class="absolute inset-0 bg-black/20"></div>
        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 text-center">
            <h2 class="text-3xl sm:text-5xl font-black text-white mb-4 sm:mb-6">
                Sẵn Sàng Viral Ngay Hôm Nay?
            </h2>
            <p class="text-base sm:text-xl text-white/90 mb-8 sm:mb-12 max-w-2xl mx-auto">
                Tham gia cùng 1000+ creators đang tìm video viral và tạo nội dung AI mỗi ngày
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="login.php?mode=register" class="w-full sm:w-auto bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-black px-10 py-4 rounded-xl transition shadow-2xl text-lg">
                    <i class="fa-solid fa-gift mr-2"></i> Dùng Thử 39K/3 Ngày
                </a>
            </div>
        </div>
    </section>

    <!-- 📄 FOOTER -->
    <footer class="bg-slate-900 text-slate-300 py-12 sm:py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-8 mb-8">
                <!-- Company Info -->
                <div>
                    <h4 class="font-bold text-white mb-4 text-sm sm:text-base">HSHOP Analytics</h4>
                    <p class="text-xs sm:text-sm text-slate-400 mb-3">
                        YouTube Viral Intelligence Platform
                    </p>
                    <div class="flex gap-3">
                        <a href="#" class="w-8 h-8 bg-slate-800 hover:bg-slate-700 rounded-lg flex items-center justify-center transition">
                            <i class="fa-brands fa-facebook"></i>
                        </a>
                        <a href="#" class="w-8 h-8 bg-slate-800 hover:bg-slate-700 rounded-lg flex items-center justify-center transition">
                            <i class="fa-brands fa-youtube"></i>
                        </a>
                        <a href="#" class="w-8 h-8 bg-slate-800 hover:bg-slate-700 rounded-lg flex items-center justify-center transition">
                            <i class="fa-brands fa-telegram"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Products -->
                <div>
                    <h4 class="font-bold text-white mb-4 text-sm sm:text-base">Sản Phẩm</h4>
                    <ul class="space-y-2 text-xs sm:text-sm">
                        <li><a href="login.php" class="hover:text-white transition">Đăng nhập</a></li>
                        <li><a href="login.php?mode=register" class="hover:text-white transition">Đăng ký</a></li>
                        <li><a href="pricing.php" class="hover:text-white transition">Bảng giá</a></li>
                        <li><a href="affiliate.php" class="hover:text-white transition">Affiliate Program</a></li>
                    </ul>
                </div>
                
                <!-- Support -->
                <div>
                    <h4 class="font-bold text-white mb-4 text-sm sm:text-base">Hỗ Trợ</h4>
                    <ul class="space-y-2 text-xs sm:text-sm">
                        <li><a href="login.php" class="hover:text-white transition">Đăng nhập</a></li>
                        <li><a href="login.php?mode=register" class="hover:text-white transition">Đăng ký</a></li>
                        <li><a href="pricing.php" class="hover:text-white transition">Bảng giá</a></li>
                    </ul>
                </div>
                
                <!-- Legal -->
                <div>
                    <h4 class="font-bold text-white mb-4 text-sm sm:text-base">Pháp Lý</h4>
                    <ul class="space-y-2 text-xs sm:text-sm text-slate-400">
                        <li><strong class="text-white">HSHOP MEDIA VIỆT NAM</strong></li>
                       
                    </ul>
                </div>
            </div>
            
            <!-- Bottom Bar -->
            <div class="border-t border-slate-800 pt-8 text-center">
                <p class="text-xs sm:text-sm text-slate-400">
                    © 2026 HSHOP Analytics • Made with ❤️ in Vietnam
                </p>
            </div>
        </div>
    </footer>

    <!-- 📱 MOBILE MENU SCRIPT -->
    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            const overlay = document.getElementById('mobileMenuOverlay');
            
            // Toggle active class
            menu.classList.toggle('active');
            overlay.classList.toggle('hidden');
            
            // Prevent body scroll when menu open
            if (menu.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        }
        
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                
                // Skip empty anchors
                if (!href || href === '#') return;
                
                const target = document.querySelector(href);
                if (target) {
                    // ✅ ONLY prevent default for valid targets
                    e.preventDefault();
                    
                    // Close mobile menu if open
                    const mobileMenu = document.getElementById('mobileMenu');
                    if (mobileMenu && mobileMenu.classList.contains('active')) {
                        toggleMobileMenu();
                    }
                    
                    // Smooth scroll with offset for fixed header
                    const headerHeight = 80;
                    const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - headerHeight;
                    
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>

</body>
</html>
