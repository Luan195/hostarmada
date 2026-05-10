<?php
// 🎊 TẾT 2026 LANDING PAGE - CAMPAIGN EXCLUSIVE
require_once 'includes/session.php';
require_once 'includes/functions.php';
require_once 'config/lixitet.php'; // 🧧 Lì Xì Tết Pricing (SECURE - outside public)

// Check if campaign is still active
$isCampaignActive = isTetCampaignActive();
$daysRemaining = getTetCampaignDaysRemaining();

// Get Tet pricing
$tetPlans = getAllTetPricingPlans();
$plan6m = $tetPlans['tet_6m'];
$plan12m = $tetPlans['tet_12m'];

// Get comparison with regular prices
$comparison = getTetPricingComparison();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎊 Lì Xì Tết 2026 - Giảm Đến 79% | HSHOP Media VN</title>
    <meta name="description" content="Ưu đãi đặc biệt dịp Tết Bính Ngọ 🐎 2026! Chỉ 399K/năm - Giảm 791K (79%). YouTube Scanner Pro với giá chưa từng có!">
    
    <!-- CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&family=Dela+Gothic+One&display=swap');
        
        * { 
            font-family: 'Inter', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }
        
        html {
            scroll-behavior: smooth;
        }
        
        .font-tet { font-family: 'Dela Gothic One', cursive; }
        
        /* Gradient Background Tết */
        .gradient-tet {
            background: linear-gradient(135deg, #ff0000 0%, #ff6b00 25%, #ffd700 50%, #ff6b00 75%, #ff0000 100%);
            background-size: 400% 400%;
            animation: tetGradient 8s ease infinite;
        }
        
        @keyframes tetGradient {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        /* Confetti Animation */
        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background: #ffd700;
            position: absolute;
            animation: confettiFall 5s linear infinite;
        }
        
        @keyframes confettiFall {
            to { transform: translateY(100vh) rotate(360deg); }
        }
        
        /* Pulse Red */
        @keyframes pulseRed {
            0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255, 0, 0, 0.7); }
            50% { transform: scale(1.05); box-shadow: 0 0 0 15px rgba(255, 0, 0, 0); }
        }
        
        .pulse-red {
            animation: pulseRed 2s infinite;
        }
        
        /* Gold Shine */
        @keyframes goldShine {
            0% { background-position: -200% center; }
            100% { background-position: 200% center; }
        }
        
        .gold-shine {
            background: linear-gradient(90deg, #ffd700 25%, #ffed4e 50%, #ffd700 75%);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: goldShine 3s linear infinite;
        }
        
        /* Tet Card */
        .tet-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(255, 255, 255, 0.85) 100%);
            backdrop-filter: blur(10px);
            border: 3px solid #ffd700;
            box-shadow: 0 20px 60px rgba(255, 0, 0, 0.3);
            transition: all 0.3s ease;
        }
        
        .tet-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 30px 80px rgba(255, 0, 0, 0.5);
        }
        
        /* Countdown */
        .countdown-box {
            background: linear-gradient(135deg, #ff0000, #ff6b00);
            box-shadow: 0 10px 30px rgba(255, 0, 0, 0.5);
        }
        
        /* Mobile optimizations */
        @media (max-width: 640px) {
            .tet-card {
                border-width: 2px;
            }
            
            .tet-card:hover {
                transform: translateY(-5px) scale(1.01);
            }
        }
        
        /* Smooth transitions for interactive elements */
        button, a, summary {
            transition: all 0.3s ease;
        }
        
        /* Better touch targets for mobile */
        @media (hover: none) and (pointer: coarse) {
            button, a, summary {
                min-height: 44px;
                min-width: 44px;
            }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-red-50 via-orange-50 to-yellow-50 overflow-x-hidden">

    <!-- 🎊 HEADER -->
    <header class="bg-white/90 backdrop-blur-md border-b-4 border-red-600 sticky top-0 z-50 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="flex items-center justify-between h-20">
                <!-- Logo -->
                <a href="index.php" class="flex items-center gap-3">
                    <div class="bg-gradient-to-br from-red-600 to-red-700 text-white p-3 rounded-xl shadow-lg">
                        <i class="fa-brands fa-youtube text-3xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-black text-slate-900">
                            HSHOP <span class="text-red-600">Analytics</span>
                        </h1>
                        <p class="text-xs text-red-600 font-bold">🎊 CHƯƠNG TRÌNH TẾT 2026</p>
                    </div>
                </a>
                
                <!-- Desktop Nav -->
                <nav class="hidden md:flex items-center gap-4">
                    <a href="#pricing" class="text-sm font-medium text-slate-600 hover:text-red-600 transition">
                        <i class="fa-solid fa-tags mr-1"></i> Bảng Giá
                    </a>
                    <a href="#setup-guide" class="text-sm font-medium text-slate-600 hover:text-blue-600 transition">
                        <i class="fa-solid fa-book mr-1"></i> Hướng Dẫn
                    </a>
                    <?php if (isLoggedIn()): ?>
                        <a href="scanner.php" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition">
                            <i class="fa-solid fa-compass mr-1"></i> Scanner
                        </a>
                        <span class="text-sm font-bold text-green-600">
                            <i class="fa-solid fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </span>
                    <?php else: ?>
                        <a href="login.php" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition">
                            Đăng nhập
                        </a>
                        <a href="login.php?mode=register" class="bg-red-600 hover:bg-red-700 text-white font-bold px-5 py-2.5 rounded-lg transition text-sm shadow-lg hover:shadow-xl">
                            Đăng ký ngay
                        </a>
                    <?php endif; ?>
                </nav>
                
                <!-- Mobile Menu Button -->
                <button onclick="toggleMobileMenu()" class="md:hidden text-red-600 p-2">
                    <i class="fa-solid fa-bars text-2xl"></i>
                </button>
            </div>
        </div>
    </header>

    <?php if (!$isCampaignActive): ?>
        <!-- Campaign Ended Notice -->
        <div class="max-w-4xl mx-auto px-4 py-20 text-center">
            <div class="bg-white rounded-3xl p-12 shadow-2xl">
                <i class="fa-solid fa-calendar-xmark text-8xl text-gray-400 mb-6"></i>
                <h2 class="text-4xl font-black text-slate-900 mb-4">Chương Trình Đã Kết Thúc</h2>
                <p class="text-xl text-slate-600 mb-8">
                    Ưu đãi Tết 2026 đã kết thúc. Cảm ơn quý khách đã quan tâm!
                </p>
                <div class="flex gap-4 justify-center">
                    <a href="pricing.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-8 py-4 rounded-xl transition text-lg">
                        <i class="fa-solid fa-tags mr-2"></i> Xem Bảng Giá Thường
                    </a>
                    <a href="index.php" class="bg-slate-600 hover:bg-slate-700 text-white font-bold px-8 py-4 rounded-xl transition text-lg">
                        <i class="fa-solid fa-home mr-2"></i> Về Trang Chủ
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>

    <!-- 🎊 HERO SECTION -->
    <section class="relative gradient-tet py-20 overflow-hidden">
        <!-- Decorative Elements -->
        <div class="absolute inset-0 bg-black/10"></div>
        <div class="absolute top-10 left-10 text-9xl opacity-10">🧧</div>
        <div class="absolute bottom-10 right-10 text-9xl opacity-10">🎊</div>
        
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 text-center">
            <!-- Countdown Timer -->
            <div class="mb-8">
                <?php echo getTetCountdownHTML(); ?>
            </div>
            
            <!-- Main Headline -->
            <h1 class="text-5xl sm:text-7xl md:text-8xl font-black text-white mb-6 leading-tight drop-shadow-2xl font-tet">
                🧧 LÌ XÌ TẾT 2026
            </h1>
            
            <div class="text-4xl sm:text-6xl font-black mb-8">
                <span class="gold-shine font-tet">GIẢM ĐẾN 79%</span>
            </div>
            
            <!-- Chúc Tết Message -->
            <div class="max-w-3xl mx-auto mb-12 bg-white/20 backdrop-blur-md rounded-3xl p-8 border-4 border-yellow-300">
                <p class="text-2xl sm:text-3xl text-white font-bold mb-4 leading-relaxed">
                    🎊 Chúc Quý Khách<br>
                    <span class="text-yellow-300">Năm Mới An Khang Thịnh Vượng</span><br>
                    Sự Nghiệp Phát Đạt - Kênh Viral Triệu View 🎊
                </p>
                <p class="text-lg text-white/90">
                    Nhân dịp Xuân Tết Bính Ngọ 🐎 2026, HSHOP Media VN gửi tặng "LÌ XÌ ĐẶC BIỆT"<br>
                    <strong class="text-yellow-300"></strong>
                </p>
            </div>
            
            <!-- CTA Buttons -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-8">
                <a href="#pricing" class="w-full sm:w-auto bg-yellow-400 hover:bg-yellow-500 text-red-900 font-black px-12 py-5 rounded-2xl transition shadow-2xl text-xl pulse-red">
                    <i class="fa-solid fa-gift mr-2"></i>
                    XEM ƯU ĐÃI NGAY
                </a>
                <a href="#compare" class="w-full sm:w-auto bg-white hover:bg-gray-100 text-red-600 font-bold px-12 py-5 rounded-2xl transition shadow-xl text-xl">
                    <i class="fa-solid fa-chart-line mr-2"></i>
                    So Sánh Giá
                </a>
            </div>
            
            <!-- Trust Badges -->
            <div class="flex flex-wrap items-center justify-center gap-6 text-white/90 text-sm">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-fire text-yellow-300 text-xl"></i>
                    <span class="font-bold">999+ đơn đã mua Tết này</span>
                </div>
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-shield-check text-green-300 text-xl"></i>
                    <span class="font-bold">Bảo mật 100%</span>
                </div>
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-clock text-blue-300 text-xl"></i>
                    <span class="font-bold">Kích hoạt tức thì</span>
                </div>
            </div>
        </div>
    </section>

    <!-- 💰 PRICING SECTION -->
    <section id="pricing" class="py-16 sm:py-20 lg:py-24 bg-gradient-to-br from-white via-red-50 to-orange-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Section Header -->
            <div class="text-center mb-12 sm:mb-16 lg:mb-20">
                <h2 class="text-4xl sm:text-5xl lg:text-6xl font-black text-slate-900 mb-3 sm:mb-4 font-tet leading-tight">
                    🎁 BẢNG GIÁ TẾT 2026
                </h2>
                <p class="text-xl sm:text-2xl text-red-600 font-bold mb-2">
        
                </p>
                <p class="text-base sm:text-lg text-slate-600 max-w-2xl mx-auto px-4">
                    hết ưu đãi, giá quay về mức thường (tăng 2-3 lần)
                </p>
            </div>
            
            <!-- Pricing Cards -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8 max-w-6xl mx-auto mb-16 sm:mb-20">
                
                <!-- 6 Months Plan -->
                <div class="tet-card rounded-2xl sm:rounded-3xl p-6 sm:p-8 relative">
                    <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-red-600 text-white text-xs sm:text-sm font-black px-4 sm:px-6 py-1.5 sm:py-2 rounded-full shadow-lg whitespace-nowrap">
                        <?php echo $plan6m['badge']; ?>
                    </div>
                    
                    <div class="text-center mb-6 mt-2 sm:mt-4">
                        <div class="w-16 h-16 sm:w-20 sm:h-20 bg-gradient-to-br from-red-500 to-pink-600 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                            <i class="fa-solid fa-gift text-3xl sm:text-4xl text-white"></i>
                        </div>
                        <h3 class="text-2xl sm:text-3xl font-black text-slate-900 mb-2"><?php echo $plan6m['name']; ?></h3>
                        <p class="text-sm text-slate-600"><?php echo $plan6m['duration']; ?></p>
                    </div>
                    
                    <!-- Pricing -->
                    <div class="text-center mb-6">
                        <div class="text-gray-500 line-through text-xl sm:text-2xl mb-2">
                            <?php echo formatTetPrice($plan6m['original_price']); ?>
                        </div>
                        <div class="text-5xl sm:text-6xl font-black text-red-600 mb-3">
                            <?php echo formatTetPrice($plan6m['tet_price']); ?>
                        </div>
                        <div class="inline-block bg-green-100 text-green-700 px-3 sm:px-4 py-2 rounded-full text-xs sm:text-sm font-bold mb-2">
                            💰 Tiết kiệm <?php echo formatTetPrice($plan6m['save_amount']); ?> (<?php echo $plan6m['discount_percent']; ?>%)
                        </div>
                        <p class="text-sm text-slate-600 mt-1">
                            Chỉ <?php echo formatTetPrice($plan6m['per_day']); ?>/ngày
                        </p>
                    </div>
                    
                    <!-- Features -->
                    <ul class="space-y-2.5 sm:space-y-3 mb-6 sm:mb-8">
                        <?php foreach ($plan6m['features'] as $feature): ?>
                        <li class="flex items-start gap-2 sm:gap-3 text-xs sm:text-sm">
                            <i class="fa-solid fa-check-circle text-green-500 mt-0.5 flex-shrink-0 text-base sm:text-lg"></i>
                            <span class="leading-relaxed"><?php echo $feature; ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <!-- CTA -->
                    <a href="<?php echo isLoggedIn() ? 'tet-checkout.php?plan=tet_6m' : 'login.php?redirect=tet-checkout.php&plan=tet_6m'; ?>" 
                       class="block w-full bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-700 hover:to-pink-700 text-white font-black px-6 sm:px-8 py-3.5 sm:py-4 rounded-xl transition shadow-lg text-center text-base sm:text-lg touch-manipulation">
                        <?php echo $plan6m['cta']; ?>
                    </a>
                </div>
                
                <!-- 12 Months Plan (POPULAR) -->
                <div class="tet-card rounded-2xl sm:rounded-3xl p-6 sm:p-8 relative transform lg:scale-105 shadow-2xl">
                    <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-yellow-500 text-slate-900 text-xs sm:text-sm font-black px-4 sm:px-6 py-1.5 sm:py-2 rounded-full shadow-lg pulse-red whitespace-nowrap">
                        <?php echo $plan12m['badge']; ?>
                    </div>
                    
                    <div class="text-center mb-6 mt-2 sm:mt-4">
                        <div class="w-16 h-16 sm:w-20 sm:h-20 bg-gradient-to-br from-yellow-500 to-orange-600 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                            <i class="fa-solid fa-crown text-3xl sm:text-4xl text-white"></i>
                        </div>
                        <h3 class="text-2xl sm:text-3xl font-black text-slate-900 mb-2"><?php echo $plan12m['name']; ?></h3>
                        <p class="text-sm text-slate-600"><?php echo $plan12m['duration']; ?></p>
                    </div>
                    
                    <!-- Pricing -->
                    <div class="text-center mb-6">
                        <div class="text-gray-500 line-through text-xl sm:text-2xl mb-2">
                            <?php echo formatTetPrice($plan12m['original_price']); ?>
                        </div>
                        <div class="text-6xl sm:text-7xl font-black text-red-600 mb-3">
                            <?php echo formatTetPrice($plan12m['tet_price']); ?>
                        </div>
                        <div class="inline-block bg-green-100 text-green-700 px-3 sm:px-4 py-2 rounded-full text-xs sm:text-sm font-bold mb-2">
                            💰 Tiết kiệm <?php echo formatTetPrice($plan12m['save_amount']); ?> (<?php echo $plan12m['discount_percent']; ?>%)
                        </div>
                        <p class="text-sm font-bold text-slate-600 mt-1">
                            <strong>Chỉ <?php echo formatTetPrice($plan12m['per_day']); ?>/ngày</strong> - Rẻ hơn 1 ly café!
                        </p>
                    </div>
                    
                    <!-- Features -->
                    <ul class="space-y-2.5 sm:space-y-3 mb-6 sm:mb-8">
                        <?php foreach ($plan12m['features'] as $feature): ?>
                        <li class="flex items-start gap-2 sm:gap-3 text-xs sm:text-sm">
                            <i class="fa-solid fa-check-circle text-green-500 mt-0.5 flex-shrink-0 text-base sm:text-lg"></i>
                            <span class="leading-relaxed"><?php echo $feature; ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <!-- CTA -->
                    <a href="<?php echo isLoggedIn() ? 'tet-checkout.php?plan=tet_12m' : 'login.php?redirect=tet-checkout.php&plan=tet_12m'; ?>" 
                       class="block w-full bg-gradient-to-r from-yellow-500 to-orange-600 hover:from-yellow-600 hover:to-orange-700 text-white font-black px-6 sm:px-8 py-3.5 sm:py-4 rounded-xl transition shadow-lg text-center text-base sm:text-lg pulse-red touch-manipulation">
                        <?php echo $plan12m['cta']; ?>
                    </a>
                </div>
                
            </div>
            
            <!-- Trust Badge -->
            <div class="text-center">
                <p class="text-sm text-slate-600 mb-4">
                    <i class="fa-solid fa-lock mr-2 text-green-600"></i>
                    Thanh toán an toàn 100% • Kích hoạt ngay sau khi chuyển tiền
                </p>
            </div>
        </div>
    </section>

    <!-- 📚 HƯỚNG DẪN SETUP API - PROFESSIONAL VERSION -->
    <section id="setup-guide" class="py-16 sm:py-20 lg:py-24 bg-gradient-to-br from-slate-50 to-blue-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Section Header -->
            <div class="text-center mb-12 sm:mb-16 lg:mb-20">
                <div class="inline-flex items-center gap-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white px-5 py-2.5 rounded-full text-sm font-bold mb-6 shadow-lg">
                    <i class="fa-solid fa-graduation-cap"></i> 
                    <span>HƯỚNG DẪN CHI TIẾT</span>
                </div>
                <h2 class="text-4xl sm:text-5xl lg:text-6xl font-black text-slate-900 mb-4 sm:mb-6 leading-tight px-4">
                    🔧 Cách Lấy API Keys Miễn Phí
                </h2>
                <p class="text-base sm:text-lg lg:text-xl text-slate-600 max-w-3xl mx-auto px-4 leading-relaxed">
                    Làm theo 5 bước đơn giản để lấy <strong class="text-red-600">YouTube Data API</strong> và <strong class="text-purple-600">Gemini AI API</strong> - 100% miễn phí, không cần thẻ tín dụng!
                </p>
                
                <!-- Quick Stats -->
                <div class="flex flex-wrap items-center justify-center gap-4 sm:gap-6 mt-8 px-4">
                    <div class="bg-white rounded-xl px-4 sm:px-6 py-3 shadow-md">
                        <div class="text-2xl sm:text-3xl font-black text-red-600">10,000</div>
                        <div class="text-xs sm:text-sm text-slate-600">requests/ngày YouTube</div>
                    </div>
                    <div class="bg-white rounded-xl px-4 sm:px-6 py-3 shadow-md">
                        <div class="text-2xl sm:text-3xl font-black text-purple-600">60</div>
                        <div class="text-xs sm:text-sm text-slate-600">requests/phút Gemini</div>
                    </div>
                    <div class="bg-white rounded-xl px-4 sm:px-6 py-3 shadow-md">
                        <div class="text-2xl sm:text-3xl font-black text-green-600">5 phút</div>
                        <div class="text-xs sm:text-sm text-slate-600">để hoàn tất setup</div>
                    </div>
                </div>
            </div>

            <!-- Guide Cards -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8 lg:gap-10 mb-12 sm:mb-16">
                
                <!-- YouTube API Guide - Enhanced -->
                <div class="bg-white rounded-2xl sm:rounded-3xl p-6 sm:p-8 lg:p-10 border-2 border-red-100 shadow-xl hover:shadow-2xl transition-shadow duration-300">
                    <!-- Card Header -->
                    <div class="flex items-start gap-4 mb-6 sm:mb-8 pb-6 border-b-2 border-red-100">
                        <div class="w-14 h-14 sm:w-16 sm:h-16 bg-gradient-to-br from-red-600 to-red-700 rounded-2xl flex items-center justify-center shadow-lg flex-shrink-0">
                            <i class="fa-brands fa-youtube text-3xl sm:text-4xl text-white"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-2xl sm:text-3xl font-black text-slate-900 mb-2">YouTube Data API v3</h3>
                            <div class="flex items-center gap-2 text-sm sm:text-base">
                                <span class="inline-block bg-green-100 text-green-700 px-3 py-1 rounded-full font-bold text-xs sm:text-sm">
                                    <i class="fa-solid fa-check-circle mr-1"></i>100% Miễn Phí
                                </span>
                                <span class="text-slate-600">10,000 requests/ngày</span>
                            </div>
                        </div>
                    </div>

                    <!-- Steps -->
                    <div class="space-y-5 sm:space-y-6">
                        <!-- Step 1 -->
                        <div class="flex gap-4">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-red-600 to-red-700 text-white rounded-xl flex items-center justify-center font-black text-base sm:text-lg shadow-md">
                                    1
                                </div>
                            </div>
                            <div class="flex-1 pt-1">
                                <h4 class="font-black text-slate-900 mb-2 text-base sm:text-lg">Truy cập Google Cloud Console</h4>
                                <p class="text-sm sm:text-base text-slate-600 mb-3 leading-relaxed">
                                    Mở trình duyệt và vào trang quản lý API của Google
                                </p>
                                <a href="https://console.cloud.google.com" target="_blank" 
                                   class="inline-flex items-center gap-2 bg-red-50 hover:bg-red-100 text-red-700 px-4 py-2.5 rounded-lg transition text-sm sm:text-base font-semibold group">
                                    <i class="fa-solid fa-external-link-alt group-hover:translate-x-1 transition-transform"></i>
                                    <span>Mở Google Cloud Console</span>
                                </a>
                            </div>
                        </div>

                        <!-- Step 2 -->
                        <div class="flex gap-4">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-red-600 to-red-700 text-white rounded-xl flex items-center justify-center font-black text-base sm:text-lg shadow-md">
                                    2
                                </div>
                            </div>
                            <div class="flex-1 pt-1">
                                <h4 class="font-black text-slate-900 mb-2 text-base sm:text-lg">Tạo Project Mới</h4>
                                <p class="text-sm sm:text-base text-slate-600 mb-3 leading-relaxed">
                                    Click <strong class="text-slate-900">"New Project"</strong> ở góc trên bên trái
                                </p>
                                <div class="bg-slate-50 border-2 border-slate-200 rounded-lg p-3 sm:p-4">
                                    <div class="flex items-start gap-2 mb-2">
                                        <i class="fa-solid fa-lightbulb text-yellow-500 mt-0.5"></i>
                                        <div>
                                            <p class="text-xs sm:text-sm font-bold text-slate-700 mb-1">Gợi ý đặt tên:</p>
                                            <code class="text-xs sm:text-sm bg-white px-2 py-1 rounded border border-slate-300">YouTube Scanner HSHOP</code>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3 -->
                        <div class="flex gap-4">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-red-600 to-red-700 text-white rounded-xl flex items-center justify-center font-black text-base sm:text-lg shadow-md">
                                    3
                                </div>
                            </div>
                            <div class="flex-1 pt-1">
                                <h4 class="font-black text-slate-900 mb-2 text-base sm:text-lg">Enable YouTube Data API v3</h4>
                                <ol class="text-sm sm:text-base text-slate-600 space-y-2 list-inside leading-relaxed">
                                    <li class="flex items-start gap-2">
                                        <span class="text-red-600 font-bold flex-shrink-0">→</span>
                                        <span>Vào menu <strong class="text-slate-900">"APIs & Services"</strong></span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-red-600 font-bold flex-shrink-0">→</span>
                                        <span>Click <strong class="text-slate-900">"Enable APIs and Services"</strong></span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-red-600 font-bold flex-shrink-0">→</span>
                                        <span>Tìm kiếm <code class="bg-red-50 px-2 py-0.5 rounded text-red-700 font-semibold">YouTube Data API v3</code></span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-red-600 font-bold flex-shrink-0">→</span>
                                        <span>Click nút <strong class="text-blue-600">"Enable"</strong></span>
                                    </li>
                                </ol>
                            </div>
                        </div>

                        <!-- Step 4 -->
                        <div class="flex gap-4">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-red-600 to-red-700 text-white rounded-xl flex items-center justify-center font-black text-base sm:text-lg shadow-md">
                                    4
                                </div>
                            </div>
                            <div class="flex-1 pt-1">
                                <h4 class="font-black text-slate-900 mb-2 text-base sm:text-lg">Tạo API Key</h4>
                                <ol class="text-sm sm:text-base text-slate-600 space-y-2 list-inside mb-3 leading-relaxed">
                                    <li class="flex items-start gap-2">
                                        <span class="text-red-600 font-bold flex-shrink-0">→</span>
                                        <span>Vào <strong class="text-slate-900">"Credentials"</strong> trong menu bên trái</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-red-600 font-bold flex-shrink-0">→</span>
                                        <span>Click <strong class="text-slate-900">"+ Create Credentials"</strong></span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-red-600 font-bold flex-shrink-0">→</span>
                                        <span>Chọn <strong class="text-slate-900">"API Key"</strong></span>
                                    </li>
                                </ol>
                                <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-300 rounded-lg p-3 sm:p-4">
                                    <div class="flex items-start gap-2">
                                        <i class="fa-solid fa-check-circle text-green-600 text-xl mt-0.5"></i>
                                        <div>
                                            <p class="text-sm sm:text-base font-bold text-green-900 mb-1">API Key đã được tạo!</p>
                                            <p class="text-xs sm:text-sm text-green-700">
                                                Copy ngay API key (format: <code class="bg-white px-2 py-0.5 rounded text-xs">AIzaSyA...</code>)
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 5 -->
                        <div class="flex gap-4">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-red-600 to-red-700 text-white rounded-xl flex items-center justify-center font-black text-base sm:text-lg shadow-md">
                                    5
                                </div>
                            </div>
                            <div class="flex-1 pt-1">
                                <h4 class="font-black text-slate-900 mb-2 text-base sm:text-lg">Dán vào Scanner</h4>
                                <ol class="text-sm sm:text-base text-slate-600 space-y-2 list-inside leading-relaxed">
                                    <li class="flex items-start gap-2">
                                        <span class="text-red-600 font-bold flex-shrink-0">→</span>
                                        <span>Vào trang <strong class="text-slate-900">Scanner</strong></span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-red-600 font-bold flex-shrink-0">→</span>
                                        <span>Click icon <strong class="text-slate-900">⚙️ Settings</strong></span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-red-600 font-bold flex-shrink-0">→</span>
                                        <span>Dán YouTube API key vào ô <strong class="text-slate-900">"YouTube API Key"</strong></span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-red-600 font-bold flex-shrink-0">→</span>
                                        <span>Click <strong class="text-blue-600">"Lưu"</strong> → Hoàn tất! 🎉</span>
                                    </li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <!-- Important Note -->
                    <div class="mt-6 sm:mt-8 bg-gradient-to-r from-yellow-50 to-orange-50 border-2 border-yellow-300 rounded-xl p-4 sm:p-5">
                        <div class="flex gap-3">
                            <i class="fa-solid fa-exclamation-triangle text-yellow-600 text-xl flex-shrink-0 mt-0.5"></i>
                            <div>
                                <h5 class="font-bold text-yellow-900 mb-2 text-sm sm:text-base">⚡ Lưu Ý Quan Trọng:</h5>
                                <ul class="text-xs sm:text-sm text-yellow-800 space-y-1 leading-relaxed">
                                    <li>• API key hoàn toàn <strong>MIỄN PHÍ</strong>, không cần thẻ tín dụng</li>
                                    <li>• Giới hạn: <strong>10,000 requests/ngày</strong> (đủ cho 100+ lần tìm kiếm)</li>
                                    <li>• Nếu hết quota, thêm API key thứ 2 trong Settings</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gemini API Guide - Enhanced -->
                <div class="bg-white rounded-2xl sm:rounded-3xl p-6 sm:p-8 lg:p-10 border-2 border-purple-100 shadow-xl hover:shadow-2xl transition-shadow duration-300">
                    <!-- Card Header -->
                    <div class="flex items-start gap-4 mb-6 sm:mb-8 pb-6 border-b-2 border-purple-100">
                        <div class="w-14 h-14 sm:w-16 sm:h-16 bg-gradient-to-br from-purple-600 to-pink-600 rounded-2xl flex items-center justify-center shadow-lg flex-shrink-0">
                            <i class="fa-solid fa-brain text-3xl sm:text-4xl text-white"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-2xl sm:text-3xl font-black text-slate-900 mb-2">Gemini AI API</h3>
                            <div class="flex flex-wrap items-center gap-2 text-sm sm:text-base">
                                <span class="inline-block bg-purple-100 text-purple-700 px-3 py-1 rounded-full font-bold text-xs sm:text-sm">
                                    <i class="fa-solid fa-crown mr-1"></i>Premium Feature
                                </span>
                                <span class="text-slate-600">60 requests/phút</span>
                            </div>
                        </div>
                    </div>

                    <!-- Steps -->
                    <div class="space-y-5 sm:space-y-6">
                        <!-- Step 1 -->
                        <div class="flex gap-4">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-purple-600 to-pink-600 text-white rounded-xl flex items-center justify-center font-black text-base sm:text-lg shadow-md">
                                    1
                                </div>
                            </div>
                            <div class="flex-1 pt-1">
                                <h4 class="font-black text-slate-900 mb-2 text-base sm:text-lg">Truy cập Google AI Studio</h4>
                                <p class="text-sm sm:text-base text-slate-600 mb-3 leading-relaxed">
                                    Mở trình duyệt và vào trang lấy API key của Gemini AI
                                </p>
                                <a href="https://aistudio.google.com/app/apikey" target="_blank" 
                                   class="inline-flex items-center gap-2 bg-purple-50 hover:bg-purple-100 text-purple-700 px-4 py-2.5 rounded-lg transition text-sm sm:text-base font-semibold group">
                                    <i class="fa-solid fa-external-link-alt group-hover:translate-x-1 transition-transform"></i>
                                    <span>Mở Google AI Studio</span>
                                </a>
                            </div>
                        </div>

                        <!-- Step 2 -->
                        <div class="flex gap-4">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-purple-600 to-pink-600 text-white rounded-xl flex items-center justify-center font-black text-base sm:text-lg shadow-md">
                                    2
                                </div>
                            </div>
                            <div class="flex-1 pt-1">
                                <h4 class="font-black text-slate-900 mb-2 text-base sm:text-lg">Đăng nhập tài khoản Google</h4>
                                <p class="text-sm sm:text-base text-slate-600 mb-3 leading-relaxed">
                                    Sử dụng Gmail của bạn để đăng nhập vào AI Studio
                                </p>
                                <div class="bg-slate-50 border-2 border-slate-200 rounded-lg p-3 sm:p-4">
                                    <div class="flex items-start gap-2">
                                        <i class="fa-solid fa-info-circle text-blue-500 mt-0.5"></i>
                                        <p class="text-xs sm:text-sm text-slate-700">
                                            <strong>Không cần thẻ tín dụng!</strong> Chỉ cần tài khoản Gmail thông thường
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3 -->
                        <div class="flex gap-4">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-purple-600 to-pink-600 text-white rounded-xl flex items-center justify-center font-black text-base sm:text-lg shadow-md">
                                    3
                                </div>
                            </div>
                            <div class="flex-1 pt-1">
                                <h4 class="font-black text-slate-900 mb-2 text-base sm:text-lg">Tạo API Key</h4>
                                <ol class="text-sm sm:text-base text-slate-600 space-y-2 list-inside leading-relaxed">
                                    <li class="flex items-start gap-2">
                                        <span class="text-purple-600 font-bold flex-shrink-0">→</span>
                                        <span>Click nút <strong class="text-slate-900">"Create API Key"</strong></span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-purple-600 font-bold flex-shrink-0">→</span>
                                        <span>Chọn project có sẵn hoặc tạo mới</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-purple-600 font-bold flex-shrink-0">→</span>
                                        <span>API key sẽ được generate tự động</span>
                                    </li>
                                </ol>
                                <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-300 rounded-lg p-3 sm:p-4 mt-3">
                                    <div class="flex items-start gap-2">
                                        <i class="fa-solid fa-check-circle text-green-600 text-xl mt-0.5"></i>
                                        <div>
                                            <p class="text-sm sm:text-base font-bold text-green-900 mb-1">API Key đã sẵn sàng!</p>
                                            <p class="text-xs sm:text-sm text-green-700">
                                                Copy ngay key (format: <code class="bg-white px-2 py-0.5 rounded text-xs">AIzaSyB...</code>)
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4 -->
                        <div class="flex gap-4">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-purple-600 to-pink-600 text-white rounded-xl flex items-center justify-center font-black text-base sm:text-lg shadow-md">
                                    4
                                </div>
                            </div>
                            <div class="flex-1 pt-1">
                                <h4 class="font-black text-slate-900 mb-2 text-base sm:text-lg">Dán vào Scanner</h4>
                                <ol class="text-sm sm:text-base text-slate-600 space-y-2 list-inside leading-relaxed">
                                    <li class="flex items-start gap-2">
                                        <span class="text-purple-600 font-bold flex-shrink-0">→</span>
                                        <span>Vào trang <strong class="text-slate-900">Scanner</strong></span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-purple-600 font-bold flex-shrink-0">→</span>
                                        <span>Click <strong class="text-slate-900">⚙️ Settings</strong></span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-purple-600 font-bold flex-shrink-0">→</span>
                                        <span>Dán Gemini API key vào ô <strong class="text-slate-900">"Gemini API Key"</strong></span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-purple-600 font-bold flex-shrink-0">→</span>
                                        <span>Click <strong class="text-blue-600">"Lưu"</strong></span>
                                    </li>
                                </ol>
                            </div>
                        </div>

                        <!-- Step 5 -->
                        <div class="flex gap-4">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-purple-600 to-pink-600 text-white rounded-xl flex items-center justify-center font-black text-base sm:text-lg shadow-md">
                                    5
                                </div>
                            </div>
                            <div class="flex-1 pt-1">
                                <h4 class="font-black text-slate-900 mb-2 text-base sm:text-lg">Sử dụng AI Deep Dive</h4>
                                <p class="text-sm sm:text-base text-slate-600 mb-3 leading-relaxed">
                                    Sau khi search channel, click nút <strong class="text-purple-600">"🤖 AI Deep Dive"</strong> để phân tích chuyên sâu
                                </p>
                                <div class="bg-purple-50 border-2 border-purple-200 rounded-lg p-3 sm:p-4">
                                    <div class="flex items-start gap-2">
                                        <i class="fa-solid fa-magic text-purple-600 mt-0.5"></i>
                                        <p class="text-xs sm:text-sm text-purple-900">
                                            <strong>AI sẽ phân tích:</strong> Niche trends, competitor insights, content strategy, monetization potential
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Important Note -->
                    <div class="mt-6 sm:mt-8 bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-300 rounded-xl p-4 sm:p-5">
                        <div class="flex gap-3">
                            <i class="fa-solid fa-gift text-blue-600 text-xl flex-shrink-0 mt-0.5"></i>
                            <div>
                                <h5 class="font-bold text-blue-900 mb-2 text-sm sm:text-base">🎁 Bonus Tips:</h5>
                                <ul class="text-xs sm:text-sm text-blue-800 space-y-1 leading-relaxed">
                                    <li>• Gemini API có <strong>60 requests/phút</strong> miễn phí</li>
                                    <li>• Hết quota? Thêm nhiều keys trong Settings để rotate tự động</li>
                                    <li>• AI Deep Dive chỉ khả dụng cho gói <strong class="text-purple-600">Premium/VIP</strong></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Help Center -->
            <div class="mt-12 sm:mt-16 lg:mt-20">
                <div class="bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 rounded-2xl sm:rounded-3xl p-6 sm:p-8 lg:p-12 text-center shadow-2xl relative overflow-hidden">
                    <!-- Decorative Background -->
                    <div class="absolute inset-0 bg-black/10"></div>
                    <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -translate-y-32 translate-x-32"></div>
                    <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/10 rounded-full translate-y-24 -translate-x-24"></div>
                    
                    <div class="relative max-w-3xl mx-auto">
                        <div class="w-16 h-16 sm:w-20 sm:h-20 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center mx-auto mb-4 sm:mb-6 shadow-lg">
                            <i class="fa-solid fa-life-ring text-4xl sm:text-5xl text-white"></i>
                        </div>
                        
                        <h3 class="text-2xl sm:text-3xl lg:text-4xl font-black text-white mb-3 sm:mb-4 leading-tight">
                            Cần Hỗ Trợ Thêm?
                        </h3>
                        
                        <p class="text-base sm:text-lg lg:text-xl text-white/90 mb-6 sm:mb-8 leading-relaxed px-4">
                            Đội ngũ support HSHOP sẵn sàng hỗ trợ 1-1 để bạn setup thành công 100%
                        </p>
                        
                        <div class="flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-4 px-4">
                            <a href="#" 
                               class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-white hover:bg-gray-50 text-purple-600 font-bold px-6 sm:px-8 py-3 sm:py-4 rounded-xl transition shadow-lg text-sm sm:text-base group">
                                <i class="fa-brands fa-youtube text-xl group-hover:scale-110 transition-transform"></i>
                                <span>Xem Video Hướng Dẫn</span>
                            </a>
                            
                            <a href="https://zalo.me/<?php echo str_replace([' ', '-'], '', SUPPORT_HOTLINE); ?>" 
                               target="_blank"
                               class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white font-bold px-6 sm:px-8 py-3 sm:py-4 rounded-xl transition text-sm sm:text-base group border-2 border-white/30">
                                <i class="fa-solid fa-phone text-xl group-hover:rotate-12 transition-transform"></i>
                                <span><?php echo SUPPORT_HOTLINE; ?></span>
                            </a>
                        </div>
                        
                        <!-- Trust Indicators -->
                        <div class="mt-6 sm:mt-8 flex flex-wrap items-center justify-center gap-4 sm:gap-6 text-white/80 text-xs sm:text-sm">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-headset"></i>
                                <span>Support 24/7</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-clock"></i>
                                <span>Phản hồi < 2h</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-star"></i>
                                <span>4.9/5 đánh giá</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Common Issues FAQ -->
            <div class="mt-12 sm:mt-16 lg:mt-20 max-w-4xl mx-auto">
                <div class="text-center mb-8 sm:mb-12">
                    <h3 class="text-2xl sm:text-3xl lg:text-4xl font-black text-slate-900 mb-3 sm:mb-4">
                        ❓ Câu Hỏi Thường Gặp
                    </h3>
                    <p class="text-sm sm:text-base text-slate-600">
                        Giải đáp nhanh các thắc mắc phổ biến
                    </p>
                </div>

                <div class="space-y-4">
                    <!-- FAQ 1 -->
                    <details class="group bg-white rounded-xl sm:rounded-2xl shadow-md hover:shadow-lg transition-shadow">
                        <summary class="cursor-pointer p-4 sm:p-6 flex items-center justify-between font-bold text-slate-900 text-sm sm:text-base">
                            <span class="flex items-center gap-3">
                                <i class="fa-solid fa-circle-question text-blue-600 text-lg sm:text-xl"></i>
                                <span>API key có mất phí không?</span>
                            </span>
                            <i class="fa-solid fa-chevron-down group-open:rotate-180 transition-transform text-slate-400"></i>
                        </summary>
                        <div class="px-4 sm:px-6 pb-4 sm:pb-6 text-sm sm:text-base text-slate-600 leading-relaxed border-t border-slate-100 pt-4">
                            <strong class="text-green-600">Hoàn toàn MIỄN PHÍ!</strong> Cả YouTube API và Gemini API đều không yêu cầu thẻ tín dụng. 
                            Bạn có quota miễn phí: <strong>10,000 requests/ngày</strong> cho YouTube và <strong>60 requests/phút</strong> cho Gemini.
                        </div>
                    </details>

                    <!-- FAQ 2 -->
                    <details class="group bg-white rounded-xl sm:rounded-2xl shadow-md hover:shadow-lg transition-shadow">
                        <summary class="cursor-pointer p-4 sm:p-6 flex items-center justify-between font-bold text-slate-900 text-sm sm:text-base">
                            <span class="flex items-center gap-3">
                                <i class="fa-solid fa-circle-question text-blue-600 text-lg sm:text-xl"></i>
                                <span>Hết quota thì sao?</span>
                            </span>
                            <i class="fa-solid fa-chevron-down group-open:rotate-180 transition-transform text-slate-400"></i>
                        </summary>
                        <div class="px-4 sm:px-6 pb-4 sm:pb-6 text-sm sm:text-base text-slate-600 leading-relaxed border-t border-slate-100 pt-4">
                            Nếu hết quota, bạn có <strong>2 cách</strong>:<br>
                            1️⃣ <strong>Thêm API key thứ 2</strong> - Scanner sẽ tự động rotate giữa các keys<br>
                            2️⃣ <strong>Đợi reset quota</strong> - YouTube reset mỗi ngày, Gemini reset mỗi phút
                        </div>
                    </details>

                    <!-- FAQ 3 -->
                    <details class="group bg-white rounded-xl sm:rounded-2xl shadow-md hover:shadow-lg transition-shadow">
                        <summary class="cursor-pointer p-4 sm:p-6 flex items-center justify-between font-bold text-slate-900 text-sm sm:text-base">
                            <span class="flex items-center gap-3">
                                <i class="fa-solid fa-circle-question text-blue-600 text-lg sm:text-xl"></i>
                                <span>API key có an toàn không?</span>
                            </span>
                            <i class="fa-solid fa-chevron-down group-open:rotate-180 transition-transform text-slate-400"></i>
                        </summary>
                        <div class="px-4 sm:px-6 pb-4 sm:pb-6 text-sm sm:text-base text-slate-600 leading-relaxed border-t border-slate-100 pt-4">
                            <strong class="text-green-600">100% an toàn!</strong> API keys được lưu trữ <strong>mã hóa</strong> trên server. 
                            Chỉ bạn và hệ thống mới có thể sử dụng. Không ai khác có thể truy cập keys của bạn.
                        </div>
                    </details>

                    <!-- FAQ 4 -->
                    <details class="group bg-white rounded-xl sm:rounded-2xl shadow-md hover:shadow-lg transition-shadow">
                        <summary class="cursor-pointer p-4 sm:p-6 flex items-center justify-between font-bold text-slate-900 text-sm sm:text-base">
                            <span class="flex items-center gap-3">
                                <i class="fa-solid fa-circle-question text-blue-600 text-lg sm:text-xl"></i>
                                <span>Tôi không có API key, vẫn dùng được không?</span>
                            </span>
                            <i class="fa-solid fa-chevron-down group-open:rotate-180 transition-transform text-slate-400"></i>
                        </summary>
                        <div class="px-4 sm:px-6 pb-4 sm:pb-6 text-sm sm:text-base text-slate-600 leading-relaxed border-t border-slate-100 pt-4">
                            <strong class="text-blue-600">Có thể!</strong> Hệ thống có <strong>API Pool</strong> dự phòng cho user chưa có key. 
                            Tuy nhiên, để có trải nghiệm tốt nhất và không bị giới hạn, bạn nên lấy API key riêng (chỉ mất 5 phút).
                        </div>
                    </details>
                </div>
            </div>

        </div>
    </section>

    <!-- 📊 COMPARISON SECTION -->
    <section id="compare" class="py-20 bg-white">
        <div class="max-w-5xl mx-auto px-4 sm:px-6">
            <div class="text-center mb-12">
                <h2 class="text-5xl font-black text-slate-900 mb-4 font-tet">
                    📊 So Sánh Giá Tết vs Giá Thường
                </h2>
                <p class="text-xl text-slate-600">
                    Xem ngay bạn tiết kiệm được bao nhiêu!
                </p>
            </div>
            
            <div class="bg-gradient-to-br from-red-50 to-orange-50 rounded-3xl p-8 border-4 border-red-200 shadow-2xl">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b-2 border-red-300">
                            <th class="py-4 px-4 text-lg font-black">Gói</th>
                            <th class="py-4 px-4 text-lg font-black text-center">Giá Thường</th>
                            <th class="py-4 px-4 text-lg font-black text-center text-red-600">Giá Tết 🎊</th>
                            <th class="py-4 px-4 text-lg font-black text-center text-green-600">Tiết Kiệm</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-red-200">
                            <td class="py-4 px-4 font-bold">6 Tháng</td>
                            <td class="py-4 px-4 text-center line-through text-gray-500"><?php echo formatTetPrice($comparison['6m']['regular']); ?></td>
                            <td class="py-4 px-4 text-center text-2xl font-black text-red-600"><?php echo formatTetPrice($comparison['6m']['tet']); ?></td>
                            <td class="py-4 px-4 text-center text-xl font-black text-green-600"><?php echo formatTetPrice($comparison['6m']['save']); ?></td>
                        </tr>
                        <tr>
                            <td class="py-4 px-4 font-bold">12 Tháng 👑</td>
                            <td class="py-4 px-4 text-center line-through text-gray-500"><?php echo formatTetPrice($comparison['12m']['regular']); ?></td>
                            <td class="py-4 px-4 text-center text-2xl font-black text-red-600"><?php echo formatTetPrice($comparison['12m']['tet']); ?></td>
                            <td class="py-4 px-4 text-center text-xl font-black text-green-600"><?php echo formatTetPrice($comparison['12m']['save']); ?></td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="mt-8 bg-yellow-100 border-2 border-yellow-400 rounded-xl p-6 text-center">
                    <p class="text-lg font-bold text-slate-900 mb-2">
                        ⚠️ <strong>LƯU Ý:</strong> 
                    </p>
                    <p class="text-sm text-slate-700">
                        hết ưu đãi, giá sẽ quay về mức thường (cao gấp 2-3 lần)
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- 🎯 FINAL CTA -->
    <section class="py-20 gradient-tet relative">
        <div class="absolute inset-0 bg-black/10"></div>
        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 text-center">
            <h2 class="text-4xl sm:text-6xl font-black text-white mb-6 font-tet">
                🧧 Đừng Bỏ Lỡ Cơ Hội Vàng!
            </h2>
            <p class="text-2xl text-white/90 mb-8">
                Chỉ còn <strong class="text-yellow-300"><?php echo $daysRemaining; ?> ngày</strong> để nhận ưu đãi Tết đặc biệt này
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="#pricing" class="w-full sm:w-auto bg-yellow-400 hover:bg-yellow-500 text-red-900 font-black px-12 py-5 rounded-2xl transition shadow-2xl text-xl pulse-red">
                    <i class="fa-solid fa-gift mr-2"></i> Mua Ngay - Giá Sốc
                </a>
            </div>
        </div>
    </section>

    <!-- 📄 FOOTER -->
    <footer class="bg-slate-900 text-slate-300 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 text-center">
            <div class="mb-6">
                <h4 class="font-bold text-white mb-2 text-lg">HSHOP Analytics</h4>
                <p class="text-sm text-slate-400">
                    🎊 Chúc Quý Khách Năm Mới An Khang Thịnh Vượng 🎊
                </p>
            </div>
            
            <div class="border-t border-slate-800 pt-6">
                
                <p class="text-xs text-slate-500 mt-2">
                    © 2026 HSHOP Analytics • Made with ❤️ in Vietnam
                </p>
            </div>
        </div>
    </footer>

    <?php endif; ?>

    <!-- Mobile Menu Toggle Script -->
    <script>
        function toggleMobileMenu() {
            alert('Mobile menu - implement if needed');
        }
        
        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                if (href && href !== '#') {
                    const target = document.querySelector(href);
                    if (target) {
                        e.preventDefault();
                        const headerHeight = 80;
                        const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - headerHeight;
                        window.scrollTo({
                            top: targetPosition,
                            behavior: 'smooth'
                        });
                    }
                }
            });
        });
    </script>

</body>
</html>
