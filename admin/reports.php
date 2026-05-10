<?php
// Proper session initialization
require_once '../includes/session.php';
require_once '../includes/functions.php';

// Check admin access
if (!isAdmin()) {
    redirect('login.php');
}

// =====================================================
// 📅 DATE RANGE FILTER
// =====================================================
$filterType = $_GET['filter'] ?? 'month';
$customFrom = $_GET['from'] ?? '';
$customTo = $_GET['to'] ?? '';

$today = date('Y-m-d');
$thisWeekStart = date('Y-m-d', strtotime('monday this week'));
$thisMonthStart = date('Y-m-01');
$thisYearStart = date('Y-01-01');

switch ($filterType) {
    case 'day':
        $dateFrom = $today;
        $dateTo = $today;
        $periodLabel = 'Hôm nay (' . date('d/m/Y') . ')';
        break;
    case 'week':
        $dateFrom = $thisWeekStart;
        $dateTo = $today;
        $periodLabel = 'Tuần này';
        break;
    case 'month':
        $dateFrom = $thisMonthStart;
        $dateTo = $today;
        $periodLabel = 'Tháng ' . date('m/Y');
        break;
    case 'year':
        $dateFrom = $thisYearStart;
        $dateTo = $today;
        $periodLabel = 'Năm ' . date('Y');
        break;
    case 'custom':
        $dateFrom = $customFrom ?: $thisMonthStart;
        $dateTo = $customTo ?: $today;
        $periodLabel = date('d/m/Y', strtotime($dateFrom)) . ' - ' . date('d/m/Y', strtotime($dateTo));
        break;
    default:
        $dateFrom = $thisMonthStart;
        $dateTo = $today;
        $periodLabel = 'Tháng ' . date('m/Y');
}

// Load data
$users = loadDB('users.json');
$orders = loadDB('orders.json');

// =====================================================
// 📊 CALCULATE STATISTICS
// =====================================================
$approvedOrders = array_filter($orders, fn($o) => ($o['status'] ?? '') === 'approved');

// Revenue calculations
$totalRevenue = 0;
$filteredRevenue = 0;
$filteredOrders = [];

foreach ($approvedOrders as $orderId => $order) {
    $amount = $order['amount'] ?? 0;
    $totalRevenue += $amount;
    
    if (!empty($order['approved_at'])) {
        $orderDate = date('Y-m-d', strtotime($order['approved_at']));
        if ($orderDate >= $dateFrom && $orderDate <= $dateTo) {
            $filteredRevenue += $amount;
            $filteredOrders[$orderId] = $order;
        }
    }
}

// User statistics
$totalUsers = count($users);
$newUsersInPeriod = 0;
$filteredUsersList = [];

$tierStats = ['free' => 0, 'trial' => 0, 'basic' => 0, 'vip' => 0, 'admin' => 0];

foreach ($users as $username => $user) {
    $tier = $user['tier'] ?? 'free';
    $tierStats[$tier]++;
    
    if (!empty($user['created_at'])) {
        $createdDate = date('Y-m-d', strtotime($user['created_at']));
        if ($createdDate >= $dateFrom && $createdDate <= $dateTo) {
            $newUsersInPeriod++;
            $filteredUsersList[$username] = $user;
        }
    }
}

$paidUsers = $tierStats['trial'] + $tierStats['basic'] + $tierStats['vip'];
$conversionRate = $totalUsers > 0 ? ($paidUsers / $totalUsers) * 100 : 0;

// Plan breakdown
$planBreakdown = ['trial' => 0, '1m' => 0, '3m' => 0, '6m' => 0, '12m' => 0];
$planRevenue = ['trial' => 0, '1m' => 0, '3m' => 0, '6m' => 0, '12m' => 0];

foreach ($filteredOrders as $order) {
    $plan = $order['plan'] ?? '';
    if (isset($planBreakdown[$plan])) {
        $planBreakdown[$plan]++;
        $planRevenue[$plan] += $order['amount'] ?? 0;
    }
}

// Affiliate stats
$affiliateStats = [];
foreach ($users as $username => $user) {
    if (($user['earnings'] ?? 0) > 0 || ($user['total_referrals'] ?? 0) > 0) {
        $affiliateStats[$username] = [
            'username' => $username,
            'earnings' => $user['earnings'] ?? 0,
            'referrals' => $user['total_referrals'] ?? 0,
            'tier' => $user['tier'] ?? 'free'
        ];
    }
}
usort($affiliateStats, fn($a, $b) => $b['earnings'] - $a['earnings']);

// =====================================================
// 📥 EXPORT CSV
// =====================================================
if (isset($_GET['export'])) {
    $exportType = $_GET['export'];
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=report_' . $exportType . '_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
    
    if ($exportType === 'orders') {
        fputcsv($output, ['Order ID', 'Username', 'Email', 'Gói', 'Số tiền', 'Trạng thái', 'Ngày tạo', 'Ngày duyệt']);
        foreach ($filteredOrders as $orderId => $order) {
            fputcsv($output, [
                $orderId,
                $order['username'] ?? '',
                $order['customer_email'] ?? '',
                $order['plan_label'] ?? $order['plan'] ?? '',
                $order['amount'] ?? 0,
                $order['status'] ?? '',
                $order['created_at'] ?? '',
                $order['approved_at'] ?? ''
            ]);
        }
    } elseif ($exportType === 'users') {
        fputcsv($output, ['Username', 'Email', 'Tier', 'Trạng thái', 'Ngày đăng ký', 'Login cuối', 'Affiliate Code', 'Referred By']);
        foreach ($filteredUsersList as $username => $user) {
            fputcsv($output, [
                $username,
                $user['email'] ?? '',
                $user['tier'] ?? 'free',
                $user['status'] ?? 'active',
                $user['created_at'] ?? '',
                $user['last_login'] ?? '',
                $user['affiliate_code'] ?? '',
                $user['referred_by'] ?? ''
            ]);
        }
    } elseif ($exportType === 'affiliates') {
        fputcsv($output, ['Username', 'Tier', 'Số người giới thiệu', 'Hoa hồng']);
        foreach ($affiliateStats as $aff) {
            fputcsv($output, [
                $aff['username'],
                $aff['tier'],
                $aff['referrals'],
                $aff['earnings']
            ]);
        }
    }
    
    fclose($output);
    exit;
}

// Pending orders for sidebar
$pendingCount = count(array_filter($orders, fn($o) => ($o['status'] ?? '') === 'pending'));

$currentPage = 'reports';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo Cáo Chi Tiết - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
        .sidebar { width: 260px; min-height: 100vh; }
        .main-content { margin-left: 260px; }
        .nav-item { transition: all 0.2s ease; }
        .nav-item:hover { background: linear-gradient(90deg, rgba(99,102,241,0.1) 0%, transparent 100%); }
        .nav-item.active { background: linear-gradient(90deg, #6366f1 0%, #8b5cf6 100%); color: white !important; }
        .nav-item.active i, .nav-item.active span { color: white !important; }
        .filter-btn.active { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: white; }
        @media (max-width: 1024px) { .sidebar { display: none; } .main-content { margin-left: 0; } }
        @media print { .sidebar, .no-print { display: none !important; } .main-content { margin-left: 0; } }
    </style>
</head>
<body class="bg-slate-100">

    <!-- Sidebar -->
    <aside class="sidebar fixed left-0 top-0 bg-slate-900 text-white z-40 no-print">
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
                <?php if ($pendingCount > 0): ?><span class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full"><?php echo $pendingCount; ?></span><?php endif; ?>
            </a>
            <a href="users.php" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:text-white">
                <i class="fa-solid fa-users w-5"></i><span>Quản Lý Users</span>
            </a>
            <!--<a href="affiliates.php" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:text-white">-->
            <!--    <i class="fa-solid fa-handshake w-5"></i><span>Cộng Tác Viên</span>-->
            <!--</a>-->
            <a href="reports.php" class="nav-item active flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:text-white">
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
        <header class="bg-white shadow-sm px-6 py-4 sticky top-0 z-30 no-print">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-black text-slate-800">Báo Cáo Chi Tiết</h1>
                    <p class="text-sm text-slate-500"><?php echo $periodLabel; ?></p>
                </div>
                <div class="flex gap-2">
                    <button onclick="window.print()" class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-4 py-2 rounded-lg font-bold text-sm">
                        <i class="fa-solid fa-print mr-1"></i> In
                    </button>
                </div>
            </div>
        </header>

        <div class="p-6">
            <!-- Date Range Filter -->
            <div class="bg-white rounded-xl shadow-sm p-4 mb-6 no-print">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="text-sm font-bold text-slate-600"><i class="fa-solid fa-calendar mr-2"></i>Kỳ báo cáo:</span>
                    
                    <div class="flex flex-wrap gap-2">
                        <a href="?filter=day" class="filter-btn px-4 py-2 rounded-lg text-sm font-bold <?php echo $filterType === 'day' ? 'active' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">Hôm nay</a>
                        <a href="?filter=week" class="filter-btn px-4 py-2 rounded-lg text-sm font-bold <?php echo $filterType === 'week' ? 'active' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">Tuần này</a>
                        <a href="?filter=month" class="filter-btn px-4 py-2 rounded-lg text-sm font-bold <?php echo $filterType === 'month' ? 'active' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">Tháng này</a>
                        <a href="?filter=year" class="filter-btn px-4 py-2 rounded-lg text-sm font-bold <?php echo $filterType === 'year' ? 'active' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">Năm nay</a>
                    </div>
                    
                    <div class="flex items-center gap-2 ml-auto">
                        <input type="text" id="dateRange" placeholder="Chọn khoảng thời gian..." 
                               class="px-4 py-2 border border-slate-300 rounded-lg text-sm w-64">
                        <button onclick="applyDateRange()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-bold">
                            <i class="fa-solid fa-filter mr-1"></i> Áp dụng
                        </button>
                    </div>
                </div>
            </div>

            <!-- Summary Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl p-5 text-white">
                    <div class="text-xs opacity-80 mb-1">Doanh thu trong kỳ</div>
                    <div class="text-2xl font-black"><?php echo number_format($filteredRevenue, 0, ',', '.'); ?>đ</div>
                    <div class="text-xs opacity-80 mt-2">Tổng: <?php echo number_format($totalRevenue, 0, ',', '.'); ?>đ</div>
                </div>
                <div class="bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl p-5 text-white">
                    <div class="text-xs opacity-80 mb-1">Đơn hàng trong kỳ</div>
                    <div class="text-2xl font-black"><?php echo count($filteredOrders); ?></div>
                    <div class="text-xs opacity-80 mt-2">Tổng: <?php echo count($approvedOrders); ?> đơn</div>
                </div>
                <div class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl p-5 text-white">
                    <div class="text-xs opacity-80 mb-1">Users mới trong kỳ</div>
                    <div class="text-2xl font-black"><?php echo $newUsersInPeriod; ?></div>
                    <div class="text-xs opacity-80 mt-2">Tổng: <?php echo $totalUsers; ?> users</div>
                </div>
                <div class="bg-gradient-to-br from-orange-500 to-red-600 rounded-xl p-5 text-white">
                    <div class="text-xs opacity-80 mb-1">Tỷ lệ chuyển đổi</div>
                    <div class="text-2xl font-black"><?php echo number_format($conversionRate, 1); ?>%</div>
                    <div class="text-xs opacity-80 mt-2"><?php echo $paidUsers; ?>/<?php echo $totalUsers; ?> đã trả phí</div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Tier Distribution -->
                <div class="bg-white rounded-xl p-6 shadow-sm">
                    <h3 class="text-lg font-black mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-chart-pie text-purple-500"></i>
                        Phân Bố Tier (Tổng)
                    </h3>
                    <canvas id="tierChart" height="200"></canvas>
                    <div class="grid grid-cols-5 gap-2 mt-4 text-center text-xs">
                        <div class="p-2 bg-slate-100 rounded"><div class="font-black text-lg"><?php echo $tierStats['free']; ?></div>Free</div>
                        <div class="p-2 bg-yellow-100 rounded"><div class="font-black text-lg text-yellow-600"><?php echo $tierStats['trial']; ?></div>Trial</div>
                        <div class="p-2 bg-blue-100 rounded"><div class="font-black text-lg text-blue-600"><?php echo $tierStats['basic']; ?></div>Basic</div>
                        <div class="p-2 bg-purple-100 rounded"><div class="font-black text-lg text-purple-600"><?php echo $tierStats['vip']; ?></div>VIP</div>
                        <div class="p-2 bg-red-100 rounded"><div class="font-black text-lg text-red-600"><?php echo $tierStats['admin']; ?></div>Admin</div>
                    </div>
                </div>

                <!-- Plan Revenue -->
                <div class="bg-white rounded-xl p-6 shadow-sm">
                    <h3 class="text-lg font-black mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-box text-orange-500"></i>
                        Doanh Thu Theo Gói (Trong kỳ)
                    </h3>
                    <canvas id="planChart" height="200"></canvas>
                </div>
            </div>

            <!-- Detailed Tables -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Orders in Period -->
                <div class="bg-white rounded-xl p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-black flex items-center gap-2">
                            <i class="fa-solid fa-shopping-cart text-green-500"></i>
                            Đơn Hàng Trong Kỳ (<?php echo count($filteredOrders); ?>)
                        </h3>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'orders'])); ?>" 
                           class="text-xs bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded font-bold no-print">
                            <i class="fa-solid fa-download mr-1"></i> Export CSV
                        </a>
                    </div>
                    <div class="overflow-x-auto max-h-80">
                        <table class="w-full text-xs">
                            <thead class="bg-slate-100 sticky top-0">
                                <tr>
                                    <th class="px-3 py-2 text-left">User</th>
                                    <th class="px-3 py-2 text-left">Gói</th>
                                    <th class="px-3 py-2 text-right">Số tiền</th>
                                    <th class="px-3 py-2 text-left">Ngày</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <?php foreach (array_slice($filteredOrders, 0, 20) as $order): ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="px-3 py-2 font-bold"><?php echo htmlspecialchars($order['username']); ?></td>
                                    <td class="px-3 py-2"><?php echo $order['plan_label'] ?? $order['plan']; ?></td>
                                    <td class="px-3 py-2 text-right font-bold text-green-600"><?php echo number_format($order['amount'], 0, ',', '.'); ?>đ</td>
                                    <td class="px-3 py-2 text-slate-500"><?php echo date('d/m', strtotime($order['approved_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- New Users in Period -->
                <div class="bg-white rounded-xl p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-black flex items-center gap-2">
                            <i class="fa-solid fa-user-plus text-blue-500"></i>
                            Users Mới Trong Kỳ (<?php echo $newUsersInPeriod; ?>)
                        </h3>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'users'])); ?>" 
                           class="text-xs bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded font-bold no-print">
                            <i class="fa-solid fa-download mr-1"></i> Export CSV
                        </a>
                    </div>
                    <div class="overflow-x-auto max-h-80">
                        <table class="w-full text-xs">
                            <thead class="bg-slate-100 sticky top-0">
                                <tr>
                                    <th class="px-3 py-2 text-left">Username</th>
                                    <th class="px-3 py-2 text-left">Email</th>
                                    <th class="px-3 py-2 text-left">Tier</th>
                                    <th class="px-3 py-2 text-left">Ngày</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <?php foreach (array_slice($filteredUsersList, 0, 20, true) as $username => $user): ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="px-3 py-2 font-bold"><?php echo htmlspecialchars($username); ?></td>
                                    <td class="px-3 py-2 text-slate-500"><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td class="px-3 py-2"><?php echo getTierBadge($user['tier'] ?? 'free'); ?></td>
                                    <td class="px-3 py-2 text-slate-500"><?php echo date('d/m', strtotime($user['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Affiliate Performance -->
            <!--<div class="bg-white rounded-xl p-6 shadow-sm">-->
            <!--    <div class="flex items-center justify-between mb-4">-->
            <!--        <h3 class="text-lg font-black flex items-center gap-2">-->
            <!--            <i class="fa-solid fa-handshake text-yellow-500"></i>-->
            <!--            Top 10 Cộng Tác Viên-->
            <!--        </h3>-->
            <!--        <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'affiliates'])); ?>" -->
            <!--           class="text-xs bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1 rounded font-bold no-print">-->
            <!--            <i class="fa-solid fa-download mr-1"></i> Export CSV-->
            <!--        </a>-->
            <!--    </div>-->
            <!--    <div class="overflow-x-auto">-->
            <!--        <table class="w-full text-sm">-->
            <!--            <thead class="bg-gradient-to-r from-yellow-400 to-orange-500 text-white">-->
            <!--                <tr>-->
            <!--                    <th class="px-4 py-2 text-left">#</th>-->
            <!--                    <th class="px-4 py-2 text-left">Username</th>-->
            <!--                    <th class="px-4 py-2 text-left">Tier</th>-->
            <!--                    <th class="px-4 py-2 text-center">Referrals</th>-->
            <!--                    <th class="px-4 py-2 text-right">Hoa Hồng</th>-->
            <!--                </tr>-->
            <!--            </thead>-->
            <!--            <tbody class="divide-y">-->
            <!--                <?php foreach (array_slice($affiliateStats, 0, 10) as $idx => $aff): ?>-->
            <!--                <tr class="hover:bg-slate-50">-->
            <!--                    <td class="px-4 py-2 font-bold"><?php echo $idx + 1; ?></td>-->
            <!--                    <td class="px-4 py-2 font-bold"><?php echo htmlspecialchars($aff['username']); ?></td>-->
            <!--                    <td class="px-4 py-2"><?php echo getTierBadge($aff['tier']); ?></td>-->
            <!--                    <td class="px-4 py-2 text-center"><?php echo $aff['referrals']; ?></td>-->
            <!--                    <td class="px-4 py-2 text-right font-black text-green-600"><?php echo number_format($aff['earnings'], 0, ',', '.'); ?>đ</td>-->
            <!--                </tr>-->
            <!--                <?php endforeach; ?>-->
            <!--            </tbody>-->
            <!--        </table>-->
            <!--    </div>-->
            <!--</div>-->
        </div>
    </main>

    <script>
        // Date Range Picker
        flatpickr("#dateRange", {
            mode: "range",
            dateFormat: "Y-m-d",
            defaultDate: ["<?php echo $dateFrom; ?>", "<?php echo $dateTo; ?>"]
        });
        
        function applyDateRange() {
            const dateRange = document.getElementById('dateRange').value;
            const dates = dateRange.split(' to ');
            if (dates.length === 2) {
                window.location.href = `?filter=custom&from=${dates[0]}&to=${dates[1]}`;
            } else if (dates.length === 1 && dates[0]) {
                window.location.href = `?filter=custom&from=${dates[0]}&to=${dates[0]}`;
            }
        }

        // Tier Chart
        new Chart(document.getElementById('tierChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Free', 'Trial', 'Basic', 'VIP', 'Admin'],
                datasets: [{
                    data: [<?php echo implode(',', array_values($tierStats)); ?>],
                    backgroundColor: ['#94a3b8', '#fbbf24', '#3b82f6', '#a855f7', '#ef4444']
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });

        // Plan Revenue Chart
        new Chart(document.getElementById('planChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['Trial', '1 tháng', '3 tháng', '6 tháng', '12 tháng'],
                datasets: [{
                    label: 'Doanh thu',
                    data: [<?php echo implode(',', array_values($planRevenue)); ?>],
                    backgroundColor: ['#fbbf24', '#3b82f6', '#6366f1', '#a855f7', '#ec4899']
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { callback: v => v.toLocaleString('vi-VN') + 'đ' }
                    }
                }
            }
        });
    </script>

</body>
</html>
