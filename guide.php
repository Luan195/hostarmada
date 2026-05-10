<?php
// 📚 TRANG HƯỚNG DẪN SETUP API KEYS - HSHOP ANALYTICS
require_once 'includes/session.php';
require_once 'includes/functions.php';

$pageTitle = "Hướng Dẫn Setup API Keys";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📚 <?php echo $pageTitle; ?> | HSHOP Analytics</title>
    <meta name="description" content="Hướng dẫn chi tiết cách lấy YouTube Data API và Gemini AI API miễn phí trong 5 phút. Không cần thẻ tín dụng!">
    
    <!-- CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
        
        * { 
            font-family: 'Inter', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }
        
        html {
            scroll-behavior: smooth;
        }
        
        /* Smooth transitions */
        button, a, summary, .guide-card {
            transition: all 0.3s ease;
        }
        
        /* Guide Card Hover */
        .guide-card {
            background: white;
            border: 2px solid #e5e7eb;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .guide-card:hover {
            border-color: #3b82f6;
            box-shadow: 0 12px 24px rgba(59, 130, 246, 0.15);
            transform: translateY(-4px);
        }
        
        /* Better touch targets for mobile */
        @media (hover: none) and (pointer: coarse) {
            button, a, summary {
                min-height: 44px;
                min-width: 44px;
            }
        }
        
        /* Step number animation */
        .step-number {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            transition: all 0.3s ease;
        }
        
        .step-number:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 16px rgba(59, 130, 246, 0.3);
        }
        
        /* Code block styling */
        .code-block {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-family: 'Monaco', 'Courier New', monospace;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 min-h-screen">

    <!-- 📌 HEADER -->
    <header class="bg-white/90 backdrop-blur-md border-b-2 border-blue-600 sticky top-0 z-50 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="flex items-center justify-between h-16 sm:h-20">
                <!-- Logo -->
                <a href="index.php" class="flex items-center gap-2 sm:gap-3">
                    <div class="bg-gradient-to-br from-blue-600 to-blue-700 text-white p-2 sm:p-3 rounded-xl shadow-lg">
                        <i class="fa-brands fa-youtube text-2xl sm:text-3xl"></i>
                    </div>
                    <div>
                        <h1 class="text-lg sm:text-2xl font-black text-slate-900">
                            HSHOP <span class="text-blue-600">Analytics</span>
                        </h1>
                        <p class="text-xs text-blue-600 font-bold hidden sm:block">📚 Hướng Dẫn Setup API</p>
                    </div>
                </a>
                
                <!-- Desktop Nav -->
                <nav class="hidden md:flex items-center gap-4">
                    <a href="index.php" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition">
                        <i class="fa-solid fa-home mr-1"></i> Trang Chủ
                    </a>
                    <a href="pricing.php" class="text-sm font-medium text-slate-600 hover:text-red-600 transition">
                        <i class="fa-solid fa-tags mr-1"></i> Bảng Giá
                    </a>
                    <?php if (isLoggedIn()): ?>
                        <a href="scanner.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-5 py-2.5 rounded-lg transition text-sm shadow-lg">
                            <i class="fa-solid fa-compass mr-1"></i> Mở Scanner
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition">
                            Đăng nhập
                        </a>
                        <a href="login.php?mode=register" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-5 py-2.5 rounded-lg transition text-sm shadow-lg">
                            Đăng ký ngay
                        </a>
                    <?php endif; ?>
                </nav>
                
                <!-- Mobile Menu Button -->
                <button onclick="window.location.href='index.php'" class="md:hidden text-blue-600 p-2">
                    <i class="fa-solid fa-home text-2xl"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- 🎯 HERO SECTION -->
    <section class="py-12 sm:py-16 lg:py-20 bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 text-white relative overflow-hidden">
        <!-- Decorative Background -->
        <div class="absolute inset-0 bg-black/10"></div>
        <div class="absolute top-0 right-0 w-96 h-96 bg-white/10 rounded-full -translate-y-48 translate-x-48"></div>
        <div class="absolute bottom-0 left-0 w-64 h-64 bg-white/10 rounded-full translate-y-32 -translate-x-32"></div>
        
        <div class="relative max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <!-- Icon -->
            <div class="w-20 h-20 sm:w-24 sm:h-24 bg-white/20 backdrop-blur-sm rounded-3xl flex items-center justify-center mx-auto mb-6 sm:mb-8 shadow-2xl">
                <i class="fa-solid fa-graduation-cap text-5xl sm:text-6xl text-white"></i>
            </div>
            
            <!-- Title -->
            <h1 class="text-3xl sm:text-4xl lg:text-6xl font-black mb-4 sm:mb-6 leading-tight">
                📚 Hướng Dẫn Lấy API Keys
            </h1>
            
            <p class="text-lg sm:text-xl lg:text-2xl mb-6 sm:mb-8 max-w-3xl mx-auto leading-relaxed text-white/90">
                Làm theo <strong class="text-yellow-300">5 bước đơn giản</strong> để lấy YouTube Data API và Gemini AI API - 
                <strong class="text-yellow-300">100% miễn phí</strong>, không cần thẻ tín dụng!
            </p>
            
            <!-- Quick Stats -->
            <div class="flex flex-wrap items-center justify-center gap-4 sm:gap-6 mb-8 sm:mb-12">
                <div class="bg-white/20 backdrop-blur-sm rounded-2xl px-6 sm:px-8 py-4 sm:py-5 shadow-xl border border-white/30">
                    <div class="text-3xl sm:text-4xl font-black text-yellow-300">10,000</div>
                    <div class="text-xs sm:text-sm text-white/90 font-semibold">requests/ngày YouTube</div>
                </div>
                <div class="bg-white/20 backdrop-blur-sm rounded-2xl px-6 sm:px-8 py-4 sm:py-5 shadow-xl border border-white/30">
                    <div class="text-3xl sm:text-4xl font-black text-yellow-300">60</div>
                    <div class="text-xs sm:text-sm text-white/90 font-semibold">requests/phút Gemini</div>
                </div>
                <div class="bg-white/20 backdrop-blur-sm rounded-2xl px-6 sm:px-8 py-4 sm:py-5 shadow-xl border border-white/30">
                    <div class="text-3xl sm:text-4xl font-black text-yellow-300">5 phút</div>
                    <div class="text-xs sm:text-sm text-white/90 font-semibold">để hoàn tất setup</div>
                </div>
            </div>
            
            <!-- CTA Buttons -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-4">
                <a href="#youtube-guide" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-white hover:bg-gray-50 text-blue-600 font-bold px-8 py-4 rounded-xl transition shadow-2xl text-base sm:text-lg group">
                    <i class="fa-brands fa-youtube text-xl group-hover:scale-110 transition-transform"></i>
                    <span>YouTube API Guide</span>
                </a>
                <a href="#gemini-guide" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white font-bold px-8 py-4 rounded-xl transition text-base sm:text-lg group border-2 border-white/30">
                    <i class="fa-solid fa-brain text-xl group-hover:scale-110 transition-transform"></i>
                    <span>Gemini API Guide</span>
                </a>
            </div>
        </div>
    </section>

    <!-- 📺 YOUTUBE API GUIDE -->
    <section id="youtube-guide" class="py-16 sm:py-20 lg:py-24">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Section Header -->
            <div class="text-center mb-12 sm:mb-16">
                <div class="inline-flex items-center gap-2 bg-red-100 text-red-700 px-5 py-2.5 rounded-full text-sm font-bold mb-6 shadow-md">
                    <i class="fa-brands fa-youtube"></i>
                    <span>MIỄN PHÍ 10,000 REQUESTS/NGÀY</span>
                </div>
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black text-slate-900 mb-4 sm:mb-6 leading-tight">
                    YouTube Data API v3
                </h2>
                <p class="text-base sm:text-lg text-slate-600 max-w-2xl mx-auto leading-relaxed">
                    Làm theo 5 bước để lấy API key cho Scanner - Không mất phí, không cần thẻ tín dụng
                </p>
            </div>

            <!-- Guide Steps -->
            <div class="space-y-6 sm:space-y-8">
                
                <!-- Step 1 -->
                <div class="guide-card rounded-2xl sm:rounded-3xl p-6 sm:p-8">
                    <div class="flex gap-4 sm:gap-6">
                        <div class="flex-shrink-0">
                            <div class="step-number w-12 h-12 sm:w-14 sm:h-14 rounded-xl flex items-center justify-center text-white font-black text-xl sm:text-2xl shadow-lg">
                                1
                            </div>
                        </div>
                        <div class="flex-1 pt-1">
                            <h3 class="text-xl sm:text-2xl font-black text-slate-900 mb-3 sm:mb-4">Truy cập Google Cloud Console</h3>
                            <p class="text-sm sm:text-base text-slate-600 mb-4 leading-relaxed">
                                Mở trình duyệt và vào trang quản lý API của Google
                            </p>
                            <a href="https://console.cloud.google.com" target="_blank" 
                               class="inline-flex items-center gap-2 bg-blue-50 hover:bg-blue-100 text-blue-700 px-5 py-3 rounded-lg transition text-sm sm:text-base font-bold group shadow-sm">
                                <i class="fa-solid fa-external-link-alt group-hover:translate-x-1 transition-transform"></i>
                                <span>Mở Google Cloud Console</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="guide-card rounded-2xl sm:rounded-3xl p-6 sm:p-8">
                    <div class="flex gap-4 sm:gap-6">
                        <div class="flex-shrink-0">
                            <div class="step-number w-12 h-12 sm:w-14 sm:h-14 rounded-xl flex items-center justify-center text-white font-black text-xl sm:text-2xl shadow-lg">
                                2
                            </div>
                        </div>
                        <div class="flex-1 pt-1">
                            <h3 class="text-xl sm:text-2xl font-black text-slate-900 mb-3 sm:mb-4">Tạo Project Mới</h3>
                            <ol class="text-sm sm:text-base text-slate-600 space-y-2 mb-4 leading-relaxed">
                                <li class="flex items-start gap-2">
                                    <span class="text-blue-600 font-bold flex-shrink-0">→</span>
                                    <span>Click nút <strong class="text-slate-900">"Select a project"</strong> ở góc trên bên trái</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-blue-600 font-bold flex-shrink-0">→</span>
                                    <span>Click <strong class="text-slate-900">"New Project"</strong></span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-blue-600 font-bold flex-shrink-0">→</span>
                                    <span>Đặt tên project</span>
                                </li>
                            </ol>
                            <div class="code-block p-4">
                                <div class="flex items-start gap-2 mb-2">
                                    <i class="fa-solid fa-lightbulb text-yellow-500 mt-1"></i>
                                    <div>
                                        <p class="text-xs sm:text-sm font-bold text-slate-700 mb-2">Gợi ý đặt tên:</p>
                                        <code class="text-sm bg-white px-3 py-1.5 rounded border border-slate-300 inline-block">YouTube Scanner HSHOP</code>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="guide-card rounded-2xl sm:rounded-3xl p-6 sm:p-8">
                    <div class="flex gap-4 sm:gap-6">
                        <div class="flex-shrink-0">
                            <div class="step-number w-12 h-12 sm:w-14 sm:h-14 rounded-xl flex items-center justify-center text-white font-black text-xl sm:text-2xl shadow-lg">
                                3
                            </div>
                        </div>
                        <div class="flex-1 pt-1">
                            <h3 class="text-xl sm:text-2xl font-black text-slate-900 mb-3 sm:mb-4">Enable YouTube Data API v3</h3>
                            <ol class="text-sm sm:text-base text-slate-600 space-y-2.5 leading-relaxed">
                                <li class="flex items-start gap-2">
                                    <span class="text-blue-600 font-bold flex-shrink-0">→</span>
                                    <span>Vào menu bên trái → Click <strong class="text-slate-900">"APIs & Services"</strong></span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-blue-600 font-bold flex-shrink-0">→</span>
                                    <span>Click <strong class="text-slate-900">"+ Enable APIs and Services"</strong> (nút xanh ở đầu trang)</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-blue-600 font-bold flex-shrink-0">→</span>
                                    <span>Tìm kiếm: <code class="bg-red-50 px-2 py-1 rounded text-red-700 font-bold text-sm">YouTube Data API v3</code></span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-blue-600 font-bold flex-shrink-0">→</span>
                                    <span>Click vào kết quả → Click nút <strong class="text-blue-600">"Enable"</strong></span>
                                </li>
                            </ol>
                        </div>
                    </div>
                </div>

                <!-- Step 4 -->
                <div class="guide-card rounded-2xl sm:rounded-3xl p-6 sm:p-8">
                    <div class="flex gap-4 sm:gap-6">
                        <div class="flex-shrink-0">
                            <div class="step-number w-12 h-12 sm:w-14 sm:h-14 rounded-xl flex items-center justify-center text-white font-black text-xl sm:text-2xl shadow-lg">
                                4
                            </div>
                        </div>
                        <div class="flex-1 pt-1">
                            <h3 class="text-xl sm:text-2xl font-black text-slate-900 mb-3 sm:mb-4">Tạo API Key</h3>
                            <ol class="text-sm sm:text-base text-slate-600 space-y-2.5 mb-4 leading-relaxed">
                                <li class="flex items-start gap-2">
                                    <span class="text-blue-600 font-bold flex-shrink-0">→</span>
                                    <span>Vào <strong class="text-slate-900">"Credentials"</strong> trong menu bên trái</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-blue-600 font-bold flex-shrink-0">→</span>
                                    <span>Click <strong class="text-slate-900">"+ Create Credentials"</strong></span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-blue-600 font-bold flex-shrink-0">→</span>
                                    <span>Chọn <strong class="text-slate-900">"API Key"</strong></span>
                                </li>
                            </ol>
                            <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-300 rounded-xl p-4 sm:p-5">
                                <div class="flex items-start gap-3">
                                    <i class="fa-solid fa-check-circle text-green-600 text-2xl mt-1"></i>
                                    <div>
                                        <p class="text-sm sm:text-base font-bold text-green-900 mb-2">✅ API Key đã được tạo!</p>
                                        <p class="text-xs sm:text-sm text-green-700 mb-3">
                                            Copy ngay API key (format: <code class="bg-white px-2 py-1 rounded text-xs">AIzaSyA...</code>)
                                        </p>
                                        <div class="bg-white/80 rounded-lg p-3 border border-green-200">
                                            <p class="text-xs sm:text-sm text-slate-700">
                                                💡 <strong>Tip:</strong> Click "Restrict Key" để giới hạn key chỉ dùng cho YouTube API (tăng bảo mật)
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 5 -->
                <div class="guide-card rounded-2xl sm:rounded-3xl p-6 sm:p-8 border-blue-300 bg-gradient-to-br from-white to-blue-50">
                    <div class="flex gap-4 sm:gap-6">
                        <div class="flex-shrink-0">
                            <div class="step-number w-12 h-12 sm:w-14 sm:h-14 rounded-xl flex items-center justify-center text-white font-black text-xl sm:text-2xl shadow-lg bg-gradient-to-br from-green-500 to-emerald-600">
                                5
                            </div>
                        </div>
                        <div class="flex-1 pt-1">
                            <h3 class="text-xl sm:text-2xl font-black text-slate-900 mb-3 sm:mb-4">Dán vào Scanner</h3>
                            <ol class="text-sm sm:text-base text-slate-600 space-y-2.5 mb-4 leading-relaxed">
                                <li class="flex items-start gap-2">
                                    <span class="text-green-600 font-bold flex-shrink-0">→</span>
                                    <span>Vào trang <strong class="text-slate-900">Scanner</strong></span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-green-600 font-bold flex-shrink-0">→</span>
                                    <span>Click icon <strong class="text-slate-900">⚙️ Settings</strong> (góc trên phải)</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-green-600 font-bold flex-shrink-0">→</span>
                                    <span>Dán YouTube API key vào ô <strong class="text-slate-900">"YouTube API Key"</strong></span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-green-600 font-bold flex-shrink-0">→</span>
                                    <span>Click <strong class="text-blue-600">"Save"</strong></span>
                                </li>
                            </ol>
                            <div class="text-center py-4">
                                <div class="text-5xl mb-3">🎉</div>
                                <p class="text-lg sm:text-xl font-black text-green-600">Hoàn tất! Bạn đã setup thành công!</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Important Note -->
            <div class="mt-8 sm:mt-12 bg-gradient-to-r from-yellow-50 to-orange-50 border-2 border-yellow-300 rounded-2xl p-6 sm:p-8">
                <div class="flex gap-4">
                    <i class="fa-solid fa-exclamation-triangle text-yellow-600 text-3xl flex-shrink-0"></i>
                    <div>
                        <h4 class="font-black text-yellow-900 mb-3 text-lg sm:text-xl">⚡ Lưu Ý Quan Trọng</h4>
                        <ul class="text-sm sm:text-base text-yellow-800 space-y-2 leading-relaxed">
                            <li class="flex items-start gap-2">
                                <span class="flex-shrink-0">•</span>
                                <span>API key hoàn toàn <strong>MIỄN PHÍ</strong>, không cần thẻ tín dụng</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="flex-shrink-0">•</span>
                                <span>Giới hạn: <strong>10,000 requests/ngày</strong> (đủ cho 100+ lần tìm kiếm)</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="flex-shrink-0">•</span>
                                <span>Hết quota? Thêm API key thứ 2 trong Settings (hệ thống tự động rotate)</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="flex-shrink-0">•</span>
                                <span>Quota reset mỗi ngày lúc 00:00 UTC (7:00 sáng giờ Việt Nam)</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- 🧠 GEMINI API GUIDE -->
    <section id="gemini-guide" class="py-16 sm:py-20 lg:py-24 bg-gradient-to-br from-purple-50 to-pink-50">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Section Header -->
            <div class="text-center mb-12 sm:mb-16">
                <div class="inline-flex items-center gap-2 bg-purple-100 text-purple-700 px-5 py-2.5 rounded-full text-sm font-bold mb-6 shadow-md">
                    <i class="fa-solid fa-brain"></i>
                    <span>MIỄN PHÍ 60 REQUESTS/PHÚT</span>
                </div>
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black text-slate-900 mb-4 sm:mb-6 leading-tight">
                    Gemini AI API
                </h2>
                <p class="text-base sm:text-lg text-slate-600 max-w-2xl mx-auto leading-relaxed">
                    Cho tính năng <strong class="text-purple-600">AI Deep Dive</strong> - Phân tích chuyên sâu bằng AI (Premium/VIP)
                </p>
            </div>

            <!-- Guide Steps -->
            <div class="space-y-6 sm:space-y-8">
                
                <!-- Step 1 -->
                <div class="guide-card rounded-2xl sm:rounded-3xl p-6 sm:p-8">
                    <div class="flex gap-4 sm:gap-6">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 sm:w-14 sm:h-14 bg-gradient-to-br from-purple-600 to-pink-600 rounded-xl flex items-center justify-center text-white font-black text-xl sm:text-2xl shadow-lg">
                                1
                            </div>
                        </div>
                        <div class="flex-1 pt-1">
                            <h3 class="text-xl sm:text-2xl font-black text-slate-900 mb-3 sm:mb-4">Truy cập Google AI Studio</h3>
                            <p class="text-sm sm:text-base text-slate-600 mb-4 leading-relaxed">
                                Mở trình duyệt và vào trang lấy API key của Gemini AI
                            </p>
                            <a href="https://aistudio.google.com/app/apikey" target="_blank" 
                               class="inline-flex items-center gap-2 bg-purple-50 hover:bg-purple-100 text-purple-700 px-5 py-3 rounded-lg transition text-sm sm:text-base font-bold group shadow-sm">
                                <i class="fa-solid fa-external-link-alt group-hover:translate-x-1 transition-transform"></i>
                                <span>Mở Google AI Studio</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="guide-card rounded-2xl sm:rounded-3xl p-6 sm:p-8">
                    <div class="flex gap-4 sm:gap-6">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 sm:w-14 sm:h-14 bg-gradient-to-br from-purple-600 to-pink-600 rounded-xl flex items-center justify-center text-white font-black text-xl sm:text-2xl shadow-lg">
                                2
                            </div>
                        </div>
                        <div class="flex-1 pt-1">
                            <h3 class="text-xl sm:text-2xl font-black text-slate-900 mb-3 sm:mb-4">Đăng nhập & Tạo API Key</h3>
                            <ol class="text-sm sm:text-base text-slate-600 space-y-2.5 mb-4 leading-relaxed">
                                <li class="flex items-start gap-2">
                                    <span class="text-purple-600 font-bold flex-shrink-0">→</span>
                                    <span>Đăng nhập bằng <strong class="text-slate-900">Gmail</strong> của bạn</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-purple-600 font-bold flex-shrink-0">→</span>
                                    <span>Click nút <strong class="text-slate-900">"Create API Key"</strong></span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-purple-600 font-bold flex-shrink-0">→</span>
                                    <span>Chọn project Google Cloud (hoặc tạo mới)</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-purple-600 font-bold flex-shrink-0">→</span>
                                    <span>✅ API key được generate tự động</span>
                                </li>
                            </ol>
                            <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-300 rounded-xl p-4 sm:p-5">
                                <div class="flex items-start gap-3">
                                    <i class="fa-solid fa-check-circle text-green-600 text-2xl mt-1"></i>
                                    <div>
                                        <p class="text-sm sm:text-base font-bold text-green-900 mb-2">✅ API Key đã sẵn sàng!</p>
                                        <p class="text-xs sm:text-sm text-green-700">
                                            Copy ngay key (format: <code class="bg-white px-2 py-1 rounded text-xs">AIzaSyB...</code>)
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="code-block p-4 mt-4">
                                <div class="flex items-start gap-2">
                                    <i class="fa-solid fa-info-circle text-blue-500 mt-0.5"></i>
                                    <p class="text-xs sm:text-sm text-slate-700">
                                        <strong>Không cần thẻ tín dụng!</strong> Chỉ cần tài khoản Gmail thông thường
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="guide-card rounded-2xl sm:rounded-3xl p-6 sm:p-8">
                    <div class="flex gap-4 sm:gap-6">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 sm:w-14 sm:h-14 bg-gradient-to-br from-purple-600 to-pink-600 rounded-xl flex items-center justify-center text-white font-black text-xl sm:text-2xl shadow-lg">
                                3
                            </div>
                        </div>
                        <div class="flex-1 pt-1">
                            <h3 class="text-xl sm:text-2xl font-black text-slate-900 mb-3 sm:mb-4">Dán vào Scanner</h3>
                            <ol class="text-sm sm:text-base text-slate-600 space-y-2.5 leading-relaxed">
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
                                    <span>Click <strong class="text-blue-600">"Save"</strong> → Xong!</span>
                                </li>
                            </ol>
                        </div>
                    </div>
                </div>

                <!-- Step 4 -->
                <div class="guide-card rounded-2xl sm:rounded-3xl p-6 sm:p-8 border-purple-300 bg-gradient-to-br from-white to-purple-50">
                    <div class="flex gap-4 sm:gap-6">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 sm:w-14 sm:h-14 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center text-white font-black text-xl sm:text-2xl shadow-lg">
                                4
                            </div>
                        </div>
                        <div class="flex-1 pt-1">
                            <h3 class="text-xl sm:text-2xl font-black text-slate-900 mb-3 sm:mb-4">Sử dụng AI Deep Dive</h3>
                            <p class="text-sm sm:text-base text-slate-600 mb-4 leading-relaxed">
                                Sau khi search channel, click nút <strong class="text-purple-600">"🤖 AI Deep Dive"</strong> để phân tích chuyên sâu
                            </p>
                            <div class="bg-purple-50 border-2 border-purple-200 rounded-xl p-4 sm:p-5">
                                <div class="flex items-start gap-3">
                                    <i class="fa-solid fa-magic text-purple-600 text-xl mt-0.5"></i>
                                    <div>
                                        <p class="text-sm font-bold text-purple-900 mb-2">🤖 AI sẽ phân tích:</p>
                                        <ul class="text-xs sm:text-sm text-purple-800 space-y-1">
                                            <li>• Niche trends & market positioning</li>
                                            <li>• Competitor insights & gaps</li>
                                            <li>• Content strategy & optimization</li>
                                            <li>• Monetization potential & growth tips</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Important Note -->
            <div class="mt-8 sm:mt-12 bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-300 rounded-2xl p-6 sm:p-8">
                <div class="flex gap-4">
                    <i class="fa-solid fa-gift text-blue-600 text-3xl flex-shrink-0"></i>
                    <div>
                        <h4 class="font-black text-blue-900 mb-3 text-lg sm:text-xl">🎁 Bonus Tips</h4>
                        <ul class="text-sm sm:text-base text-blue-800 space-y-2 leading-relaxed">
                            <li class="flex items-start gap-2">
                                <span class="flex-shrink-0">•</span>
                                <span>Gemini API có <strong>60 requests/phút</strong> miễn phí</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="flex-shrink-0">•</span>
                                <span>Hết quota? Thêm nhiều keys trong Settings để rotate tự động</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="flex-shrink-0">•</span>
                                <span>AI Deep Dive chỉ khả dụng cho gói <strong class="text-purple-600">Premium/VIP</strong></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- 🔧 HƯỚNG DẪN THÊM API VÀO TOOL -->
    <section class="py-16 sm:py-20 lg:py-24 bg-gradient-to-br from-slate-50 to-gray-100">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="text-center mb-12 sm:mb-16">
                <div class="inline-flex items-center gap-2 bg-green-100 text-green-700 px-5 py-2.5 rounded-full text-sm font-bold mb-6 shadow-md">
                    <i class="fa-solid fa-cog"></i>
                    <span>SETUP TRONG SCANNER</span>
                </div>
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black text-slate-900 mb-4 sm:mb-6 leading-tight">
                    🔧 Cách Thêm API Keys Vào Tool
                </h2>
                <p class="text-base sm:text-lg text-slate-600 max-w-2xl mx-auto leading-relaxed">
                    Sau khi có API keys, làm theo hướng dẫn này để thêm vào Scanner
                </p>
            </div>

            <div class="space-y-6 sm:space-y-8">
                
                <!-- Step 1: Đăng nhập -->
                <div class="guide-card rounded-2xl sm:rounded-3xl p-6 sm:p-8">
                    <div class="flex gap-4 sm:gap-6">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 sm:w-14 sm:h-14 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center text-white font-black text-xl sm:text-2xl shadow-lg">
                                1
                            </div>
                        </div>
                        <div class="flex-1 pt-1">
                            <h3 class="text-xl sm:text-2xl font-black text-slate-900 mb-3 sm:mb-4">Đăng Nhập Vào Scanner</h3>
                            <p class="text-sm sm:text-base text-slate-600 mb-4 leading-relaxed">
                                Truy cập trang Scanner và đăng nhập bằng tài khoản của bạn
                            </p>
                            <?php if (isLoggedIn()): ?>
                                <a href="scanner.php" 
                                   class="inline-flex items-center gap-2 bg-green-50 hover:bg-green-100 text-green-700 px-5 py-3 rounded-lg transition text-sm sm:text-base font-bold group shadow-sm">
                                    <i class="fa-solid fa-compass group-hover:rotate-12 transition-transform"></i>
                                    <span>Mở Scanner Ngay</span>
                                </a>
                            <?php else: ?>
                                <a href="login.php" 
                                   class="inline-flex items-center gap-2 bg-green-50 hover:bg-green-100 text-green-700 px-5 py-3 rounded-lg transition text-sm sm:text-base font-bold group shadow-sm">
                                    <i class="fa-solid fa-sign-in-alt group-hover:translate-x-1 transition-transform"></i>
                                    <span>Đăng Nhập Ngay</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Mở Settings -->
                <div class="guide-card rounded-2xl sm:rounded-3xl p-6 sm:p-8">
                    <div class="flex gap-4 sm:gap-6">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 sm:w-14 sm:h-14 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center text-white font-black text-xl sm:text-2xl shadow-lg">
                                2
                            </div>
                        </div>
                        <div class="flex-1 pt-1">
                            <h3 class="text-xl sm:text-2xl font-black text-slate-900 mb-3 sm:mb-4">Click Icon Settings ⚙️</h3>
                            <p class="text-sm sm:text-base text-slate-600 mb-4 leading-relaxed">
                                Ở góc trên phải của Scanner, tìm và click vào icon Settings (bánh răng cưa)
                            </p>
                            <div class="bg-blue-50 border-2 border-blue-200 rounded-xl p-4 sm:p-5">
                                <div class="flex items-start gap-3">
                                    <i class="fa-solid fa-info-circle text-blue-600 text-2xl mt-0.5"></i>
                                    <div>
                                        <p class="text-sm font-bold text-blue-900 mb-2">💡 Vị trí Settings:</p>
                                        <ul class="text-xs sm:text-sm text-blue-800 space-y-1">
                                            <li>• <strong>Desktop:</strong> Góc trên phải, cạnh avatar/username</li>
                                            <li>• <strong>Mobile:</strong> Menu hamburger (☰) → Settings</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Dán API Keys -->
                <div class="guide-card rounded-2xl sm:rounded-3xl p-6 sm:p-8">
                    <div class="flex gap-4 sm:gap-6">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 sm:w-14 sm:h-14 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center text-white font-black text-xl sm:text-2xl shadow-lg">
                                3
                            </div>
                        </div>
                        <div class="flex-1 pt-1">
                            <h3 class="text-xl sm:text-2xl font-black text-slate-900 mb-3 sm:mb-4">Dán API Keys Vào Ô Tương Ứng</h3>
                            <p class="text-sm sm:text-base text-slate-600 mb-4 leading-relaxed">
                                Modal Settings sẽ hiện ra với các ô input cho từng loại API
                            </p>
                            
                            <div class="space-y-3 mb-4">
                                <!-- YouTube API Input -->
                                <div class="bg-gradient-to-r from-red-50 to-pink-50 border-2 border-red-200 rounded-lg p-4">
                                    <div class="flex items-center gap-2 mb-2">
                                        <i class="fa-brands fa-youtube text-red-600 text-lg"></i>
                                        <span class="font-bold text-slate-900 text-sm sm:text-base">YouTube API Key</span>
                                        <span class="text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded-full font-bold">Required</span>
                                    </div>
                                    <input type="text" 
                                           placeholder="AIzaSyA... (paste your YouTube API key here)"
                                           class="w-full px-3 py-2 text-xs sm:text-sm border-2 border-red-300 rounded-lg bg-white font-mono"
                                           disabled>
                                    <p class="text-xs text-slate-600 mt-2">Paste API key từ Google Cloud Console</p>
                                </div>

                                <!-- Gemini API Input -->
                                <div class="bg-gradient-to-r from-purple-50 to-pink-50 border-2 border-purple-200 rounded-lg p-4">
                                    <div class="flex items-center gap-2 mb-2">
                                        <i class="fa-solid fa-brain text-purple-600 text-lg"></i>
                                        <span class="font-bold text-slate-900 text-sm sm:text-base">Gemini AI API Key</span>
                                        <span class="text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full font-bold">Optional</span>
                                    </div>
                                    <input type="text" 
                                           placeholder="AIzaSyB... (paste your Gemini API key here)"
                                           class="w-full px-3 py-2 text-xs sm:text-sm border-2 border-purple-300 rounded-lg bg-white font-mono"
                                           disabled>
                                    <p class="text-xs text-slate-600 mt-2">Cho tính năng AI Deep Dive (Premium only)</p>
                                </div>
                            </div>

                            <div class="bg-yellow-50 border-2 border-yellow-300 rounded-xl p-4">
                                <div class="flex items-start gap-2">
                                    <i class="fa-solid fa-lightbulb text-yellow-600 text-lg mt-0.5"></i>
                                    <div class="text-xs sm:text-sm text-yellow-900">
                                        <strong>💡 Pro Tip:</strong> Bạn có thể thêm <strong>nhiều keys</strong> cho mỗi API type. 
                                        Hệ thống sẽ tự động <strong>rotate</strong> giữa các keys khi hết quota!
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Click Save -->
                <div class="guide-card rounded-2xl sm:rounded-3xl p-6 sm:p-8 border-green-300 bg-gradient-to-br from-white to-green-50">
                    <div class="flex gap-4 sm:gap-6">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 sm:w-14 sm:h-14 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white font-black text-xl sm:text-2xl shadow-lg">
                                4
                            </div>
                        </div>
                        <div class="flex-1 pt-1">
                            <h3 class="text-xl sm:text-2xl font-black text-slate-900 mb-3 sm:mb-4">Click "Lưu" (Save)</h3>
                            <p class="text-sm sm:text-base text-slate-600 mb-4 leading-relaxed">
                                Sau khi dán xong tất cả API keys, click nút <strong class="text-blue-600">"Lưu"</strong> hoặc <strong class="text-blue-600">"Save"</strong>
                            </p>
                            
                            <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-300 rounded-xl p-4 sm:p-5">
                                <div class="flex items-start gap-3">
                                    <i class="fa-solid fa-check-circle text-green-600 text-3xl mt-0.5"></i>
                                    <div>
                                        <p class="text-base sm:text-lg font-black text-green-900 mb-2">✅ API Keys đã được lưu thành công!</p>
                                        <p class="text-xs sm:text-sm text-green-700 mb-3">
                                            Bạn sẽ thấy thông báo xác nhận và keys sẽ được lưu vào database
                                        </p>
                                        <ul class="text-xs sm:text-sm text-green-800 space-y-1">
                                            <li>• Lần sau login không cần điền lại</li>
                                            <li>• Keys được lưu mã hóa an toàn</li>
                                            <li>• Tự động load khi bạn quay lại</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 5: Bắt đầu sử dụng -->
                <div class="guide-card rounded-2xl sm:rounded-3xl p-6 sm:p-8 border-blue-300 bg-gradient-to-br from-blue-50 to-indigo-50">
                    <div class="flex gap-4 sm:gap-6">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 sm:w-14 sm:h-14 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center text-white font-black text-xl sm:text-2xl shadow-lg">
                                5
                            </div>
                        </div>
                        <div class="flex-1 pt-1">
                            <h3 class="text-xl sm:text-2xl font-black text-slate-900 mb-3 sm:mb-4">Bắt Đầu Tìm Kiếm! 🚀</h3>
                            <p class="text-sm sm:text-base text-slate-600 mb-4 leading-relaxed">
                                Đóng Settings modal và bắt đầu sử dụng Scanner với API keys của bạn
                            </p>
                            
                            <div class="bg-white rounded-xl border-2 border-blue-300 p-4 sm:p-5">
                                <div class="flex items-center gap-3 mb-3">
                                    <i class="fa-solid fa-rocket text-blue-600 text-2xl"></i>
                                    <span class="font-black text-slate-900 text-base sm:text-lg">Ready to Go!</span>
                                </div>
                                <ul class="text-xs sm:text-sm text-slate-700 space-y-2">
                                    <li class="flex items-start gap-2">
                                        <i class="fa-solid fa-check text-green-600 mt-0.5"></i>
                                        <span>Nhập <strong>keyword</strong> vào ô search (ví dụ: "làm bánh", "review đồ công nghệ")</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <i class="fa-solid fa-check text-green-600 mt-0.5"></i>
                                        <span>Click <strong>"Tìm Kiếm"</strong> hoặc nhấn <strong>Enter</strong></span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <i class="fa-solid fa-check text-green-600 mt-0.5"></i>
                                        <span>Chờ 3-5 giây để Scanner phân tích</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <i class="fa-solid fa-check text-green-600 mt-0.5"></i>
                                        <span>Xem kết quả: Channels, Videos, Metrics, Insights</span>
                                    </li>
                                </ul>
                            </div>

                            <div class="text-center mt-6 py-4">
                                <div class="text-5xl mb-3">🎉</div>
                                <p class="text-lg sm:text-xl font-black text-blue-600">Chúc mừng! Bạn đã setup xong!</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Quick Tips -->
            <div class="mt-8 sm:mt-12 grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                
                <!-- Tip 1 -->
                <div class="bg-white rounded-xl border-2 border-blue-200 p-5 sm:p-6 shadow-md">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fa-solid fa-key text-blue-600 text-lg"></i>
                        </div>
                        <h4 class="font-black text-slate-900 text-base sm:text-lg">Thêm Nhiều Keys</h4>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        Click <strong>"+ Thêm Key"</strong> để thêm nhiều YouTube hoặc Gemini keys. 
                        Hệ thống tự động rotate để tránh hết quota!
                    </p>
                </div>

                <!-- Tip 2 -->
                <div class="bg-white rounded-xl border-2 border-purple-200 p-5 sm:p-6 shadow-md">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fa-solid fa-trash text-purple-600 text-lg"></i>
                        </div>
                        <h4 class="font-black text-slate-900 text-base sm:text-lg">Xóa hoặc Sửa Keys</h4>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        Muốn xóa key? Click icon <strong>🗑️ Trash</strong> bên cạnh key. 
                        Muốn sửa? Xóa key cũ và thêm key mới.
                    </p>
                </div>

                <!-- Tip 3 -->
                <div class="bg-white rounded-xl border-2 border-green-200 p-5 sm:p-6 shadow-md">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fa-solid fa-shield-check text-green-600 text-lg"></i>
                        </div>
                        <h4 class="font-black text-slate-900 text-base sm:text-lg">Keys Được Bảo Mật</h4>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        Tất cả API keys được <strong>mã hóa</strong> trước khi lưu vào database. 
                        Chỉ bạn và hệ thống có thể truy cập!
                    </p>
                </div>

                <!-- Tip 4 -->
                <div class="bg-white rounded-xl border-2 border-orange-200 p-5 sm:p-6 shadow-md">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                            <i class="fa-solid fa-sync text-orange-600 text-lg"></i>
                        </div>
                        <h4 class="font-black text-slate-900 text-base sm:text-lg">Auto-Save Keys</h4>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        Keys được <strong>auto-save</strong> sau 2 giây khi bạn nhập. 
                        Lần sau login sẽ tự động load về!
                    </p>
                </div>

            </div>

        </div>
    </section>

    <!-- 🎯 CÁCH TOOL HOẠT ĐỘNG -->
    <section class="py-16 sm:py-20 lg:py-24 bg-gradient-to-br from-indigo-50 via-blue-50 to-cyan-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="text-center mb-12 sm:mb-16">
                <div class="inline-flex items-center gap-2 bg-indigo-100 text-indigo-700 px-5 py-2.5 rounded-full text-sm font-bold mb-6 shadow-md">
                    <i class="fa-solid fa-microchip"></i>
                    <span>WORKFLOW & FEATURES</span>
                </div>
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black text-slate-900 mb-4 sm:mb-6 leading-tight">
                    ⚙️ Cách Tool Hoạt Động
                </h2>
                <p class="text-base sm:text-lg text-slate-600 max-w-3xl mx-auto leading-relaxed">
                    Hiểu rõ workflow và tính năng để sử dụng Scanner hiệu quả nhất
                </p>
            </div>

            <!-- Workflow Diagram -->
            <div class="mb-12 sm:mb-16">
                <div class="bg-white rounded-2xl sm:rounded-3xl p-6 sm:p-8 lg:p-10 shadow-xl border-2 border-indigo-200">
                    <h3 class="text-2xl sm:text-3xl font-black text-slate-900 mb-6 sm:mb-8 text-center">
                        🔄 Workflow Tìm Kiếm
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 sm:gap-6">
                        
                        <!-- Step 1 -->
                        <div class="text-center">
                            <div class="w-16 h-16 sm:w-20 sm:h-20 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-3 sm:mb-4 shadow-lg">
                                <i class="fa-solid fa-search text-3xl sm:text-4xl text-white"></i>
                            </div>
                            <h4 class="font-bold text-slate-900 mb-2 text-sm sm:text-base">1. Nhập Keyword</h4>
                            <p class="text-xs sm:text-sm text-slate-600">Nhập từ khóa tìm kiếm</p>
                        </div>

                        <div class="hidden md:flex items-center justify-center">
                            <i class="fa-solid fa-arrow-right text-3xl text-blue-400"></i>
                        </div>

                        <!-- Step 2 -->
                        <div class="text-center">
                            <div class="w-16 h-16 sm:w-20 sm:h-20 bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-3 sm:mb-4 shadow-lg">
                                <i class="fa-brands fa-youtube text-3xl sm:text-4xl text-white"></i>
                            </div>
                            <h4 class="font-bold text-slate-900 mb-2 text-sm sm:text-base">2. Query YouTube</h4>
                            <p class="text-xs sm:text-sm text-slate-600">Gọi YouTube API</p>
                        </div>

                        <div class="hidden md:flex items-center justify-center">
                            <i class="fa-solid fa-arrow-right text-3xl text-purple-400"></i>
                        </div>

                        <!-- Step 3 -->
                        <div class="text-center">
                            <div class="w-16 h-16 sm:w-20 sm:h-20 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center mx-auto mb-3 sm:mb-4 shadow-lg">
                                <i class="fa-solid fa-chart-bar text-3xl sm:text-4xl text-white"></i>
                            </div>
                            <h4 class="font-bold text-slate-900 mb-2 text-sm sm:text-base">3. Phân Tích Data</h4>
                            <p class="text-xs sm:text-sm text-slate-600">Tính toán metrics</p>
                        </div>

                    </div>

                    <div class="flex justify-center my-4 sm:my-6">
                        <i class="fa-solid fa-arrow-down text-4xl text-green-400"></i>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                        
                        <!-- Step 4 -->
                        <div class="text-center">
                            <div class="w-16 h-16 sm:w-20 sm:h-20 bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl flex items-center justify-center mx-auto mb-3 sm:mb-4 shadow-lg">
                                <i class="fa-solid fa-table text-3xl sm:text-4xl text-white"></i>
                            </div>
                            <h4 class="font-bold text-slate-900 mb-2 text-sm sm:text-base">4. Hiển Thị Kết Quả</h4>
                            <p class="text-xs sm:text-sm text-slate-600">Table với channels & metrics</p>
                        </div>

                        <!-- Step 5 (Optional) -->
                        <div class="text-center">
                            <div class="w-16 h-16 sm:w-20 sm:h-20 bg-gradient-to-br from-pink-500 to-pink-600 rounded-2xl flex items-center justify-center mx-auto mb-3 sm:mb-4 shadow-lg">
                                <i class="fa-solid fa-brain text-3xl sm:text-4xl text-white"></i>
                            </div>
                            <h4 class="font-bold text-slate-900 mb-2 text-sm sm:text-base">5. AI Deep Dive (Optional)</h4>
                            <p class="text-xs sm:text-sm text-slate-600">Phân tích AI chuyên sâu</p>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Main Features -->
            <div class="mb-12 sm:mb-16">
                <h3 class="text-2xl sm:text-3xl font-black text-slate-900 mb-6 sm:mb-8 text-center">
                    ✨ Tính Năng Chính
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
                    
                    <!-- Feature 1 -->
                    <div class="bg-white rounded-xl border-2 border-blue-200 p-6 shadow-lg hover:shadow-xl transition-shadow">
                        <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mb-4 shadow-md">
                            <i class="fa-solid fa-search text-2xl text-white"></i>
                        </div>
                        <h4 class="font-black text-slate-900 mb-3 text-lg">Tìm Kiếm Thông Minh</h4>
                        <ul class="text-sm text-slate-600 space-y-2">
                            <li class="flex items-start gap-2">
                                <i class="fa-solid fa-check text-green-600 mt-0.5"></i>
                                <span>Search theo <strong>keyword</strong>, <strong>topic</strong>, <strong>niche</strong></span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fa-solid fa-check text-green-600 mt-0.5"></i>
                                <span>Lọc theo <strong>views</strong>, <strong>subs</strong>, <strong>engagement</strong></span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fa-solid fa-check text-green-600 mt-0.5"></i>
                                <span>Sort kết quả theo nhiều tiêu chí</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Feature 2 -->
                    <div class="bg-white rounded-xl border-2 border-purple-200 p-6 shadow-lg hover:shadow-xl transition-shadow">
                        <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center mb-4 shadow-md">
                            <i class="fa-solid fa-chart-line text-2xl text-white"></i>
                        </div>
                        <h4 class="font-black text-slate-900 mb-3 text-lg">Phân Tích Metrics</h4>
                        <ul class="text-sm text-slate-600 space-y-2">
                            <li class="flex items-start gap-2">
                                <i class="fa-solid fa-check text-green-600 mt-0.5"></i>
                                <span><strong>Subscribers</strong>, <strong>Total Views</strong>, <strong>Video Count</strong></span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fa-solid fa-check text-green-600 mt-0.5"></i>
                                <span><strong>Avg Views/Video</strong>, <strong>Engagement Rate</strong></span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fa-solid fa-check text-green-600 mt-0.5"></i>
                                <span>Tính <strong>CPM estimate</strong>, <strong>Revenue potential</strong></span>
                            </li>
                        </ul>
                    </div>

                    <!-- Feature 3 -->
                    <div class="bg-white rounded-xl border-2 border-green-200 p-6 shadow-lg hover:shadow-xl transition-shadow">
                        <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center mb-4 shadow-md">
                            <i class="fa-solid fa-bullseye text-2xl text-white"></i>
                        </div>
                        <h4 class="font-black text-slate-900 mb-3 text-lg">Tìm Niche Cạnh Thấp</h4>
                        <ul class="text-sm text-slate-600 space-y-2">
                            <li class="flex items-start gap-2">
                                <i class="fa-solid fa-check text-green-600 mt-0.5"></i>
                                <span>Phát hiện <strong>Blue Ocean</strong> opportunities</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fa-solid fa-check text-green-600 mt-0.5"></i>
                                <span>Highlight channels có <strong>high views/low subs</strong></span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fa-solid fa-check text-green-600 mt-0.5"></i>
                                <span>Gợi ý <strong>under-served niches</strong></span>
                            </li>
                        </ul>
                    </div>

                    <!-- Feature 4 -->
                    <div class="bg-white rounded-xl border-2 border-orange-200 p-6 shadow-lg hover:shadow-xl transition-shadow">
                        <div class="w-14 h-14 bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl flex items-center justify-center mb-4 shadow-md">
                            <i class="fa-solid fa-users text-2xl text-white"></i>
                        </div>
                        <h4 class="font-black text-slate-900 mb-3 text-lg">Phân Tích Đối Thủ</h4>
                        <ul class="text-sm text-slate-600 space-y-2">
                            <li class="flex items-start gap-2">
                                <i class="fa-solid fa-check text-green-600 mt-0.5"></i>
                                <span>Xem <strong>top videos</strong> của channel</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fa-solid fa-check text-green-600 mt-0.5"></i>
                                <span>Phân tích <strong>content strategy</strong></span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fa-solid fa-check text-green-600 mt-0.5"></i>
                                <span>So sánh <strong>performance metrics</strong></span>
                            </li>
                        </ul>
                    </div>

                    <!-- Feature 5 -->
                    <div class="bg-white rounded-xl border-2 border-pink-200 p-6 shadow-lg hover:shadow-xl transition-shadow">
                        <div class="w-14 h-14 bg-gradient-to-br from-pink-500 to-pink-600 rounded-xl flex items-center justify-center mb-4 shadow-md">
                            <i class="fa-solid fa-brain text-2xl text-white"></i>
                        </div>
                        <h4 class="font-black text-slate-900 mb-3 text-lg">AI Deep Dive</h4>
                        <ul class="text-sm text-slate-600 space-y-2">
                            <li class="flex items-start gap-2">
                                <i class="fa-solid fa-crown text-purple-600 mt-0.5"></i>
                                <span><strong>Premium/VIP only</strong></span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fa-solid fa-check text-green-600 mt-0.5"></i>
                                <span>AI phân tích <strong>niche trends</strong></span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fa-solid fa-check text-green-600 mt-0.5"></i>
                                <span>Gợi ý <strong>content ideas</strong> & <strong>optimization</strong></span>
                            </li>
                        </ul>
                    </div>

                    <!-- Feature 6 -->
                    <div class="bg-white rounded-xl border-2 border-indigo-200 p-6 shadow-lg hover:shadow-xl transition-shadow">
                        <div class="w-14 h-14 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl flex items-center justify-center mb-4 shadow-md">
                            <i class="fa-solid fa-download text-2xl text-white"></i>
                        </div>
                        <h4 class="font-black text-slate-900 mb-3 text-lg">Export Data</h4>
                        <ul class="text-sm text-slate-600 space-y-2">
                            <li class="flex items-start gap-2">
                                <i class="fa-solid fa-check text-green-600 mt-0.5"></i>
                                <span>Export kết quả ra <strong>CSV/Excel</strong></span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fa-solid fa-check text-green-600 mt-0.5"></i>
                                <span>Lưu search history</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fa-solid fa-check text-green-600 mt-0.5"></i>
                                <span>Bookmark channels yêu thích</span>
                            </li>
                        </ul>
                    </div>

                </div>
            </div>

            <!-- Usage Tips -->
            <div class="bg-gradient-to-r from-amber-50 to-orange-50 border-2 border-amber-300 rounded-2xl sm:rounded-3xl p-6 sm:p-8 lg:p-10">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-14 h-14 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fa-solid fa-lightbulb text-2xl text-white"></i>
                    </div>
                    <h3 class="text-2xl sm:text-3xl font-black text-slate-900">💡 Tips Sử Dụng Hiệu Quả</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                    
                    <div class="bg-white rounded-xl p-4 sm:p-5 border-2 border-amber-200">
                        <h4 class="font-bold text-slate-900 mb-2 text-sm sm:text-base flex items-center gap-2">
                            <span class="w-6 h-6 bg-amber-500 text-white rounded-full flex items-center justify-center text-xs font-black">1</span>
                            Search Keyword Cụ Thể
                        </h4>
                        <p class="text-xs sm:text-sm text-slate-600">
                            Thay vì "làm bánh", dùng "cách làm bánh bông lan xốp mềm" để tìm niche cụ thể hơn.
                        </p>
                    </div>

                    <div class="bg-white rounded-xl p-4 sm:p-5 border-2 border-amber-200">
                        <h4 class="font-bold text-slate-900 mb-2 text-sm sm:text-base flex items-center gap-2">
                            <span class="w-6 h-6 bg-amber-500 text-white rounded-full flex items-center justify-center text-xs font-black">2</span>
                            Lọc Channels Nhỏ
                        </h4>
                        <p class="text-xs sm:text-sm text-slate-600">
                            Channels < 50K subs nhưng có high views/video = Niche cạnh thấp, dễ làm!
                        </p>
                    </div>

                    <div class="bg-white rounded-xl p-4 sm:p-5 border-2 border-amber-200">
                        <h4 class="font-bold text-slate-900 mb-2 text-sm sm:text-base flex items-center gap-2">
                            <span class="w-6 h-6 bg-amber-500 text-white rounded-full flex items-center justify-center text-xs font-black">3</span>
                            Xem Top Videos
                        </h4>
                        <p class="text-xs sm:text-sm text-slate-600">
                            Click vào channel để xem top 10 videos → Phân tích title pattern, thumbnail style.
                        </p>
                    </div>

                    <div class="bg-white rounded-xl p-4 sm:p-5 border-2 border-amber-200">
                        <h4 class="font-bold text-slate-900 mb-2 text-sm sm:text-base flex items-center gap-2">
                            <span class="w-6 h-6 bg-amber-500 text-white rounded-full flex items-center justify-center text-xs font-black">4</span>
                            Dùng AI Deep Dive
                        </h4>
                        <p class="text-xs sm:text-sm text-slate-600">
                            Với Premium, click "AI Deep Dive" để nhận insights chuyên sâu về niche trends & strategy.
                        </p>
                    </div>

                    <div class="bg-white rounded-xl p-4 sm:p-5 border-2 border-amber-200">
                        <h4 class="font-bold text-slate-900 mb-2 text-sm sm:text-base flex items-center gap-2">
                            <span class="w-6 h-6 bg-amber-500 text-white rounded-full flex items-center justify-center text-xs font-black">5</span>
                            Thêm Nhiều API Keys
                        </h4>
                        <p class="text-xs sm:text-sm text-slate-600">
                            2-3 YouTube keys = 20K-30K requests/ngày. Không bao giờ hết quota!
                        </p>
                    </div>

                    <div class="bg-white rounded-xl p-4 sm:p-5 border-2 border-amber-200">
                        <h4 class="font-bold text-slate-900 mb-2 text-sm sm:text-base flex items-center gap-2">
                            <span class="w-6 h-6 bg-amber-500 text-white rounded-full flex items-center justify-center text-xs font-black">6</span>
                            Export & Analyze
                        </h4>
                        <p class="text-xs sm:text-sm text-slate-600">
                            Export kết quả ra CSV để phân tích sâu hơn trong Excel/Google Sheets.
                        </p>
                    </div>

                </div>
            </div>

        </div>
    </section>

    <!-- ❓ FAQ SECTION -->
    <section class="py-16 sm:py-20 lg:py-24">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="text-center mb-12 sm:mb-16">
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black text-slate-900 mb-4 sm:mb-6">
                    ❓ Câu Hỏi Thường Gặp
                </h2>
                <p class="text-base sm:text-lg text-slate-600">
                    Giải đáp nhanh các thắc mắc phổ biến
                </p>
            </div>

            <div class="space-y-4 sm:space-y-5">
                
                <!-- FAQ 1 -->
                <details class="group bg-white rounded-xl sm:rounded-2xl shadow-lg hover:shadow-xl transition-shadow">
                    <summary class="cursor-pointer p-5 sm:p-6 flex items-center justify-between font-bold text-slate-900 text-sm sm:text-base">
                        <span class="flex items-center gap-3 flex-1">
                            <i class="fa-solid fa-circle-question text-blue-600 text-xl flex-shrink-0"></i>
                            <span>API key có mất phí không?</span>
                        </span>
                        <i class="fa-solid fa-chevron-down group-open:rotate-180 transition-transform text-slate-400 flex-shrink-0 ml-2"></i>
                    </summary>
                    <div class="px-5 sm:px-6 pb-5 sm:pb-6 text-sm sm:text-base text-slate-600 leading-relaxed border-t border-slate-100 pt-4">
                        <strong class="text-green-600">✅ Hoàn toàn MIỄN PHÍ!</strong> Cả YouTube API và Gemini API đều không yêu cầu thẻ tín dụng. 
                        Bạn có quota miễn phí: <strong>10,000 requests/ngày</strong> cho YouTube và <strong>60 requests/phút</strong> cho Gemini.
                    </div>
                </details>

                <!-- FAQ 2 -->
                <details class="group bg-white rounded-xl sm:rounded-2xl shadow-lg hover:shadow-xl transition-shadow">
                    <summary class="cursor-pointer p-5 sm:p-6 flex items-center justify-between font-bold text-slate-900 text-sm sm:text-base">
                        <span class="flex items-center gap-3 flex-1">
                            <i class="fa-solid fa-circle-question text-blue-600 text-xl flex-shrink-0"></i>
                            <span>Hết quota thì sao?</span>
                        </span>
                        <i class="fa-solid fa-chevron-down group-open:rotate-180 transition-transform text-slate-400 flex-shrink-0 ml-2"></i>
                    </summary>
                    <div class="px-5 sm:px-6 pb-5 sm:pb-6 text-sm sm:text-base text-slate-600 leading-relaxed border-t border-slate-100 pt-4">
                        Nếu hết quota, bạn có <strong>2 cách</strong>:<br><br>
                        <strong>1️⃣ Thêm API key thứ 2</strong> - Scanner sẽ tự động rotate giữa các keys<br>
                        <strong>2️⃣ Đợi reset quota</strong>:
                        <ul class="mt-2 ml-4 space-y-1">
                            <li>• YouTube: Reset mỗi ngày lúc 00:00 UTC</li>
                            <li>• Gemini: Reset mỗi phút</li>
                        </ul>
                    </div>
                </details>

                <!-- FAQ 3 -->
                <details class="group bg-white rounded-xl sm:rounded-2xl shadow-lg hover:shadow-xl transition-shadow">
                    <summary class="cursor-pointer p-5 sm:p-6 flex items-center justify-between font-bold text-slate-900 text-sm sm:text-base">
                        <span class="flex items-center gap-3 flex-1">
                            <i class="fa-solid fa-circle-question text-blue-600 text-xl flex-shrink-0"></i>
                            <span>API key có an toàn không?</span>
                        </span>
                        <i class="fa-solid fa-chevron-down group-open:rotate-180 transition-transform text-slate-400 flex-shrink-0 ml-2"></i>
                    </summary>
                    <div class="px-5 sm:px-6 pb-5 sm:pb-6 text-sm sm:text-base text-slate-600 leading-relaxed border-t border-slate-100 pt-4">
                        <strong class="text-green-600">✅ 100% an toàn!</strong> API keys được lưu trữ <strong>mã hóa</strong> trên server. 
                        Chỉ bạn và hệ thống mới có thể sử dụng. Không ai khác có thể truy cập keys của bạn.
                    </div>
                </details>

                <!-- FAQ 4 -->
                <details class="group bg-white rounded-xl sm:rounded-2xl shadow-lg hover:shadow-xl transition-shadow">
                    <summary class="cursor-pointer p-5 sm:p-6 flex items-center justify-between font-bold text-slate-900 text-sm sm:text-base">
                        <span class="flex items-center gap-3 flex-1">
                            <i class="fa-solid fa-circle-question text-blue-600 text-xl flex-shrink-0"></i>
                            <span>Không có API key vẫn dùng được không?</span>
                        </span>
                        <i class="fa-solid fa-chevron-down group-open:rotate-180 transition-transform text-slate-400 flex-shrink-0 ml-2"></i>
                    </summary>
                    <div class="px-5 sm:px-6 pb-5 sm:pb-6 text-sm sm:text-base text-slate-600 leading-relaxed border-t border-slate-100 pt-4">
                        <strong class="text-blue-600">✅ Có thể!</strong> Hệ thống có <strong>API Pool</strong> dự phòng cho user chưa có key. 
                        Tuy nhiên, để có trải nghiệm tốt nhất và không bị giới hạn, bạn nên lấy API key riêng (chỉ mất 5 phút).
                    </div>
                </details>

                <!-- FAQ 5 -->
                <details class="group bg-white rounded-xl sm:rounded-2xl shadow-lg hover:shadow-xl transition-shadow">
                    <summary class="cursor-pointer p-5 sm:p-6 flex items-center justify-between font-bold text-slate-900 text-sm sm:text-base">
                        <span class="flex items-center gap-3 flex-1">
                            <i class="fa-solid fa-circle-question text-blue-600 text-xl flex-shrink-0"></i>
                            <span>Có thể thêm nhiều API keys không?</span>
                        </span>
                        <i class="fa-solid fa-chevron-down group-open:rotate-180 transition-transform text-slate-400 flex-shrink-0 ml-2"></i>
                    </summary>
                    <div class="px-5 sm:px-6 pb-5 sm:pb-6 text-sm sm:text-base text-slate-600 leading-relaxed border-t border-slate-100 pt-4">
                        <strong class="text-green-600">✅ Không giới hạn!</strong> Bạn có thể thêm nhiều keys để:
                        <ul class="mt-2 ml-4 space-y-1">
                            <li>• Tăng quota (ví dụ: 2 YouTube keys = 20,000 requests/ngày)</li>
                            <li>• Backup khi key chính hết quota</li>
                            <li>• Hệ thống tự động rotate giữa các keys</li>
                        </ul>
                    </div>
                </details>

            </div>

        </div>
    </section>

    <!-- 🆘 HELP CENTER -->
    <section class="py-16 sm:py-20 lg:py-24 bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 text-white relative overflow-hidden">
        <!-- Decorative Background -->
        <div class="absolute inset-0 bg-black/10"></div>
        <div class="absolute top-0 right-0 w-96 h-96 bg-white/10 rounded-full -translate-y-48 translate-x-48"></div>
        
        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="w-20 h-20 sm:w-24 sm:h-24 bg-white/20 backdrop-blur-sm rounded-3xl flex items-center justify-center mx-auto mb-6 sm:mb-8 shadow-2xl">
                <i class="fa-solid fa-life-ring text-5xl sm:text-6xl text-white"></i>
            </div>
            
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black mb-4 sm:mb-6 leading-tight">
                Cần Hỗ Trợ Thêm?
            </h2>
            
            <p class="text-lg sm:text-xl lg:text-2xl mb-8 sm:mb-10 leading-relaxed text-white/90 max-w-2xl mx-auto">
                Đội ngũ support HSHOP sẵn sàng hỗ trợ 1-1 để bạn setup thành công 100%
            </p>
            
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="#youtube-guide" 
                   class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-white hover:bg-gray-50 text-purple-600 font-bold px-8 py-4 rounded-xl transition shadow-2xl text-base sm:text-lg group">
                    <i class="fa-brands fa-youtube text-2xl group-hover:scale-110 transition-transform"></i>
                    <span>Xem Lại Hướng Dẫn</span>
                </a>
                
                <a href="https://zalo.me/<?php echo str_replace([' ', '-'], '', SUPPORT_HOTLINE ?? '0123456789'); ?>" 
                   target="_blank"
                   class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white font-bold px-8 py-4 rounded-xl transition text-base sm:text-lg group border-2 border-white/30">
                    <i class="fa-solid fa-phone text-2xl group-hover:rotate-12 transition-transform"></i>
                    <span><?php echo SUPPORT_HOTLINE ?? '0123-456-789'; ?></span>
                </a>
            </div>
            
            <!-- Trust Indicators -->
            <div class="mt-8 sm:mt-10 flex flex-wrap items-center justify-center gap-6 sm:gap-8 text-white/80 text-sm sm:text-base">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-headset text-xl"></i>
                    <span>Support 24/7</span>
                </div>
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-clock text-xl"></i>
                    <span>Phản hồi &lt; 2h</span>
                </div>
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-star text-xl"></i>
                    <span>4.9/5 đánh giá</span>
                </div>
            </div>
        </div>
    </section>

    <!-- 📄 FOOTER -->
    <footer class="bg-slate-900 text-slate-300 py-12 sm:py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 text-center">
            <div class="mb-8">
                <h4 class="font-bold text-white mb-3 text-xl sm:text-2xl">HSHOP Analytics</h4>
                <p class="text-sm sm:text-base text-slate-400 max-w-2xl mx-auto">
                    Công cụ phân tích YouTube chuyên nghiệp - Tìm ngách, phân tích đối thủ, tối ưu nội dung
                </p>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 sm:gap-8 mb-8 max-w-3xl mx-auto">
                <div>
                    <h5 class="font-bold text-white mb-3 text-sm sm:text-base">Sản Phẩm</h5>
                    <ul class="space-y-2 text-xs sm:text-sm">
                        <li><a href="scanner.php" class="hover:text-white transition">YouTube Scanner</a></li>
                        <li><a href="pricing.php" class="hover:text-white transition">Bảng Giá</a></li>
                        <li><a href="guide.php" class="hover:text-white transition">Hướng Dẫn</a></li>
                    </ul>
                </div>
                <div>
                    <h5 class="font-bold text-white mb-3 text-sm sm:text-base">Hỗ Trợ</h5>
                    <ul class="space-y-2 text-xs sm:text-sm">
                        <li><a href="#youtube-guide" class="hover:text-white transition">YouTube API</a></li>
                        <li><a href="#gemini-guide" class="hover:text-white transition">Gemini API</a></li>
                        <li><a href="HUONG_DAN_SU_DUNG.md" class="hover:text-white transition">Documentation</a></li>
                    </ul>
                </div>
                <div>
                    <h5 class="font-bold text-white mb-3 text-sm sm:text-base">Liên Hệ</h5>
                    <ul class="space-y-2 text-xs sm:text-sm">
                        <li>Hotline: <?php echo SUPPORT_HOTLINE ?? '0123-456-789'; ?></li>
                        <li>Email: support@hungniwaco.shop</li>
                        <li>Support: 24/7</li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-slate-800 pt-8">
               
                <p class="text-xs text-slate-500">
                    © 2026 HSHOP Analytics • Made with ❤️ in Vietnam
                </p>
            </div>
        </div>
    </footer>

    <!-- Scroll to Top Button -->
    <button onclick="window.scrollTo({top: 0, behavior: 'smooth'})" 
            class="fixed bottom-6 right-6 bg-blue-600 hover:bg-blue-700 text-white w-12 h-12 rounded-full shadow-2xl flex items-center justify-center transition-all hover:scale-110 z-40"
            id="scrollTopBtn"
            style="display: none;">
        <i class="fa-solid fa-arrow-up text-xl"></i>
    </button>

    <script>
        // Show/hide scroll to top button
        window.addEventListener('scroll', function() {
            const scrollBtn = document.getElementById('scrollTopBtn');
            if (window.pageYOffset > 300) {
                scrollBtn.style.display = 'flex';
            } else {
                scrollBtn.style.display = 'none';
            }
        });
    </script>

</body>
</html>
