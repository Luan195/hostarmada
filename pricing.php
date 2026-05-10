<?php
// Proper session initialization
require_once 'includes/session.php';
require_once 'includes/functions.php';
require_once 'includes/pricing_data.php'; // ✅ CENTRALIZED PRICING DATA

// CHIẾN LƯỢC GIÁ GẠCH NGANG: Hiển thị giá gốc cao → Giá bán (tạo cảm giác deal tốt!)
// PRICING STRATEGY: Original Price (high) → Sale Price (actual)

// ✅ USE CENTRALIZED DATA INSTEAD OF HARDCODED ARRAY
$pricingPlans = getAllPricingPlans();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảng Giá - HSHOP Analytics</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
        body { 
            font-family: 'Inter', sans-serif; 
        }
        
        /* Strike-through animation */
        .price-strike {
            position: relative;
            display: inline-block;
        }
        .price-strike::after {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            height: 2px;
            background: #ef4444;
            transform: translateY(-50%) rotate(-5deg);
        }
        
        /* Pulse animation for popular badge */
        @keyframes pulse-glow {
            0%, 100% { 
                box-shadow: 0 0 20px rgba(250, 204, 21, 0.5);
                transform: scale(1);
            }
            50% { 
                box-shadow: 0 0 40px rgba(250, 204, 21, 0.8);
                transform: scale(1.02);
            }
        }
        .popular-card {
            animation: pulse-glow 3s ease-in-out infinite;
        }
        
        /* Gradient text */
        .gradient-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 to-slate-100">

    <!-- Header -->
    <header class="bg-white/80 backdrop-blur-md border-b border-slate-200 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="index.php" class="flex items-center gap-3 group">
                <div class="bg-gradient-to-br from-red-600 to-red-700 text-white p-2 rounded-lg shadow-md group-hover:scale-110 transition-transform">
                    <i class="fa-brands fa-youtube text-xl"></i>
                </div>
                <h1 class="font-extrabold text-lg tracking-tight text-slate-900">HSHOP <span class="text-red-600">Analytics</span></h1>
            </a>
            <div class="flex items-center gap-4">
                <?php if (isLoggedIn()): ?>
                    <a href="scanner.php" class="text-sm font-bold text-slate-600 hover:text-red-600 transition-colors">
                        <i class="fa-solid fa-radar mr-1"></i> Scanner
                    </a>
                    <a href="logout.php" class="text-sm font-bold text-slate-600 hover:text-red-600 transition-colors">
                        <i class="fa-solid fa-sign-out-alt mr-1"></i> Logout
                    </a>
                <?php else: ?>
                    <a href="login.php" class="text-sm font-bold text-slate-600 hover:text-red-600 transition-colors">
                        <i class="fa-solid fa-sign-in-alt mr-1"></i> Đăng Nhập
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="relative bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 text-white py-24 overflow-hidden">
        <!-- Animated background -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-10 left-10 w-72 h-72 bg-purple-500 rounded-full mix-blend-multiply filter blur-3xl animate-pulse"></div>
            <div class="absolute bottom-10 right-10 w-72 h-72 bg-red-500 rounded-full mix-blend-multiply filter blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
        </div>
        
        <div class="relative max-w-6xl mx-auto px-4 text-center">
            <div class="inline-block bg-red-500/20 border border-red-500/50 rounded-full px-6 py-2 mb-6 backdrop-blur-sm">
                <span class="text-red-400 font-bold text-sm">🔥 ƯU ĐÃI ĐẶC BIỆT - Giảm giá đến 40%!</span>
            </div>
            
            <h1 class="text-5xl md:text-7xl font-black mb-6 leading-tight">
                Bảng Giá Đặc Biệt
            </h1>
            <p class="text-xl md:text-2xl text-slate-300 mb-8 max-w-3xl mx-auto">
                Đầu tư thông minh cho kênh YouTube của bạn<br/>
                <span class="text-yellow-400 font-bold">Càng dài hạn - Càng tiết kiệm nhiều!</span>
            </p>
            
            <div class="flex flex-wrap justify-center gap-4 mb-8">
                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl px-6 py-3">
                    <i class="fa-solid fa-check text-green-400 mr-2"></i>
                    <span class="font-bold">Không giới hạn tìm kiếm</span>
                </div>
                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl px-6 py-3">
                    <i class="fa-solid fa-check text-green-400 mr-2"></i>
                    <span class="font-bold">AI phân tích chuyên sâu</span>
                </div>
                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl px-6 py-3">
                    <i class="fa-solid fa-check text-green-400 mr-2"></i>
                    <span class="font-bold">Xuất CSV miễn phí</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Cards -->
    <section class="py-20 -mt-16 relative z-10">
        <div class="max-w-7xl mx-auto px-4">
            
            <!-- Trial Card (Featured Banner) -->
            <?php if (isset($pricingPlans['trial'])): $trial = $pricingPlans['trial']; ?>
            <div class="bg-gradient-to-r from-green-600 to-emerald-600 rounded-3xl shadow-2xl p-8 mb-12 text-white relative overflow-hidden">
                <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-32 -mt-32"></div>
                <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-6">
                    <div class="flex-1 text-center md:text-left">
                        <div class="inline-block bg-yellow-400 text-green-900 px-4 py-1 rounded-full text-xs font-black mb-3">
                            <?php echo $trial['badge']; ?>
                        </div>
                        <h3 class="text-3xl md:text-4xl font-black mb-2"><?php echo $trial['name']; ?> - <?php echo $trial['duration']; ?></h3>
                        <p class="text-green-100 text-lg mb-4">Trải nghiệm đầy đủ tính năng với giá siêu rẻ!</p>
                        <div class="flex flex-wrap items-center justify-center md:justify-start gap-3">
                            <div class="bg-white/20 backdrop-blur-sm rounded-lg px-4 py-2">
                                <div class="text-xs text-green-200">Giá gốc</div>
                                <div class="text-xl font-bold line-through"><?php echo number_format($trial['original_price'], 0, ',', '.'); ?>đ</div>
                            </div>
                            <i class="fa-solid fa-arrow-right text-2xl"></i>
                            <div class="bg-white text-green-600 rounded-lg px-6 py-2 shadow-xl">
                                <div class="text-xs font-bold">Chỉ còn</div>
                                <div class="text-4xl font-black"><?php echo number_format($trial['sale_price'], 0, ',', '.'); ?>đ</div>
                            </div>
                        </div>
                    </div>
                    <div class="flex-shrink-0">
                        <a href="checkout.php?plan=trial" class="inline-block bg-white text-green-600 hover:bg-green-50 font-black px-10 py-4 rounded-2xl shadow-2xl transition-all transform hover:scale-105 text-lg">
                            <?php echo $trial['cta']; ?> <i class="fa-solid fa-rocket ml-2"></i>
                        </a>
                        <p class="text-center text-green-100 text-xs mt-2">
                            <i class="fa-solid fa-bolt"></i> Kích hoạt ngay lập tức
                        </p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Main Pricing Grid (4 cards: 1m, 3m, 6m, 12m) -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                
                <?php 
                // Skip trial, only show main plans
                $mainPlans = array_filter($pricingPlans, function($key) {
                    return $key !== 'trial';
                }, ARRAY_FILTER_USE_KEY);
                
                foreach ($mainPlans as $planKey => $plan): 
                ?>
                <div class="relative group">
                    <!-- Popular Badge (floating) -->
                    <?php if ($plan['badge']): ?>
                    <div class="absolute -top-4 left-1/2 transform -translate-x-1/2 z-10">
                        <div class="bg-gradient-to-r from-<?php echo $plan['color']; ?>-500 to-<?php echo $plan['color']; ?>-600 text-white px-4 py-1.5 rounded-full text-xs font-black shadow-2xl border-2 border-white">
                            <?php echo $plan['badge']; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Card -->
                    <div class="<?php echo $planKey === '12m' ? 'popular-card scale-105 lg:scale-110' : ''; ?> bg-white rounded-2xl shadow-xl border-2 <?php echo $planKey === '12m' ? 'border-yellow-400' : 'border-slate-200'; ?> p-6 hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 h-full flex flex-col relative">
                        
                        <!-- Discount Badge (corner) -->
                        <div class="absolute top-3 right-3">
                            <div class="bg-red-500 text-white w-14 h-14 rounded-full flex items-center justify-center shadow-lg transform rotate-12">
                                <div class="text-center">
                                    <div class="text-lg font-black leading-none">-<?php echo $plan['discount_percent']; ?>%</div>
                                    <div class="text-[7px] uppercase font-bold">OFF</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Plan Name -->
                        <div class="text-center mb-4 mt-6">
                            <h3 class="text-2xl font-black mb-1 text-slate-900"><?php echo $plan['name']; ?></h3>
                            <p class="text-slate-500 text-xs font-bold uppercase tracking-wide"><?php echo $plan['duration']; ?></p>
                        </div>
                        
                        <!-- Pricing -->
                        <div class="text-center mb-5">
                            <!-- Original Price (Strike-through) -->
                            <div class="mb-1">
                                <span class="price-strike text-lg text-slate-400 font-bold">
                                    <?php echo number_format($plan['original_price'], 0, ',', '.'); ?>đ
                                </span>
                            </div>
                            
                            <!-- Sale Price (Big & Bold) -->
                            <div class="mb-2">
                                <span class="text-4xl font-black text-<?php echo $plan['color']; ?>-600">
                                    <?php echo number_format($plan['sale_price'], 0, ',', '.'); ?>
                                </span>
                                <span class="text-xl font-bold text-slate-700">đ</span>
                            </div>
                            
                            <!-- Per Day Price -->
                            <div class="bg-<?php echo $plan['color']; ?>-50 border border-<?php echo $plan['color']; ?>-200 rounded-lg px-3 py-1.5 inline-block">
                                <span class="text-<?php echo $plan['color']; ?>-800 text-xs font-bold">
                                    ~<?php echo number_format($plan['per_day'], 0, ',', '.'); ?>đ/ngày
                                </span>
                            </div>
                            
                            <!-- Savings Amount -->
                            <?php if (isset($plan['save_amount'])): ?>
                            <div class="mt-2">
                                <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-[10px] font-black">
                                    💰 Tiết kiệm <?php echo number_format($plan['save_amount'], 0, ',', '.'); ?>đ
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Features List -->
                        <ul class="space-y-2 mb-6 flex-grow">
                            <?php 
                            // Show first 5 features for compact display
                            $displayFeatures = array_slice($plan['features'], 0, 5);
                            foreach ($displayFeatures as $feature): 
                            ?>
                            <li class="flex items-start gap-2 text-xs">
                                <div class="flex-shrink-0 w-4 h-4 bg-<?php echo $plan['color']; ?>-100 rounded-full flex items-center justify-center mt-0.5">
                                    <i class="fa-solid fa-check text-<?php echo $plan['color']; ?>-600 text-[8px]"></i>
                                </div>
                                <span class="text-slate-700 font-medium leading-tight"><?php echo $feature; ?></span>
                            </li>
                            <?php endforeach; ?>
                            
                            <?php if (count($plan['features']) > 5): ?>
                            <li class="text-center">
                                <span class="text-<?php echo $plan['color']; ?>-600 text-xs font-bold">
                                    +<?php echo count($plan['features']) - 5; ?> tính năng khác
                                </span>
                            </li>
                            <?php endif; ?>
                        </ul>
                        
                        <!-- CTA Button -->
                        <a href="checkout.php?plan=<?php echo $planKey; ?>" 
                           class="block w-full bg-gradient-to-r from-<?php echo $plan['color']; ?>-600 to-<?php echo $plan['color']; ?>-700 hover:from-<?php echo $plan['color']; ?>-700 hover:to-<?php echo $plan['color']; ?>-800 text-white font-black py-3 rounded-xl text-center transition-all transform hover:scale-105 shadow-lg hover:shadow-xl text-sm">
                            <?php echo $plan['cta']; ?> <i class="fa-solid fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>

            </div>
            
            <!-- Trust Indicators -->
            <div class="mt-16 text-center">
                <div class="flex flex-wrap justify-center gap-8 items-center text-slate-600">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-shield-check text-green-600 text-2xl"></i>
                        <span class="font-bold">Thanh toán an toàn</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-clock text-blue-600 text-2xl"></i>
                        <span class="font-bold">Kích hoạt trong 5-15 phút</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-headset text-purple-600 text-2xl"></i>
                        <span class="font-bold">Hỗ trợ 24/7</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-16 bg-slate-50">
        <div class="max-w-4xl mx-auto px-4">
            <h2 class="text-4xl font-black text-center mb-12">Câu Hỏi Thường Gặp</h2>
            <div class="space-y-4">
                <details class="bg-white p-6 rounded-2xl border-2 border-slate-200 hover:border-blue-400 transition-colors group">
                    <summary class="font-bold cursor-pointer flex items-center gap-3 text-lg">
                        <i class="fa-solid fa-credit-card text-blue-600 group-hover:scale-110 transition-transform"></i>
                        Phương thức thanh toán?
                    </summary>
                    <p class="mt-4 text-slate-600 pl-9">Chuyển khoản qua số tài khoản được hiển thị ở Checkout. Sau khi chuyển khoản, hệ thống sẽ tự động kích hoạt tài khoản khi phát hiện giao dịch.</p>
                
                <details class="bg-white p-6 rounded-2xl border-2 border-slate-200 hover:border-green-400 transition-colors group">
                    <summary class="font-bold cursor-pointer flex items-center gap-3 text-lg">
                        <i class="fa-solid fa-clock text-green-600 group-hover:scale-110 transition-transform"></i>
                        Bao lâu được kích hoạt?
                    </summary>
                    <p class="mt-4 text-slate-600 pl-9">Thường trong vòng <strong>5-15 phút</strong> sau khi chuyển khoản. Hệ thống tự động phát hiện và kích hoạt ngay lập tức.</p>
                </details>
                
               
                
                <details class="bg-white p-6 rounded-2xl border-2 border-slate-200 hover:border-red-400 transition-colors group">
                    <summary class="font-bold cursor-pointer flex items-center gap-3 text-lg">
                        <i class="fa-solid fa-shield-check text-red-600 group-hover:scale-110 transition-transform"></i>
                        Có an toàn không?
                    </summary>
                    <p class="mt-4 text-slate-600 pl-9">Hoàn toàn an toàn! Chúng tôi là <strong>doanh nghiệp đã đăng ký kinh doanh chính thức</strong> với mã số thuế <strong>0319057106</strong>. Thông tin công ty minh bạch ở phía dưới.</p>
                </details>
            </div>
        </div>
    </section>

    <!-- CTA Final -->
    <section class="py-20 bg-gradient-to-r from-red-600 via-purple-600 to-indigo-600 text-white text-center relative overflow-hidden">
        <!-- Animated background -->
        <div class="absolute inset-0 opacity-20">
            <div class="absolute top-0 left-1/4 w-96 h-96 bg-yellow-400 rounded-full mix-blend-multiply filter blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-pink-400 rounded-full mix-blend-multiply filter blur-3xl animate-pulse" style="animation-delay: 1.5s;"></div>
        </div>
        
        <div class="relative max-w-4xl mx-auto px-4">
            <h2 class="text-4xl md:text-5xl font-black mb-4">Sẵn Sàng Tăng Tốc Kênh YouTube? 🚀</h2>
            <p class="text-xl md:text-2xl mb-8">Hàng nghìn creator đã tin dùng công cụ của chúng tôi</p>
            
            <div class="flex flex-wrap justify-center gap-4 mb-8">
                <div class="bg-white/20 backdrop-blur-sm rounded-xl px-6 py-3">
                    <div class="text-3xl font-black">1,500+</div>
                    <div class="text-sm">Creator đang dùng</div>
                </div>
                <div class="bg-white/20 backdrop-blur-sm rounded-xl px-6 py-3">
                    <div class="text-3xl font-black">50,000+</div>
                    <div class="text-sm">Video đã phân tích</div>
                </div>
                <div class="bg-white/20 backdrop-blur-sm rounded-xl px-6 py-3">
                    <div class="text-3xl font-black">4.9/5</div>
                    <div class="text-sm">Đánh giá trung bình</div>
                </div>
            </div>
            
            <a href="checkout.php?plan=12m" class="inline-block bg-white text-red-600 font-black px-12 py-5 rounded-2xl shadow-2xl hover:shadow-3xl transition-all transform hover:scale-105 text-lg">
                Đăng Ký Ngay - Tiết Kiệm 40% <i class="fa-solid fa-arrow-right ml-2"></i>
            </a>
            
            <p class="mt-6 text-sm text-white/80">
                <i class="fa-solid fa-lock mr-1"></i> Thanh toán an toàn 100% • Kích hoạt tức thì • Bảo mật dữ liệu tuyệt đối
            </p>
        </div>
    </section>

    <!-- Company Info Footer (Professional & Legal) -->
    <footer class="bg-slate-900 text-slate-400 py-12 border-t border-slate-800">
        <div class="max-w-6xl mx-auto px-4">
            
            <!-- Main Company Info -->
            <div class="bg-slate-800/50 border border-slate-700 rounded-2xl p-8 mb-8">
                <div class="flex items-start gap-4 mb-6">
                    <!-- <div class="bg-red-600 p-4 rounded-xl">
                        <i class="fa-solid fa-building text-3xl text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-black text-white mb-2">CÔNG TY TNHH HSHOP MEDIA VIỆT NAM</h3>
                        <div class="space-y-2 text-sm">
                            <p>
                                <i class="fa-solid fa-certificate text-yellow-500 mr-2"></i>
                                <strong class="text-white">Mã số thuế:</strong> 0319057106
                            </p>
                            <p>
                                <i class="fa-solid fa-location-dot text-blue-400 mr-2"></i>
                                <strong class="text-white">Địa chỉ trụ sở:</strong> 118 Đường B, Phường Hiệp Bình, Quận Thủ Đức, Thành phố Hồ Chí Minh, Việt Nam
                            </p>
                            <p>
                                <i class="fa-solid fa-user-tie text-green-400 mr-2"></i>
                                <strong class="text-white">Người đại diện pháp luật:</strong> Nguyễn Văn Thạch Duy
                            </p>
                            <p>
                                <i class="fa-solid fa-check-circle text-green-500 mr-2"></i>
                                <strong class="text-white">Tình trạng:</strong> <span class="text-green-400">Đang hoạt động</span>
                            </p>
                        </div>
                    </div> -->
                </div>
                
                <div class="border-t border-slate-700 pt-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <h4 class="font-bold text-white mb-3 flex items-center gap-2">
                            <i class="fa-solid fa-phone text-red-500"></i> Liên Hệ
                        </h4>
                        <p class="text-sm">
                            Hotline: <a href="tel:<?php echo SUPPORT_HOTLINE; ?>" class="text-red-400 hover:text-red-300 font-bold"><?php echo SUPPORT_HOTLINE; ?></a><br/>
                            Email: <a href="mailto:<?php echo SUPPORT_EMAIL; ?>" class="text-blue-400 hover:text-blue-300">support@HSHOP.com</a>
                        </p>
                    </div>
                    
                    <div>
                        <h4 class="font-bold text-white mb-3 flex items-center gap-2">
                            <i class="fa-solid fa-clock text-yellow-500"></i> Giờ Làm Việc
                        </h4>
                        <p class="text-sm">
                            Thứ 2 - Thứ 6: 8:00 - 17:30<br/>
                            Thứ 7 - CN: 9:00 - 16:00
                        </p>
                    </div>
                    
                    <div>
                        <h4 class="font-bold text-white mb-3 flex items-center gap-2">
                            <i class="fa-solid fa-shield-check text-green-500"></i> Bảo Mật
                        </h4>
                        <p class="text-sm">
                            Doanh nghiệp đã đăng ký hợp pháp<br/>
                            Thông tin khách hàng được bảo mật 100%
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Bottom Bar -->
            <div class="text-center border-t border-slate-800 pt-8">
                <p class="mb-3">
                    © <?php echo date('Y'); ?> <strong class="text-white">HSHOP Media Việt Nam</strong>. All rights reserved.
                </p>
                <p class="text-xs text-slate-500">
                    Phần mềm phân tích YouTube chuyên nghiệp dành cho Creator • Made with ❤️ in Vietnam
                </p>
            </div>
        </div>
    </footer>

</body>
</html>
