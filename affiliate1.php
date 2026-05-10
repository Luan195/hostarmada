<?php
// Proper session initialization
require_once 'includes/session.php';
require_once 'includes/functions.php';

// Require login
if (!isLoggedIn()) {
    redirect('login.php');
}

$currentUser = getCurrentUser();
$username = $_SESSION['username'];
$affiliateCode = $currentUser['affiliate_code'];
$earnings = $currentUser['earnings'] ?? 0;
$referredBy = $currentUser['referred_by'] ?? null;

// Auto-detect domain (no hard-coding!)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$domain = $_SERVER['HTTP_HOST'];
$baseUrl = $protocol . '://' . $domain;

// Affiliate is now available for ALL users (no VIP requirement!)
// Everyone who registers can be an affiliate

// Count referrals
$users = loadDB('users.json');
$myReferrals = array_filter($users, fn($u) => ($u['referred_by'] ?? '') === $affiliateCode);
$referralCount = count($myReferrals);

// Load payment info
$paymentInfo = $currentUser['payment_info'] ?? [];
$bankName = $paymentInfo['bank_name'] ?? '';
$bankAccount = $paymentInfo['bank_account'] ?? '';
$accountHolder = $paymentInfo['account_holder'] ?? '';
$phoneNumber = $paymentInfo['phone_number'] ?? '';

// Handle payment info update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_payment_info'])) {
    $users[$username]['payment_info'] = [
        'bank_name' => trim($_POST['bank_name'] ?? ''),
        'bank_account' => trim($_POST['bank_account'] ?? ''),
        'account_holder' => trim($_POST['account_holder'] ?? ''),
        'phone_number' => trim($_POST['phone_number'] ?? ''),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    saveDB('users.json', $users);
    $successMessage = 'Cập nhật thông tin thanh toán thành công!';
    
    // Reload data
    $currentUser = $users[$username];
    $paymentInfo = $currentUser['payment_info'] ?? [];
    $bankName = $paymentInfo['bank_name'] ?? '';
    $bankAccount = $paymentInfo['bank_account'] ?? '';
    $accountHolder = $paymentInfo['account_holder'] ?? '';
    $phoneNumber = $paymentInfo['phone_number'] ?? '';
}

// Handle payout request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_payout'])) {
    if ($earnings < MIN_PAYOUT) {
        $errorMessage = 'Số dư tối thiểu để rút tiền là $' . MIN_PAYOUT;
    } elseif (empty($bankName) || empty($bankAccount) || empty($accountHolder)) {
        $errorMessage = 'Vui lòng cập nhật đầy đủ thông tin thanh toán trước khi rút tiền!';
    } else {
        // Save payout request (admin will process manually)
        $payouts = file_exists(DATA_PATH . 'payouts.json') ? json_decode(file_get_contents(DATA_PATH . 'payouts.json'), true) : [];
        $payouts[] = [
            'id' => 'PAY-' . strtoupper(substr(md5(uniqid()), 0, 8)),
            'username' => $username,
            'amount' => $earnings,
            'payment_info' => $paymentInfo,
            'status' => 'pending',
            'requested_at' => date('Y-m-d H:i:s')
        ];
        file_put_contents(DATA_PATH . 'payouts.json', json_encode($payouts, JSON_PRETTY_PRINT));
        
        $successMessage = 'Yêu cầu rút tiền đã được gửi! Admin sẽ xử lý trong 24-48h.';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Affiliate Dashboard - HSHOP Analytics</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .gradient-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .shine {
            animation: shine 2s infinite;
        }
        @keyframes shine {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 to-slate-100 min-h-screen">

    <!-- Header -->
    <header class="bg-white/80 backdrop-blur-md border-b border-slate-200 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="index.php" class="flex items-center gap-3 group">
                <div class="bg-gradient-to-br from-red-600 to-red-700 text-white p-2 rounded-lg group-hover:scale-110 transition-transform">
                    <i class="fa-brands fa-youtube text-xl"></i>
                </div>
                <h1 class="font-extrabold text-lg text-slate-900">HSHOP <span class="text-red-600">Analytics</span></h1>
            </a>
            <div class="flex items-center gap-4">
                <a href="scanner.php" class="text-sm font-bold text-slate-600 hover:text-red-600 transition-colors">
                    <i class="fa-solid fa-radar mr-1"></i> Scanner
                </a>
                <a href="logout.php" class="text-sm font-bold text-slate-600 hover:text-red-600 transition-colors">
                    <i class="fa-solid fa-sign-out-alt mr-1"></i> Logout
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-8">
        
        <?php if (isset($successMessage)): ?>
        <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-xl mb-6 flex items-center gap-3">
            <i class="fa-solid fa-circle-check text-green-600 text-2xl"></i>
            <p class="text-green-800 font-bold"><?php echo $successMessage; ?></p>
        </div>
        <?php endif; ?>

        <?php if (isset($errorMessage)): ?>
        <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-xl mb-6 flex items-center gap-3">
            <i class="fa-solid fa-circle-xmark text-red-600 text-2xl"></i>
            <p class="text-red-800 font-bold"><?php echo $errorMessage; ?></p>
        </div>
        <?php endif; ?>

        <!-- Page Title -->
        <div class="mb-8">
            <h1 class="text-5xl font-black text-slate-900 mb-2">💰 Affiliate Dashboard</h1>
            <p class="text-slate-600 text-lg">Kiếm tiền với chương trình giới thiệu 20% hoa hồng - Mở cho tất cả thành viên!</p>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Total Earnings -->
            <div class="bg-white p-6 rounded-2xl shadow-lg border-2 border-green-200 hover:shadow-xl transition-shadow">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-bold text-slate-600 text-sm uppercase tracking-wide">Total Earnings</h3>
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="fa-solid fa-dollar-sign text-2xl text-green-600"></i>
                    </div>
                </div>
                <p class="text-5xl font-black text-green-600 mb-1">$<?php echo number_format($earnings, 2); ?></p>
                <p class="text-sm text-slate-500">Current balance</p>
                <?php if ($earnings >= MIN_PAYOUT): ?>
                <button onclick="document.getElementById('payoutModal').classList.remove('hidden')" 
                        class="mt-4 w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition">
                    <i class="fa-solid fa-money-bill-transfer mr-2"></i>Request Payout
                </button>
                <?php else: ?>
                <p class="mt-4 text-xs text-slate-400">Min. $<?php echo MIN_PAYOUT; ?> to withdraw</p>
                <?php endif; ?>
            </div>

            <!-- Total Referrals -->
            <div class="bg-white p-6 rounded-2xl shadow-lg border-2 border-blue-200 hover:shadow-xl transition-shadow">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-bold text-slate-600 text-sm uppercase tracking-wide">Total Referrals</h3>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="fa-solid fa-users text-2xl text-blue-600"></i>
                    </div>
                </div>
                <p class="text-5xl font-black text-blue-600 mb-1"><?php echo $referralCount; ?></p>
                <p class="text-sm text-slate-500">People you referred</p>
            </div>

            <!-- Commission Rate -->
            <div class="bg-white p-6 rounded-2xl shadow-lg border-2 border-purple-200 hover:shadow-xl transition-shadow">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-bold text-slate-600 text-sm uppercase tracking-wide">Commission Rate</h3>
                    <div class="bg-purple-100 p-3 rounded-full">
                        <i class="fa-solid fa-percentage text-2xl text-purple-600"></i>
                    </div>
                </div>
                <p class="text-5xl font-black text-purple-600 mb-1"><?php echo (AFFILIATE_COMMISSION * 100); ?>%</p>
                <p class="text-sm text-slate-500">On all referral sales</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <!-- Left Column: Affiliate Link + Payment Info -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Affiliate Link Card -->
                <div class="gradient-card text-white p-8 rounded-2xl shadow-2xl">
                    <h2 class="text-3xl font-black mb-4 flex items-center gap-3">
                        <i class="fa-solid fa-link"></i> Your Referral Link
                    </h2>
                    <p class="text-white/90 mb-6">Share this clean, professional link to earn 20% commission!</p>
                    
                    <!-- Short Link Display -->
                    <div class="bg-white/20 backdrop-blur-sm p-5 rounded-xl mb-3 border border-white/30">
                        <div class="flex items-center gap-3 mb-2">
                            <i class="fa-solid fa-star text-yellow-300"></i>
                            <span class="text-xs font-bold text-yellow-300 uppercase">Short Link (Professional)</span>
                        </div>
                        <code class="text-white font-mono text-lg font-bold break-all block leading-relaxed" id="affiliateLink"><?php echo $baseUrl; ?>/ref/<?php echo $affiliateCode; ?></code>
                    </div>
                    
                    <!-- Original Link (Collapsed) -->
                    <details class="mb-5">
                        <summary class="text-xs text-white/60 cursor-pointer hover:text-white/80 transition">
                            <i class="fa-solid fa-chevron-down text-[10px] mr-1"></i> Show original link
                        </summary>
                        <div class="bg-white/10 backdrop-blur-sm p-3 rounded-lg mt-2 border border-white/20">
                            <code class="text-white/70 font-mono text-xs break-all block"><?php echo $baseUrl; ?>/login.php?mode=register&ref=<?php echo $affiliateCode; ?></code>
                        </div>
                    </details>
                    
                    <button onclick="copyAffiliateLink()" class="bg-white hover:bg-slate-100 text-purple-700 font-black px-8 py-4 rounded-xl transition-all transform hover:scale-105 shadow-lg w-full">
                        <i class="fa-solid fa-copy mr-2"></i> Copy Short Link to Clipboard
                    </button>
                </div>

                <!-- Payment Information Form -->
                <div class="bg-white p-8 rounded-2xl shadow-lg border-2 border-slate-200">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="bg-blue-100 p-3 rounded-full">
                            <i class="fa-solid fa-building-columns text-blue-600 text-2xl"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-black text-slate-900">Thông Tin Thanh Toán</h2>
                            <p class="text-slate-600 text-sm">Cập nhật thông tin để nhận tiền hoa hồng</p>
                        </div>
                    </div>

                    <form method="POST" class="space-y-5">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                <i class="fa-solid fa-building-columns text-slate-500 mr-1"></i> Tên Ngân Hàng *
                            </label>
                            <select name="bank_name" required 
                                    class="w-full px-4 py-3 border-2 border-slate-300 rounded-xl focus:border-blue-500 focus:ring focus:ring-blue-200 transition">
                                <option value="">-- Chọn ngân hàng --</option>
                                <option value="Vietcombank" <?php echo $bankName === 'Vietcombank' ? 'selected' : ''; ?>>Vietcombank</option>
                                <option value="VietinBank" <?php echo $bankName === 'VietinBank' ? 'selected' : ''; ?>>VietinBank</option>
                                <option value="BIDV" <?php echo $bankName === 'BIDV' ? 'selected' : ''; ?>>BIDV</option>
                                <option value="Agribank" <?php echo $bankName === 'Agribank' ? 'selected' : ''; ?>>Agribank</option>
                                <option value="MB Bank" <?php echo $bankName === 'MB Bank' ? 'selected' : ''; ?>>MB Bank</option>
                                <option value="Techcombank" <?php echo $bankName === 'Techcombank' ? 'selected' : ''; ?>>Techcombank</option>
                                <option value="ACB" <?php echo $bankName === 'ACB' ? 'selected' : ''; ?>>ACB</option>
                                <option value="VPBank" <?php echo $bankName === 'VPBank' ? 'selected' : ''; ?>>VPBank</option>
                                <option value="TPBank" <?php echo $bankName === 'TPBank' ? 'selected' : ''; ?>>TPBank</option>
                                <option value="Sacombank" <?php echo $bankName === 'Sacombank' ? 'selected' : ''; ?>>Sacombank</option>
                                <option value="HDBank" <?php echo $bankName === 'HDBank' ? 'selected' : ''; ?>>HDBank</option>
                                <option value="SHB" <?php echo $bankName === 'SHB' ? 'selected' : ''; ?>>SHB</option>
                                <option value="VIB" <?php echo $bankName === 'VIB' ? 'selected' : ''; ?>>VIB</option>
                                <option value="MSB" <?php echo $bankName === 'MSB' ? 'selected' : ''; ?>>MSB</option>
                                <option value="OCB" <?php echo $bankName === 'OCB' ? 'selected' : ''; ?>>OCB</option>
                                <option value="Khác" <?php echo $bankName === 'Khác' ? 'selected' : ''; ?>>Ngân hàng khác</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                <i class="fa-solid fa-credit-card text-slate-500 mr-1"></i> Số Tài Khoản *
                            </label>
                            <input type="text" name="bank_account" value="<?php echo htmlspecialchars($bankAccount); ?>" 
                                   placeholder="Nhập số tài khoản ngân hàng" required
                                   class="w-full px-4 py-3 border-2 border-slate-300 rounded-xl focus:border-blue-500 focus:ring focus:ring-blue-200 transition">
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                <i class="fa-solid fa-user text-slate-500 mr-1"></i> Chủ Tài Khoản *
                            </label>
                            <input type="text" name="account_holder" value="<?php echo htmlspecialchars($accountHolder); ?>" 
                                   placeholder="NGUYEN VAN A (viết hoa không dấu)" required
                                   class="w-full px-4 py-3 border-2 border-slate-300 rounded-xl focus:border-blue-500 focus:ring focus:ring-blue-200 transition">
                            <p class="text-xs text-slate-500 mt-1">* Viết hoa, không dấu (giống tên trên thẻ ATM)</p>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                <i class="fa-solid fa-phone text-slate-500 mr-1"></i> Số Điện Thoại *
                            </label>
                            <input type="tel" name="phone_number" value="<?php echo htmlspecialchars($phoneNumber); ?>" 
                                   placeholder="0912345678" required
                                   class="w-full px-4 py-3 border-2 border-slate-300 rounded-xl focus:border-blue-500 focus:ring focus:ring-blue-200 transition">
                        </div>

                        <button type="submit" name="update_payment_info" 
                                class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-black py-4 px-6 rounded-xl transition-all transform hover:scale-105 shadow-lg">
                            <i class="fa-solid fa-floppy-disk mr-2"></i> Lưu Thông Tin Thanh Toán
                        </button>
                    </form>
                </div>

            </div>

            <!-- Right Column: How It Works -->
            <div class="space-y-6">
                
                <!-- How It Works -->
                <div class="bg-white p-6 rounded-2xl shadow-lg border-2 border-slate-200">
                    <h2 class="text-xl font-black mb-6 text-slate-900">🚀 How Affiliate Works</h2>
                    <div class="space-y-6">
                        <div class="flex gap-4">
                            <div class="flex-shrink-0">
                                <div class="bg-blue-100 w-12 h-12 rounded-full flex items-center justify-center">
                                    <i class="fa-solid fa-share-nodes text-blue-600 text-lg"></i>
                                </div>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900 mb-1">1. Share Link</h3>
                                <p class="text-slate-600 text-sm">Share your referral link on social media, YouTube, or with friends.</p>
                            </div>
                        </div>

                        <div class="flex gap-4">
                            <div class="flex-shrink-0">
                                <div class="bg-green-100 w-12 h-12 rounded-full flex items-center justify-center">
                                    <i class="fa-solid fa-user-plus text-green-600 text-lg"></i>
                                </div>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900 mb-1">2. They Sign Up</h3>
                                <p class="text-slate-600 text-sm">When someone registers via your link, they become your referral.</p>
                            </div>
                        </div>

                        <div class="flex gap-4">
                            <div class="flex-shrink-0">
                                <div class="bg-purple-100 w-12 h-12 rounded-full flex items-center justify-center">
                                    <i class="fa-solid fa-money-bill-wave text-purple-600 text-lg"></i>
                                </div>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900 mb-1">3. Earn 20%</h3>
                                <p class="text-slate-600 text-sm">Get 20% commission when they upgrade to Basic or VIP!</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tips Card -->
                <div class="bg-gradient-to-br from-yellow-50 to-orange-50 border-2 border-orange-200 p-6 rounded-2xl">
                    <h3 class="font-bold text-orange-900 mb-4 flex items-center gap-2 text-lg">
                        <i class="fa-solid fa-lightbulb"></i> Pro Tips
                    </h3>
                    <ul class="space-y-3 text-orange-800 text-sm">
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-orange-500 mt-1"></i>
                            <span>Share in YouTube video descriptions</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-orange-500 mt-1"></i>
                            <span>Post in Facebook creator groups</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-orange-500 mt-1"></i>
                            <span>Create tutorial videos</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-orange-500 mt-1"></i>
                            <span>Join creator communities</span>
                        </li>
                    </ul>
                </div>

            </div>
        </div>

        <!-- My Referrals Table -->
        <div class="bg-white rounded-2xl shadow-lg border-2 border-slate-200 p-8">
            <h2 class="text-3xl font-black mb-6 text-slate-900">👥 My Referrals (<?php echo $referralCount; ?>)</h2>
            
            <?php if (empty($myReferrals)): ?>
            <div class="text-center py-16 text-slate-400">
                <i class="fa-solid fa-users-slash text-6xl mb-4 opacity-50"></i>
                <p class="text-xl font-bold text-slate-600">No referrals yet</p>
                <p class="text-sm text-slate-500 mt-2">Start sharing your link to earn commissions!</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b-2 border-slate-200">
                        <tr class="bg-slate-50">
                            <th class="py-4 px-4 font-black text-slate-700 uppercase text-xs">Username</th>
                            <th class="py-4 px-4 font-black text-slate-700 uppercase text-xs">Email</th>
                            <th class="py-4 px-4 font-black text-slate-700 uppercase text-xs">Tier</th>
                            <th class="py-4 px-4 font-black text-slate-700 uppercase text-xs">Joined</th>
                            <th class="py-4 px-4 font-black text-slate-700 uppercase text-xs text-right">Commission</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($myReferrals as $referral): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="py-4 px-4 font-bold text-slate-900"><?php echo htmlspecialchars($referral['username']); ?></td>
                            <td class="py-4 px-4 text-slate-600"><?php echo htmlspecialchars($referral['email']); ?></td>
                            <td class="py-4 px-4"><?php echo getTierBadge($referral['tier']); ?></td>
                            <td class="py-4 px-4 text-slate-600"><?php echo date('d/m/Y', strtotime($referral['created_at'])); ?></td>
                            <td class="py-4 px-4 text-right font-black text-lg text-green-600">
                                <?php 
                                if ($referral['tier'] === TIER_VIP) {
                                    echo '$' . number_format(PRICE_VIP_MONTHLY * AFFILIATE_COMMISSION, 2);
                                } elseif ($referral['tier'] === TIER_BASIC) {
                                    echo '$' . number_format(PRICE_BASIC_MONTHLY * AFFILIATE_COMMISSION, 2);
                                } else {
                                    echo '<span class="text-slate-400">$0.00</span>';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="border-t-2 border-slate-200 bg-slate-50">
                        <tr>
                            <td colspan="4" class="py-4 px-4 font-black text-slate-900 uppercase text-sm">TOTAL EARNINGS</td>
                            <td class="py-4 px-4 text-right font-black text-2xl text-green-600">$<?php echo number_format($earnings, 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php endif; ?>
        </div>

    </main>

    <!-- Payout Request Modal -->
    <div id="payoutModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-8">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-black text-slate-900">Request Payout</h3>
                <button onclick="document.getElementById('payoutModal').classList.add('hidden')" 
                        class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-times text-2xl"></i>
                </button>
            </div>

            <?php if (empty($bankName) || empty($bankAccount) || empty($accountHolder)): ?>
            <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded-lg mb-6">
                <p class="text-yellow-800 text-sm">
                    <i class="fa-solid fa-exclamation-triangle mr-2"></i>
                    Vui lòng cập nhật đầy đủ thông tin thanh toán trước!
                </p>
            </div>
            <?php else: ?>
            <div class="mb-6">
                <div class="bg-green-50 border-2 border-green-200 rounded-xl p-4 mb-4">
                    <p class="text-sm text-slate-600 mb-2">Amount to withdraw:</p>
                    <p class="text-4xl font-black text-green-600">$<?php echo number_format($earnings, 2); ?></p>
                </div>
                
                <div class="bg-slate-50 rounded-xl p-4 text-sm space-y-2">
                    <p class="text-slate-600"><strong>Bank:</strong> <?php echo htmlspecialchars($bankName); ?></p>
                    <p class="text-slate-600"><strong>Account:</strong> <?php echo htmlspecialchars($bankAccount); ?></p>
                    <p class="text-slate-600"><strong>Holder:</strong> <?php echo htmlspecialchars($accountHolder); ?></p>
                    <p class="text-slate-600"><strong>Phone:</strong> <?php echo htmlspecialchars($phoneNumber); ?></p>
                </div>
            </div>

            <form method="POST">
                <button type="submit" name="request_payout" 
                        class="w-full bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-black py-4 px-6 rounded-xl transition-all transform hover:scale-105 shadow-lg">
                    <i class="fa-solid fa-paper-plane mr-2"></i> Submit Payout Request
                </button>
            </form>
            <p class="text-xs text-slate-500 mt-4 text-center">Admin will process within 24-48 hours</p>
            <?php endif; ?>
        </div>
    </div>

    <footer class="bg-slate-900 text-slate-400 py-8 mt-16">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-sm">© <?php echo date('Y'); ?> HSHOP Media. Hotline: <strong class="text-red-500"><?php echo SUPPORT_HOTLINE; ?></strong></p>
        </div>
    </footer>

    <script>
        function copyAffiliateLink() {
            const link = document.getElementById('affiliateLink').innerText.trim();
            navigator.clipboard.writeText(link).then(() => {
                // Show toast notification
                const toast = document.createElement('div');
                toast.className = 'fixed bottom-6 right-6 bg-green-600 text-white px-6 py-4 rounded-xl shadow-2xl z-50 font-bold';
                toast.innerHTML = '<i class="fa-solid fa-check mr-2"></i>Link copied to clipboard!';
                document.body.appendChild(toast);
                
                setTimeout(() => {
                    toast.remove();
                }, 3000);
            }).catch(err => {
                alert('Failed to copy: ' + err);
            });
        }

        // Close modal when clicking outside
        document.getElementById('payoutModal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });
    </script>

</body>
</html>
