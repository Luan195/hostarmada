<?php
// 🎊 TẾT 2026 CHECKOUT PAGE - CAMPAIGN EXCLUSIVE
require_once 'includes/session.php';
require_once 'includes/functions.php';
require_once 'config/lixitet.php'; // 🧧 Lì Xì Tết Pricing (SECURE)

// Require login
if (!isLoggedIn()) {
    // Save intended destination
    $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
    redirect('login.php?redirect=tet-checkout.php&plan=' . ($_GET['plan'] ?? 'tet_12m'));
}

// Check if campaign is still active
if (!isTetCampaignActive()) {
    redirect('pricing.php?error=campaign_ended');
}

// Get current user
$currentUser = getCurrentUser();
$username = $_SESSION['username'];

// Get selected plan
$selectedPlan = $_GET['plan'] ?? 'tet_12m';
if (!isTetPlan($selectedPlan)) {
    redirect('tet-promo.php?error=invalid_plan');
}

$plan = getTetPricingPlan($selectedPlan);
if (!$plan) {
    redirect('tet-promo.php?error=plan_not_found');
}

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_order'])) {
    $customerName = sanitize($_POST['customer_name'] ?? '');
    $customerPhone = sanitize($_POST['customer_phone'] ?? '');
    $customerEmail = sanitize($_POST['customer_email'] ?? '');
    $transferNote = sanitize($_POST['transfer_note'] ?? '');
    
    // Validation
    if (empty($customerName) || empty($customerPhone)) {
        $error = 'Vui lòng điền đầy đủ thông tin!';
    }
    
    // Create order if no errors
    if (empty($error)) {
        $orders = loadDB('orders.json');
        
        $orderId = 'ORD' . time() . rand(1000, 9999);
        $orders[$orderId] = [
            'order_id' => $orderId,
            'username' => $username,
            'plan' => $selectedPlan,              // e.g., "tet_12m"
            'plan_label' => $plan['name'],        // e.g., "👑 Tết 12 Tháng"
            'amount' => $plan['tet_price'],       // Giá Tết (299K)
            'duration_days' => $plan['duration_days'], // 365 days
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
            'customer_email' => $customerEmail,
            'transfer_note' => $transferNote,
            'payment_proof' => 'auto',            // Auto-activation mode
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'approved_at' => null,
            'approved_by' => null,
            
            // ✅ TET CAMPAIGN TRACKING
            'campaign' => 'tet2026',
            'campaign_price' => $plan['tet_price'],      // Price paid
            'original_price' => $plan['original_price'], // Regular price
            'discount_percent' => $plan['discount_percent'],
            'expires_campaign' => TET_CAMPAIGN_END       // Campaign end date
        ];
        
        // Track referrer if exists
        if (!empty($currentUser['referred_by'])) {
            $orders[$orderId]['referrer'] = $currentUser['referred_by'];
        }
        
        if (saveDB('orders.json', $orders)) {
            $message = '✅ Đơn hàng TẾT đã được tạo! Hệ thống sẽ tự động kích hoạt sau 1-5 phút khi nhận được tiền.';
        } else {
            $error = 'Lỗi lưu đơn hàng! Vui lòng thử lại.';
        }
    }
}

// Pre-fill customer info - KEEP FIELDS EMPTY for user input
$customerName = ''; // Let user enter real name (not username)
$customerEmail = ''; // Let user enter verified email
$customerPhone = ''; // Let user enter phone number
$transferNote = $username . ' ' . str_replace('tet_', '', $selectedPlan); // e.g., "ndgroup 12m" (auto-generated)
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎊 Thanh Toán Tết - <?php echo $plan['name']; ?> | HSHOP Analytics</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap');
        * { font-family: 'Inter', sans-serif; }
        
        .gradient-tet {
            background: linear-gradient(135deg, #ff0000 0%, #ff6b00 50%, #ffd700 100%);
        }
        
        @keyframes pulseRed {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        .pulse-red { animation: pulseRed 2s infinite; }
    </style>
</head>
<body class="bg-gradient-to-br from-red-50 via-orange-50 to-yellow-50 min-h-screen">

    <!-- Header -->
    <header class="bg-white shadow-md py-4 border-b-4 border-red-600">
        <div class="max-w-6xl mx-auto px-4 flex justify-between items-center">
            <a href="tet-promo.php" class="text-2xl font-black text-red-600">
                <i class="fa-brands fa-youtube"></i> HSHOP Analytics
                <span class="text-sm text-slate-600 block">🎊 THANH TOÁN TẾT 2026</span>
            </a>
            <a href="tet-promo.php" class="text-slate-600 hover:text-slate-900">
                <i class="fa-solid fa-arrow-left mr-2"></i> Quay lại
            </a>
        </div>
    </header>

    <div class="max-w-5xl mx-auto px-4 py-12">
        
        <!-- Success/Error Messages -->
        <?php if (!empty($message)): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-6 mb-6 rounded-lg shadow-lg">
            <div class="flex items-center">
                <i class="fa-solid fa-check-circle text-3xl mr-4"></i>
                <div>
                    <h3 class="font-bold text-lg mb-2"><?php echo $message; ?></h3>
                    <p class="text-sm">Bạn có thể theo dõi trạng thái đơn hàng tại <a href="scanner.php" class="underline font-bold">trang Scanner</a>.</p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-6 mb-6 rounded-lg shadow-lg">
            <div class="flex items-center">
                <i class="fa-solid fa-exclamation-circle text-3xl mr-4"></i>
                <p class="font-bold"><?php echo $error; ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <!-- LEFT: Order Summary -->
            <div class="bg-white rounded-2xl p-8 shadow-xl border-4 border-red-200">
                <div class="text-center mb-6 pb-6 border-b-2 border-red-100">
                    <div class="inline-block bg-red-600 text-white px-4 py-2 rounded-full text-sm font-bold mb-3">
                        🎊 ƯU ĐÃI TẾT 2026
                    </div>
                    <h2 class="text-3xl font-black text-slate-900 mb-2">
                        <?php echo $plan['name']; ?>
                    </h2>
                    <p class="text-sm text-slate-600"><?php echo $plan['duration']; ?></p>
                </div>
                
                <!-- Price Breakdown -->
                <div class="space-y-4 mb-6">
                    <div class="flex justify-between items-center">
                        <span class="text-slate-600">Giá thường:</span>
                        <span class="line-through text-gray-500"><?php echo formatTetPrice($plan['original_price']); ?></span>
                    </div>
                    <div class="flex justify-between items-center text-green-600 font-bold">
                        <span>Giảm giá Tết (<?php echo $plan['discount_percent']; ?>%):</span>
                        <span>-<?php echo formatTetPrice($plan['save_amount']); ?></span>
                    </div>
                    <div class="border-t-2 border-slate-200 pt-4 flex justify-between items-center">
                        <span class="text-xl font-bold">Tổng thanh toán:</span>
                        <span class="text-4xl font-black text-red-600"><?php echo formatTetPrice($plan['tet_price']); ?></span>
                    </div>
                    <div class="bg-yellow-50 border-2 border-yellow-300 rounded-lg p-4 text-center">
                        <p class="text-sm font-bold text-yellow-800">
                            🎉 Bạn đã tiết kiệm được <span class="text-green-600 text-lg"><?php echo formatTetPrice($plan['save_amount']); ?></span>!
                        </p>
                    </div>
                </div>
                
                <!-- Features -->
                <div class="border-t-2 border-slate-200 pt-6">
                    <h3 class="font-bold text-lg mb-4 text-slate-900">✨ Tính năng bao gồm:</h3>
                    <ul class="space-y-2">
                        <?php foreach (array_slice($plan['features'], 0, 6) as $feature): ?>
                        <li class="flex items-start gap-2 text-sm">
                            <i class="fa-solid fa-check-circle text-green-500 mt-0.5 flex-shrink-0"></i>
                            <span><?php echo $feature; ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <!-- Countdown -->
                <div class="mt-6 bg-red-50 border-2 border-red-300 rounded-lg p-4 text-center">
                    <?php echo getTetCountdownHTML(); ?>
                </div>
            </div>
            
            <!-- RIGHT: Payment Form -->
            <div class="bg-white rounded-2xl p-8 shadow-xl border-2 border-slate-200">
                <h3 class="text-2xl font-black text-slate-900 mb-6">💳 Thông Tin Thanh Toán</h3>
                
                <!-- QR Code Section -->
                <div class="bg-gradient-to-br from-red-50 to-orange-50 rounded-xl p-6 mb-6 border-2 border-red-200">
                    <h4 class="font-bold text-lg mb-4 text-center text-slate-900">
                        📱 Quét Mã QR Để Thanh Toán
                    </h4>
                    
                    <!-- QR Code Image -->
                    <div class="bg-white p-4 rounded-lg mb-4">
                        <img src="<?php echo VIETQR_IMAGE; ?>" 
                             alt="VietQR Code" 
                             class="w-full max-w-xs mx-auto rounded-lg shadow-lg">
                    </div>
                    
                    <!-- Bank Info -->
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-slate-600">Ngân hàng:</span>
                            <span class="font-bold"><?php echo BANK_NAME; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-600">Số tài khoản:</span>
                            <span class="font-bold"><?php echo BANK_ACCOUNT_NUMBER; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-600">Chủ tài khoản:</span>
                            <span class="font-bold"><?php echo BANK_ACCOUNT_NAME; ?></span>
                        </div>
                        <div class="flex justify-between border-t-2 border-red-200 pt-2 mt-2">
                            <span class="text-slate-600">Số tiền:</span>
                            <span class="font-black text-red-600 text-lg"><?php echo formatTetPrice($plan['tet_price']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-600">Nội dung:</span>
                            <span class="font-bold text-blue-600"><?php echo $transferNote; ?></span>
                        </div>
                    </div>
                    
                    <div class="mt-4 bg-yellow-100 border-2 border-yellow-400 rounded-lg p-3 text-center">
                        <p class="text-xs font-bold text-yellow-900">
                            ⚠️ VUI LÒNG GHI ĐÚNG NỘI DUNG để hệ thống tự động kích hoạt!
                        </p>
                    </div>
                </div>
                
                <!-- Customer Info Form -->
                <form method="POST" class="space-y-4">
                    <!-- 👤 THÔNG TIN NGƯỜI CHUYỂN KHOẢN -->
                    <div class="bg-white border-2 border-red-300 rounded-xl p-6">
                        <h3 class="text-lg font-black text-slate-900 mb-4 flex items-center gap-2">
                            <i class="fa-solid fa-user-circle text-red-600"></i>
                            Thông Tin Người Chuyển Khoản
                        </h3>
                        <p class="text-sm text-slate-600 mb-4">
                            ⚠️ <strong>Quan trọng:</strong> Điền chính xác để admin dễ dàng xác nhận thanh toán
                        </p>
                        
                        <div class="space-y-4">
                            <!-- Họ và tên -->
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    Họ và tên <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       name="customer_name" 
                                       value="<?php echo htmlspecialchars($customerName); ?>"
                                       required 
                                       placeholder="Nhập họ và tên đầy đủ (VD: Nguyễn Văn A)"
                                       class="w-full px-4 py-3 border-2 border-slate-300 rounded-lg focus:border-red-500 focus:outline-none">
                                <p class="text-xs text-slate-500 mt-1">
                                    💡 Tên này sẽ hiển thị trong lịch sử chuyển khoản
                                </p>
                            </div>
                            
                            <!-- Số điện thoại -->
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    Số điện thoại <span class="text-red-500">*</span>
                                </label>
                                <input type="tel" 
                                       name="customer_phone" 
                                       value="<?php echo htmlspecialchars($customerPhone); ?>"
                                       required 
                                       placeholder="Nhập số điện thoại (VD: 0901234567)"
                                       class="w-full px-4 py-3 border-2 border-slate-300 rounded-lg focus:border-red-500 focus:outline-none">
                                <p class="text-xs text-slate-500 mt-1">
                                    💡 Để liên hệ nếu có vấn đề về thanh toán
                                </p>
                            </div>
                            
                            <!-- Email -->
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    Email
                                </label>
                                <input type="email" 
                                       name="customer_email" 
                                       value="<?php echo htmlspecialchars($customerEmail); ?>"
                                       placeholder="Nhập email của bạn (tùy chọn)"
                                       class="w-full px-4 py-3 border-2 border-slate-300 rounded-lg focus:border-red-500 focus:outline-none">
                                <p class="text-xs text-slate-500 mt-1">
                                    💡 Email tùy chọn, không bắt buộc
                                </p>
                            </div>
                            
                            <!-- Nội dung chuyển khoản -->
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    Nội dung chuyển khoản
                                </label>
                                <input type="text" 
                                       name="transfer_note" 
                                       value="<?php echo htmlspecialchars($transferNote); ?>"
                                       readonly
                                       class="w-full px-4 py-3 bg-gray-100 border-2 border-slate-300 rounded-lg">
                                <p class="text-xs text-slate-500 mt-1">
                                    <i class="fa-solid fa-info-circle mr-1"></i> Vui lòng ghi đúng nội dung này khi chuyển tiền
                                </p>
                            </div>
                        </div>
                    </div>
                    

                    <div class="bg-slate-50 rounded-lg p-4 text-xs text-slate-600">
                        <p class="mb-2">
                            <i class="fa-solid fa-shield-check text-green-600 mr-1"></i>

                        </p>
                        <p>
                            <i class="fa-solid fa-clock text-blue-600 mr-1"></i>
                            Hệ thống tự động kích hoạt sau 1-5 phút khi nhận được tiền
                        </p>
                    </div>
                    
                    <!-- Submit Button -->
                    <button type="submit" 
                            name="submit_order"
                            class="w-full bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-700 hover:to-pink-700 text-white font-black px-8 py-4 rounded-xl transition shadow-lg text-lg pulse-red">
                        <i class="fa-solid fa-check-circle mr-2"></i>
                        XÁC NHẬN ĐÃ CHUYỂN TIỀN
                    </button>
                </form>
                
                <!-- Support Info -->
                <div class="mt-6 text-center text-sm text-slate-600">
                    <p>
                        Cần hỗ trợ? 
                        <a href="tel:<?php echo SUPPORT_HOTLINE; ?>" class="text-blue-600 font-bold">
                            <i class="fa-solid fa-phone mr-1"></i> <?php echo SUPPORT_HOTLINE; ?>
                        </a>
                    </p>
                </div>
            </div>
            
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-slate-900 text-slate-300 py-8 mt-12">
        <div class="max-w-7xl mx-auto px-4 text-center">
         
            <p class="text-xs text-slate-500 mt-2">
                © 2026 HSHOP Analytics • 🎊 Chúc Mừng Năm Mới
            </p>
        </div>
    </footer>

</body>
</html>
