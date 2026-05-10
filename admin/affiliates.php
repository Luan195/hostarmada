<?php
// Proper session initialization
require_once '../includes/session.php';
require_once '../includes/functions.php';

// Check admin access
if (!isAdmin()) {
    redirect('login.php');
}

$message = '';

// Handle Payout Approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $payoutId = sanitize($_POST['payout_id'] ?? '');
    $payouts = loadDB('payouts.json');
    
    if ($_POST['action'] === 'approve_payout' && isset($payouts[$payoutId])) {
        $payouts[$payoutId]['status'] = 'approved';
        $payouts[$payoutId]['approved_at'] = date('Y-m-d H:i:s');
        $payouts[$payoutId]['approved_by'] = $_SESSION['username'];
        
        // Deduct from user's earnings
        $users = loadDB('users.json');
        $username = $payouts[$payoutId]['username'];
        if (isset($users[$username])) {
            $users[$username]['earnings'] = max(0, ($users[$username]['earnings'] ?? 0) - $payouts[$payoutId]['amount']);
            $users[$username]['total_withdrawn'] = ($users[$username]['total_withdrawn'] ?? 0) + $payouts[$payoutId]['amount'];
            saveDB('users.json', $users);
        }
        saveDB('payouts.json', $payouts);
        $message = "✅ Đã duyệt yêu cầu rút tiền #{$payoutId}!";
    } elseif ($_POST['action'] === 'reject_payout' && isset($payouts[$payoutId])) {
        $payouts[$payoutId]['status'] = 'rejected';
        $payouts[$payoutId]['rejected_at'] = date('Y-m-d H:i:s');
        $payouts[$payoutId]['rejected_by'] = $_SESSION['username'];
        saveDB('payouts.json', $payouts);
        $message = "❌ Đã từ chối yêu cầu rút tiền #{$payoutId}!";
    }
}

// Load data
$users = loadDB('users.json');
$orders = loadDB('orders.json');
$payouts = loadDB('payouts.json');

// =====================================================
// 💰 AFFILIATE STATISTICS
// =====================================================
$affiliates = [];
$totalCommission = 0;
$totalWithdrawn = 0;
$pendingPayouts = 0;

foreach ($users as $username => $user) {
    $earnings = $user['earnings'] ?? 0;
    $referrals = $user['total_referrals'] ?? 0;
    $withdrawn = $user['total_withdrawn'] ?? 0;
    
    if ($referrals > 0 || $earnings > 0 || $withdrawn > 0) {
        $affiliates[$username] = [
            'username' => $username,
            'email' => $user['email'] ?? '',
            'tier' => $user['tier'] ?? 'free',
            'affiliate_code' => $user['affiliate_code'] ?? '',
            'earnings' => $earnings,
            'withdrawn' => $withdrawn,
            'referrals' => $referrals,
            'payment_info' => $user['payment_info'] ?? null,
            'created_at' => $user['created_at'] ?? ''
        ];
        $totalCommission += $earnings + $withdrawn;
        $totalWithdrawn += $withdrawn;
    }
}

// Sort by earnings
uasort($affiliates, fn($a, $b) => ($b['earnings'] + $b['withdrawn']) - ($a['earnings'] + $a['withdrawn']));

// Pending payouts
$pendingPayoutsList = array_filter($payouts, fn($p) => ($p['status'] ?? '') === 'pending');
$pendingPayouts = count($pendingPayoutsList);

// Search & Filter
$search = $_GET['search'] ?? '';
$filterTier = $_GET['tier'] ?? '';

if (!empty($search)) {
    $affiliates = array_filter($affiliates, function($aff) use ($search) {
        return stripos($aff['username'], $search) !== false ||
               stripos($aff['email'], $search) !== false ||
               stripos($aff['affiliate_code'], $search) !== false;
    });
}

if (!empty($filterTier)) {
    $affiliates = array_filter($affiliates, fn($a) => $a['tier'] === $filterTier);
}

// Pending orders for sidebar
$pendingOrders = count(array_filter($orders, fn($o) => ($o['status'] ?? '') === 'pending'));

$currentPage = 'affiliates';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Cộng Tác Viên - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .sidebar { width: 260px; min-height: 100vh; }
        .main-content { margin-left: 260px; }
        .nav-item { transition: all 0.2s ease; }
        .nav-item:hover { background: linear-gradient(90deg, rgba(99,102,241,0.1) 0%, transparent 100%); }
        .nav-item.active { background: linear-gradient(90deg, #6366f1 0%, #8b5cf6 100%); color: white !important; }
        .nav-item.active i, .nav-item.active span { color: white !important; }
        @media (max-width: 1024px) { .sidebar { display: none; } .main-content { margin-left: 0; } }
    </style>
</head>
<body class="bg-slate-100">

    <!-- Sidebar -->
    <aside class="sidebar fixed left-0 top-0 bg-slate-900 text-white z-40">
        <div class="p-6 border-b border-slate-700">
            <h1 class="text-xl font-black flex items-center gap-2">
                <i class="fa-solid fa-shield-halved text-yellow-400"></i>
                Admin Panel
            </h1>
        </div>
        
        <nav class="p-4 space-y-2">
            <a href="index.php" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:text-white">
                <i class="fa-solid fa-gauge-high w-5"></i><span>Dashboard</span>
            </a>
            <a href="orders.php" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:text-white">
                <i class="fa-solid fa-shopping-cart w-5"></i><span>Đơn Hàng</span>
                <?php if ($pendingOrders > 0): ?><span class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full"><?php echo $pendingOrders; ?></span><?php endif; ?>
            </a>
            <a href="users.php" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:text-white">
                <i class="fa-solid fa-users w-5"></i><span>Quản Lý Users</span>
            </a>
            <a href="affiliates.php" class="nav-item active flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:text-white">
                <i class="fa-solid fa-handshake w-5"></i><span>Cộng Tác Viên</span>
                <?php if ($pendingPayouts > 0): ?><span class="ml-auto bg-orange-500 text-white text-xs font-bold px-2 py-0.5 rounded-full"><?php echo $pendingPayouts; ?></span><?php endif; ?>
            </a>
            <a href="reports.php" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:text-white">
                <i class="fa-solid fa-chart-line w-5"></i><span>Báo Cáo</span>
            </a>
            <div class="border-t border-slate-700 my-4 pt-4"></div>
            <a href="keys.php" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:text-white">
                <i class="fa-solid fa-key w-5"></i><span>API Keys Pool</span>
            </a>
            <a href="api_keys_overview.php" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:text-white">
                <i class="fa-solid fa-eye w-5"></i><span>Keys Overview</span>
            </a>
        </nav>
        
        <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-slate-700">
            <a href="logout.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-red-400 hover:bg-red-500/10">
                <i class="fa-solid fa-sign-out-alt w-5"></i><span>Đăng Xuất</span>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content min-h-screen">
        <header class="bg-white shadow-sm px-6 py-4 sticky top-0 z-30">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-black text-slate-800">Quản Lý Cộng Tác Viên</h1>
                    <p class="text-sm text-slate-500"><?php echo count($affiliates); ?> CTV đang hoạt động</p>
                </div>
            </div>
        </header>

        <div class="p-6">
            <?php if ($message): ?>
            <div class="mb-4 bg-green-50 border-l-4 border-green-500 p-4 rounded-lg">
                <p class="text-green-700"><?php echo $message; ?></p>
            </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-gradient-to-br from-yellow-400 to-orange-500 rounded-xl p-6 text-white shadow-lg">
                    <div class="flex items-center justify-between mb-2">
                        <i class="fa-solid fa-users text-3xl opacity-60"></i>
                    </div>
                    <div class="text-3xl font-black"><?php echo count($affiliates); ?></div>
                    <div class="text-sm opacity-90">Tổng CTV</div>
                </div>

                <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl p-6 text-white shadow-lg">
                    <div class="flex items-center justify-between mb-2">
                        <i class="fa-solid fa-dollar-sign text-3xl opacity-60"></i>
                    </div>
                    <div class="text-3xl font-black"><?php echo number_format($totalCommission, 0, ',', '.'); ?>đ</div>
                    <div class="text-sm opacity-90">Tổng Hoa Hồng (All-time)</div>
                </div>

                <div class="bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl p-6 text-white shadow-lg">
                    <div class="flex items-center justify-between mb-2">
                        <i class="fa-solid fa-money-bill-transfer text-3xl opacity-60"></i>
                    </div>
                    <div class="text-3xl font-black"><?php echo number_format($totalWithdrawn, 0, ',', '.'); ?>đ</div>
                    <div class="text-sm opacity-90">Đã Chi Trả</div>
                </div>

                <div class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl p-6 text-white shadow-lg">
                    <div class="flex items-center justify-between mb-2">
                        <i class="fa-solid fa-clock text-3xl opacity-60"></i>
                    </div>
                    <div class="text-3xl font-black"><?php echo $pendingPayouts; ?></div>
                    <div class="text-sm opacity-90">Yêu Cầu Rút Tiền Chờ</div>
                </div>
            </div>

            <!-- Pending Payouts -->
            <?php if (!empty($pendingPayoutsList)): ?>
            <div class="bg-white rounded-xl shadow-sm p-6 mb-6 border-l-4 border-orange-500">
                <h2 class="text-xl font-black mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-clock text-orange-500"></i>
                    Yêu Cầu Rút Tiền Chờ Duyệt (<?php echo $pendingPayouts; ?>)
                </h2>
                <div class="space-y-4">
                    <?php foreach ($pendingPayoutsList as $payoutId => $payout): ?>
                    <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                        <div class="grid md:grid-cols-4 gap-4 items-center">
                            <div>
                                <div class="text-xs text-slate-500">CTV</div>
                                <div class="font-bold text-lg"><?php echo htmlspecialchars($payout['username']); ?></div>
                            </div>
                            <div>
                                <div class="text-xs text-slate-500">Số tiền</div>
                                <div class="font-black text-lg text-green-600"><?php echo number_format($payout['amount'], 0, ',', '.'); ?>đ</div>
                            </div>
                            <div>
                                <div class="text-xs text-slate-500">Thông tin thanh toán</div>
                                <div class="text-sm">
                                    <?php if (!empty($payout['payment_info'])): ?>
                                    <span class="font-bold"><?php echo htmlspecialchars($payout['payment_info']['bank_name'] ?? ''); ?></span><br>
                                    <span class="font-mono"><?php echo htmlspecialchars($payout['payment_info']['bank_account'] ?? ''); ?></span><br>
                                    <span><?php echo htmlspecialchars($payout['payment_info']['account_holder'] ?? ''); ?></span>
                                    <?php else: ?>
                                    <span class="text-red-500">Chưa có thông tin</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <form method="POST" class="inline">
                                    <input type="hidden" name="payout_id" value="<?php echo $payoutId; ?>">
                                    <button type="submit" name="action" value="approve_payout" 
                                            onclick="return confirm('Xác nhận đã chuyển tiền cho CTV?')"
                                            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-bold text-sm">
                                        <i class="fa-solid fa-check mr-1"></i> Duyệt
                                    </button>
                                </form>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="payout_id" value="<?php echo $payoutId; ?>">
                                    <button type="submit" name="action" value="reject_payout"
                                            onclick="return confirm('Từ chối yêu cầu rút tiền này?')"
                                            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-bold text-sm">
                                        <i class="fa-solid fa-times mr-1"></i> Từ chối
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Search & Filter -->
            <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
                <form method="GET" class="flex flex-wrap gap-4 items-end">
                    <div class="flex-1 min-w-[250px]">
                        <label class="block text-xs font-bold text-slate-600 mb-1">Tìm kiếm</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Username, email, mã affiliate..."
                               class="w-full px-4 py-2 border border-slate-300 rounded-lg">
                    </div>
                    <div class="w-40">
                        <label class="block text-xs font-bold text-slate-600 mb-1">Tier</label>
                        <select name="tier" class="w-full px-3 py-2 border border-slate-300 rounded-lg">
                            <option value="">Tất cả</option>
                            <option value="vip" <?php echo $filterTier === 'vip' ? 'selected' : ''; ?>>VIP</option>
                            <option value="basic" <?php echo $filterTier === 'basic' ? 'selected' : ''; ?>>Basic</option>
                            <option value="trial" <?php echo $filterTier === 'trial' ? 'selected' : ''; ?>>Trial</option>
                            <option value="free" <?php echo $filterTier === 'free' ? 'selected' : ''; ?>>Free</option>
                        </select>
                    </div>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg font-bold">
                        <i class="fa-solid fa-filter mr-1"></i> Lọc
                    </button>
                    <a href="affiliates.php" class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-4 py-2 rounded-lg font-bold">Reset</a>
                </form>
            </div>

            <!-- Affiliates Table -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gradient-to-r from-yellow-400 to-orange-500 text-white">
                            <tr>
                                <th class="px-4 py-3 text-left font-bold">#</th>
                                <th class="px-4 py-3 text-left font-bold">CTV</th>
                                <th class="px-4 py-3 text-left font-bold">Mã Giới Thiệu</th>
                                <th class="px-4 py-3 text-left font-bold">Tier</th>
                                <th class="px-4 py-3 text-center font-bold">Referrals</th>
                                <th class="px-4 py-3 text-right font-bold">Hoa Hồng</th>
                                <th class="px-4 py-3 text-right font-bold">Đã Rút</th>
                                <th class="px-4 py-3 text-right font-bold">Còn Lại</th>
                                <th class="px-4 py-3 text-left font-bold">Thanh Toán</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <?php 
                            $rank = 1;
                            foreach ($affiliates as $aff): 
                                $totalEarned = $aff['earnings'] + $aff['withdrawn'];
                            ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <?php if ($rank === 1): ?>
                                    <span class="w-8 h-8 bg-yellow-400 text-white rounded-full flex items-center justify-center font-black">
                                        <i class="fa-solid fa-crown"></i>
                                    </span>
                                    <?php elseif ($rank === 2): ?>
                                    <span class="w-8 h-8 bg-slate-300 text-white rounded-full flex items-center justify-center font-black">2</span>
                                    <?php elseif ($rank === 3): ?>
                                    <span class="w-8 h-8 bg-orange-400 text-white rounded-full flex items-center justify-center font-black">3</span>
                                    <?php else: ?>
                                    <span class="text-slate-400 font-bold"><?php echo $rank; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-bold text-slate-800"><?php echo htmlspecialchars($aff['username']); ?></div>
                                    <div class="text-xs text-slate-500"><?php echo htmlspecialchars($aff['email']); ?></div>
                                </td>
                                <td class="px-4 py-3">
                                    <code class="bg-slate-100 px-2 py-1 rounded text-xs font-bold"><?php echo $aff['affiliate_code']; ?></code>
                                </td>
                                <td class="px-4 py-3"><?php echo getTierBadge($aff['tier']); ?></td>
                                <td class="px-4 py-3 text-center">
                                    <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full font-bold"><?php echo $aff['referrals']; ?></span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span class="font-bold text-slate-600"><?php echo number_format($totalEarned, 0, ',', '.'); ?>đ</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span class="text-orange-600"><?php echo number_format($aff['withdrawn'], 0, ',', '.'); ?>đ</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span class="font-black text-green-600"><?php echo number_format($aff['earnings'], 0, ',', '.'); ?>đ</span>
                                </td>
                                <td class="px-4 py-3">
                                    <?php if (!empty($aff['payment_info'])): ?>
                                    <div class="text-xs">
                                        <div class="font-bold"><?php echo htmlspecialchars($aff['payment_info']['bank_name'] ?? '-'); ?></div>
                                        <div class="font-mono text-slate-600"><?php echo htmlspecialchars($aff['payment_info']['bank_account'] ?? '-'); ?></div>
                                    </div>
                                    <?php else: ?>
                                    <span class="text-xs text-slate-400">Chưa cập nhật</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php 
                            $rank++;
                            endforeach; 
                            ?>
                        </tbody>
                    </table>
                </div>

                <?php if (empty($affiliates)): ?>
                <div class="p-8 text-center text-slate-500">
                    <i class="fa-solid fa-user-slash text-4xl mb-3 text-slate-300"></i>
                    <p>Chưa có cộng tác viên nào</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Info Panel -->
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-xl p-6">
                <h3 class="font-black text-blue-800 mb-3"><i class="fa-solid fa-info-circle mr-2"></i>Thông Tin Chương Trình CTV</h3>
                <div class="grid md:grid-cols-3 gap-4 text-sm text-blue-800">
                    <div>
                        <div class="font-bold mb-1">💰 Hoa Hồng</div>
                        <p>20% giá trị đơn hàng của người được giới thiệu</p>
                    </div>
                    <div>
                        <div class="font-bold mb-1">🎯 Điều Kiện</div>
                        <p>CTV phải là VIP user mới được nhận hoa hồng</p>
                    </div>
                    <div>
                        <div class="font-bold mb-1">💸 Rút Tiền</div>
                        <p>Tối thiểu 100.000đ, xử lý trong 24-48h</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

</body>
</html>
