<?php
// Proper session initialization
require_once '../includes/session.php';
require_once '../includes/functions.php';

// Check admin access
if (!isAdmin()) {
    redirect('login.php');
}

// AJAX endpoint for auto-refresh
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json');
    $orders = loadDB('orders.json');
    $users = loadDB('users.json');
    
    $today = date('Y-m-d');
    $todayRevenue = 0;
    $approvedOrders = array_filter($orders, fn($o) => ($o['status'] ?? '') === 'approved');
    foreach ($approvedOrders as $order) {
        if (!empty($order['approved_at']) && date('Y-m-d', strtotime($order['approved_at'])) === $today) {
            $todayRevenue += floatval($order['amount'] ?? 0);
        }
    }
    
    $pendingOrders = array_filter($orders, fn($o) => ($o['status'] ?? '') === 'pending');
    
    echo json_encode([
        'todayRevenue' => $todayRevenue,
        'pending' => count($pendingOrders),
        'approved' => count($approvedOrders),
        'totalUsers' => count($users)
    ]);
    exit;
}

// =====================================================
// 📅 DATE RANGE FILTER
// =====================================================
$filterType = $_GET['filter'] ?? 'month'; // day, week, month, year, custom
$customFrom = $_GET['from'] ?? '';
$customTo = $_GET['to'] ?? '';

// Calculate date ranges
$today = date('Y-m-d');
$thisWeekStart = date('Y-m-d', strtotime('monday this week'));
$thisMonthStart = date('Y-m-01');
$thisYearStart = date('Y-01-01');

switch ($filterType) {
    case 'day':
        $dateFrom = $today;
        $dateTo = $today;
        $periodLabel = 'Hôm nay';
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
// 📊 CALCULATE STATISTICS WITH FILTER
// =====================================================
$totalUsers = count($users);
$vipUsers = count(array_filter($users, fn($u) => ($u['tier'] ?? 'free') === TIER_VIP));
$basicUsers = count(array_filter($users, fn($u) => ($u['tier'] ?? 'free') === 'basic'));
$trialUsers = count(array_filter($users, fn($u) => ($u['tier'] ?? 'free') === 'trial'));
$freeUsers = $totalUsers - $vipUsers - $basicUsers - $trialUsers;

// All-time revenue
$allTimeRevenue = 0;
$approvedOrders = array_filter($orders, fn($o) => ($o['status'] ?? '') === 'approved');
foreach ($approvedOrders as $order) {
    $allTimeRevenue += $order['amount'] ?? 0;
}

// Filtered revenue
$filteredRevenue = 0;
$filteredOrders = 0;
$newUsersInPeriod = 0;

foreach ($approvedOrders as $order) {
    if (!empty($order['approved_at'])) {
        $orderDate = date('Y-m-d', strtotime($order['approved_at']));
        if ($orderDate >= $dateFrom && $orderDate <= $dateTo) {
            $filteredRevenue += $order['amount'] ?? 0;
            $filteredOrders++;
        }
    }
}

// New users in period
foreach ($users as $user) {
    if (!empty($user['created_at'])) {
        $createdDate = date('Y-m-d', strtotime($user['created_at']));
        if ($createdDate >= $dateFrom && $createdDate <= $dateTo) {
            $newUsersInPeriod++;
        }
    }
}

// Pending orders
$pendingOrders = array_filter($orders, fn($o) => ($o['status'] ?? '') === 'pending');
$pendingCount = count($pendingOrders);

// =====================================================
// 📈 CHART DATA (Dynamic based on filter)
// =====================================================
$chartLabels = [];
$chartRevenue = [];
$chartUsers = [];

if ($filterType === 'day' || $filterType === 'week') {
    // Show daily for day/week view
    $days = ($filterType === 'day') ? 1 : 7;
    for ($i = $days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $chartLabels[] = date('d/m', strtotime($date));
        $chartRevenue[$date] = 0;
        $chartUsers[$date] = 0;
    }
} elseif ($filterType === 'month') {
    // Show daily for month view
    $daysInMonth = date('t');
    for ($i = 1; $i <= $daysInMonth; $i++) {
        $date = date('Y-m-') . str_pad($i, 2, '0', STR_PAD_LEFT);
        $chartLabels[] = str_pad($i, 2, '0', STR_PAD_LEFT);
        $chartRevenue[$date] = 0;
        $chartUsers[$date] = 0;
    }
} elseif ($filterType === 'year') {
    // Show monthly for year view
    for ($m = 1; $m <= 12; $m++) {
        $monthKey = date('Y') . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);
        $chartLabels[] = 'T' . $m;
        $chartRevenue[$monthKey] = 0;
        $chartUsers[$monthKey] = 0;
    }
} else {
    // Custom: determine granularity
    $daysDiff = (strtotime($dateTo) - strtotime($dateFrom)) / 86400;
    if ($daysDiff <= 31) {
        // Daily
        for ($d = strtotime($dateFrom); $d <= strtotime($dateTo); $d += 86400) {
            $date = date('Y-m-d', $d);
            $chartLabels[] = date('d/m', $d);
            $chartRevenue[$date] = 0;
            $chartUsers[$date] = 0;
        }
    } else {
        // Monthly
        $start = new DateTime($dateFrom);
        $end = new DateTime($dateTo);
        $end->modify('first day of next month');
        $interval = new DateInterval('P1M');
        $period = new DatePeriod($start, $interval, $end);
        foreach ($period as $dt) {
            $monthKey = $dt->format('Y-m');
            $chartLabels[] = $dt->format('m/Y');
            $chartRevenue[$monthKey] = 0;
            $chartUsers[$monthKey] = 0;
        }
    }
}

// Fill chart data
foreach ($approvedOrders as $order) {
    if (!empty($order['approved_at'])) {
        $orderDate = date('Y-m-d', strtotime($order['approved_at']));
        $orderMonth = date('Y-m', strtotime($order['approved_at']));
        
        if (isset($chartRevenue[$orderDate])) {
            $chartRevenue[$orderDate] += $order['amount'];
        } elseif (isset($chartRevenue[$orderMonth])) {
            $chartRevenue[$orderMonth] += $order['amount'];
        }
    }
}

foreach ($users as $user) {
    if (!empty($user['created_at'])) {
        $createdDate = date('Y-m-d', strtotime($user['created_at']));
        $createdMonth = date('Y-m', strtotime($user['created_at']));
        
        if (isset($chartUsers[$createdDate])) {
            $chartUsers[$createdDate]++;
        } elseif (isset($chartUsers[$createdMonth])) {
            $chartUsers[$createdMonth]++;
        }
    }
}

$chartRevenueData = array_values($chartRevenue);
$chartUsersData = array_values($chartUsers);

// =====================================================
// 💰 TOP AFFILIATES
// =====================================================
$affiliateEarnings = [];
foreach ($users as $username => $user) {
    if (($user['earnings'] ?? 0) > 0 || ($user['total_referrals'] ?? 0) > 0) {
        $affiliateEarnings[] = [
            'username' => $username,
            'earnings' => $user['earnings'] ?? 0,
            'referrals' => $user['total_referrals'] ?? 0,
            'tier' => $user['tier'] ?? 'free'
        ];
    }
}
usort($affiliateEarnings, fn($a, $b) => $b['earnings'] - $a['earnings']);
$topAffiliates = array_slice($affiliateEarnings, 0, 5);

// Recent orders
$recentOrders = array_slice($orders, 0, 5);

// Include sidebar
$currentPage = 'dashboard';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - HSHOP Analytics</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
        /* Desktop Sidebar */
        .sidebar { width: 260px; min-height: 100vh; transition: transform 0.3s ease; }
        .main-content { margin-left: 260px; transition: margin 0.3s ease; }
        .nav-item { transition: all 0.2s ease; }
        .nav-item:hover { background: linear-gradient(90deg, rgba(99,102,241,0.1) 0%, transparent 100%); }
        .nav-item.active { 
            background: linear-gradient(90deg, #6366f1 0%, #8b5cf6 100%); 
            color: white !important;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
        }
        .nav-item.active i, .nav-item.active span { color: white !important; }
        .stat-card { transition: transform 0.2s, box-shadow 0.2s; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.15); }
        .filter-btn.active { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: white; }
        
        /* Mobile Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
                box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            }
            .sidebar.mobile-open {
                transform: translateX(0);
            }
            .main-content { 
                margin-left: 0; 
                padding-bottom: 70px; /* Space for bottom nav */
            }
            .mobile-header { display: flex; }
            .desktop-header { display: none; }
        }
        
        @media (min-width: 1025px) {
            .mobile-header { display: none; }
            .desktop-header { display: flex; }
            .mobile-bottom-nav { display: none; }
        }
        
        /* Mobile Bottom Navigation */
        .mobile-bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-top: 2px solid #e2e8f0;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            z-index: 50;
            display: none;
        }
        
        .mobile-bottom-nav a {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 0.5rem;
            text-decoration: none;
            color: #64748b;
            font-size: 0.75rem;
            transition: all 0.2s;
        }
        
        .mobile-bottom-nav a.active {
            color: #6366f1;
            background: linear-gradient(to top, rgba(99,102,241,0.1), transparent);
        }
        
        .mobile-bottom-nav a i {
            font-size: 1.25rem;
            margin-bottom: 0.25rem;
        }
        
        /* Mobile Overlay */
        .mobile-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 35;
        }
        
        .mobile-overlay.active {
            display: block;
        }
    </style>
</head>
<body class="bg-slate-100">

    <!-- Mobile Overlay -->
    <div class="mobile-overlay" id="mobileOverlay" onclick="toggleMobileSidebar()"></div>

    <!-- Sidebar -->
    <aside class="sidebar fixed left-0 top-0 bg-slate-900 text-white z-40" id="sidebar">
        <div class="p-6 border-b border-slate-700">
            <h1 class="text-xl font-black flex items-center gap-2">
                <i class="fa-solid fa-shield-halved text-yellow-400"></i>
                Admin Panel
            </h1>
            <p class="text-xs text-slate-400 mt-1">HSHOP Analytics</p>
        </div>
        
        <nav class="p-4 space-y-2">
            <a href="index.php" class="nav-item <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:text-white">
                <i class="fa-solid fa-gauge-high w-5"></i>
                <span>Dashboard</span>
            </a>
            <a href="orders.php" class="nav-item <?php echo $currentPage === 'orders' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:text-white">
                <i class="fa-solid fa-shopping-cart w-5"></i>
                <span>Đơn Hàng</span>
                <?php if ($pendingCount > 0): ?>
                <span class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full"><?php echo $pendingCount; ?></span>
                <?php endif; ?>
            </a>
            <a href="users.php" class="nav-item <?php echo $currentPage === 'users' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:text-white">
                <i class="fa-solid fa-users w-5"></i>
                <span>Quản Lý Users</span>
            </a>
            <!--<a href="affiliates.php" class="nav-item <?php echo $currentPage === 'affiliates' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:text-white">-->
            <!--    <i class="fa-solid fa-handshake w-5"></i>-->
            <!--    <span>Cộng Tác Viên</span>-->
            <!--</a>-->
            <a href="reports.php" class="nav-item <?php echo $currentPage === 'reports' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:text-white">
                <i class="fa-solid fa-chart-line w-5"></i>
                <span>Báo Cáo</span>
            </a>
            
            <div class="border-t border-slate-700 my-4 pt-4">
                <p class="text-xs text-slate-500 px-4 mb-2 uppercase tracking-wider">API & Settings</p>
            </div>
            
            <a href="keys.php" class="nav-item <?php echo $currentPage === 'keys' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:text-white">
                <i class="fa-solid fa-key w-5"></i>
                <span>API Keys Pool</span>
            </a>
            <a href="api_keys_overview.php" class="nav-item <?php echo $currentPage === 'keys_overview' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:text-white">
                <i class="fa-solid fa-eye w-5"></i>
                <span>Keys Overview</span>
            </a>
            
            <div class="border-t border-slate-700 my-4 pt-4">
                <p class="text-xs text-slate-500 px-4 mb-2 uppercase tracking-wider">Quick Links</p>
            </div>
            
            <a href="../scanner.php" target="_blank" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:text-white">
                <i class="fa-solid fa-radar w-5"></i>
                <span>Scanner Tool</span>
                <i class="fa-solid fa-external-link text-xs ml-auto"></i>
            </a>
            <a href="../index.php" target="_blank" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:text-white">
                <i class="fa-solid fa-globe w-5"></i>
                <span>Trang Chủ</span>
                <i class="fa-solid fa-external-link text-xs ml-auto"></i>
            </a>
        </nav>
        
        <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-slate-700">
            <a href="logout.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-red-400 hover:bg-red-500/10 transition">
                <i class="fa-solid fa-sign-out-alt w-5"></i>
                <span>Đăng Xuất</span>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content min-h-screen">
        <!-- Mobile Header -->
        <header class="mobile-header bg-white shadow-sm px-4 py-3 items-center justify-between sticky top-0 z-30">
            <button onclick="toggleMobileSidebar()" class="p-2 hover:bg-slate-100 rounded-lg">
                <i class="fa-solid fa-bars text-xl text-slate-700"></i>
            </button>
            <h1 class="text-lg font-black text-slate-800">Dashboard</h1>
            <a href="logout.php" class="p-2 hover:bg-red-50 rounded-lg text-red-600">
                <i class="fa-solid fa-sign-out-alt"></i>
            </a>
        </header>
        
        <!-- Desktop Header -->
        <header class="desktop-header bg-white shadow-sm px-6 py-4 items-center justify-between sticky top-0 z-30">
            <div>
                <h1 class="text-2xl font-black text-slate-800">Dashboard</h1>
                <p class="text-sm text-slate-500">Tổng quan hệ thống - <?php echo $periodLabel; ?></p>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-sm text-slate-600">
                    <i class="fa-solid fa-user-circle mr-1"></i> 
                    <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>
                </span>
                <span class="text-xs text-slate-400"><?php echo date('d/m/Y H:i'); ?></span>
            </div>
        </header>

        <div class="p-6">
            <!-- Date Range Filter -->
            <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="text-sm font-bold text-slate-600"><i class="fa-solid fa-calendar mr-2"></i>Lọc theo:</span>
                    
                    <div class="flex flex-wrap gap-2">
                        <a href="?filter=day" class="filter-btn px-4 py-2 rounded-lg text-sm font-bold <?php echo $filterType === 'day' ? 'active' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">
                            Hôm nay
                        </a>
                        <a href="?filter=week" class="filter-btn px-4 py-2 rounded-lg text-sm font-bold <?php echo $filterType === 'week' ? 'active' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">
                            Tuần này
                        </a>
                        <a href="?filter=month" class="filter-btn px-4 py-2 rounded-lg text-sm font-bold <?php echo $filterType === 'month' ? 'active' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">
                            Tháng này
                        </a>
                        <a href="?filter=year" class="filter-btn px-4 py-2 rounded-lg text-sm font-bold <?php echo $filterType === 'year' ? 'active' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">
                            Năm nay
                        </a>
                    </div>
                    
                    <div class="flex items-center gap-2 ml-auto">
                        <input type="text" id="dateRange" placeholder="Chọn khoảng thời gian..." 
                               class="px-4 py-2 border border-slate-300 rounded-lg text-sm w-64 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <button onclick="applyDateRange()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-bold transition">
                            <i class="fa-solid fa-filter mr-1"></i> Áp dụng
                        </button>
                    </div>
                </div>
            </div>

            <!-- Stats Cards Row 1 -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div class="stat-card bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl p-6 text-white shadow-lg">
                    <div class="flex items-center justify-between mb-3">
                        <i class="fa-solid fa-dollar-sign text-3xl opacity-60"></i>
                        <span class="text-xs font-bold bg-white/20 px-3 py-1 rounded-full"><?php echo $periodLabel; ?></span>
                    </div>
                    <div class="text-3xl font-black mb-1"><?php echo number_format($filteredRevenue, 0, ',', '.'); ?>đ</div>
                    <div class="text-sm opacity-90">Doanh thu trong kỳ</div>
                    <div class="mt-3 pt-3 border-t border-white/20 text-xs">
                        <span class="opacity-80">Tổng: <?php echo number_format($allTimeRevenue, 0, ',', '.'); ?>đ</span>
                    </div>
                </div>

                <div class="stat-card bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl p-6 text-white shadow-lg">
                    <div class="flex items-center justify-between mb-3">
                        <i class="fa-solid fa-shopping-cart text-3xl opacity-60"></i>
                        <span class="text-xs font-bold bg-white/20 px-3 py-1 rounded-full"><?php echo $periodLabel; ?></span>
                    </div>
                    <div class="text-3xl font-black mb-1"><?php echo $filteredOrders; ?></div>
                    <div class="text-sm opacity-90">Đơn hàng trong kỳ</div>
                    <div class="mt-3 pt-3 border-t border-white/20 text-xs">
                        <span class="opacity-80">Tổng: <?php echo count($approvedOrders); ?> đơn đã duyệt</span>
                    </div>
                </div>

                <div class="stat-card bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl p-6 text-white shadow-lg">
                    <div class="flex items-center justify-between mb-3">
                        <i class="fa-solid fa-user-plus text-3xl opacity-60"></i>
                        <span class="text-xs font-bold bg-white/20 px-3 py-1 rounded-full"><?php echo $periodLabel; ?></span>
                    </div>
                    <div class="text-3xl font-black mb-1"><?php echo $newUsersInPeriod; ?></div>
                    <div class="text-sm opacity-90">Users mới trong kỳ</div>
                    <div class="mt-3 pt-3 border-t border-white/20 text-xs">
                        <span class="opacity-80">Tổng: <?php echo $totalUsers; ?> users</span>
                    </div>
                </div>

                <div class="stat-card bg-gradient-to-br from-orange-500 to-red-600 rounded-xl p-6 text-white shadow-lg relative overflow-hidden">
                    <div class="flex items-center justify-between mb-3">
                        <i class="fa-solid fa-clock text-3xl opacity-60"></i>
                        <span class="text-xs font-bold bg-white/20 px-3 py-1 rounded-full">CHỜ DUYỆT</span>
                    </div>
                    <div class="text-3xl font-black mb-1"><?php echo $pendingCount; ?></div>
                    <div class="text-sm opacity-90">Đơn chờ duyệt</div>
                    <?php if ($pendingCount > 0): ?>
                    <a href="orders.php" class="mt-3 block text-center bg-white/20 hover:bg-white/30 py-2 rounded-lg text-xs font-bold transition">
                        Xem ngay <i class="fa-solid fa-arrow-right ml-1"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Stats Cards Row 2 - User Tiers -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-xl p-5 shadow-sm border-l-4 border-slate-400">
                    <div class="text-2xl font-black text-slate-700"><?php echo $freeUsers; ?></div>
                    <div class="text-xs text-slate-500 font-semibold">FREE Users</div>
                </div>
                <div class="bg-white rounded-xl p-5 shadow-sm border-l-4 border-yellow-400">
                    <div class="text-2xl font-black text-yellow-600"><?php echo $trialUsers; ?></div>
                    <div class="text-xs text-slate-500 font-semibold">TRIAL Users</div>
                </div>
                <div class="bg-white rounded-xl p-5 shadow-sm border-l-4 border-blue-500">
                    <div class="text-2xl font-black text-blue-600"><?php echo $basicUsers; ?></div>
                    <div class="text-xs text-slate-500 font-semibold">BASIC Users</div>
                </div>
                <div class="bg-white rounded-xl p-5 shadow-sm border-l-4 border-purple-500">
                    <div class="text-2xl font-black text-purple-600"><?php echo $vipUsers; ?></div>
                    <div class="text-xs text-slate-500 font-semibold">VIP Users</div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white rounded-xl p-6 shadow-sm">
                    <h3 class="text-lg font-black mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-chart-line text-green-500"></i>
                        Doanh Thu - <?php echo $periodLabel; ?>
                    </h3>
                    <canvas id="revenueChart" height="200"></canvas>
                </div>

                <div class="bg-white rounded-xl p-6 shadow-sm">
                    <h3 class="text-lg font-black mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-users text-blue-500"></i>
                        Users Mới - <?php echo $periodLabel; ?>
                    </h3>
                    <canvas id="usersChart" height="200"></canvas>
                </div>
            </div>

            <!-- Bottom Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6"  >
                <!-- Top Affiliates -->
                <div class="bg-white rounded-xl p-6 shadow-sm" style="display: none !important;">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-black flex items-center gap-2">
                            <i class="fa-solid fa-trophy text-yellow-500"></i>
                            Top Cộng Tác Viên
                        </h3>
                        <a href="affiliates.php" class="text-sm text-indigo-600 hover:text-indigo-800 font-bold">
                            Xem tất cả <i class="fa-solid fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                    
                    <?php if (empty($topAffiliates)): ?>
                    <p class="text-slate-500 text-center py-8">Chưa có cộng tác viên nào</p>
                    <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($topAffiliates as $idx => $aff): ?>
                        <div class="flex items-center gap-4 p-3 bg-slate-50 rounded-lg">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-yellow-400 to-orange-500 flex items-center justify-center text-white font-black">
                                <?php if ($idx === 0): ?>
                                <i class="fa-solid fa-crown"></i>
                                <?php else: ?>
                                <?php echo $idx + 1; ?>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1">
                                <div class="font-bold text-slate-800"><?php echo htmlspecialchars($aff['username']); ?></div>
                                <div class="text-xs text-slate-500"><?php echo $aff['referrals']; ?> referrals</div>
                            </div>
                            <div class="text-right">
                                <div class="font-black text-green-600"><?php echo number_format($aff['earnings'], 0, ',', '.'); ?>đ</div>
                                <div class="text-xs"><?php echo getTierBadge($aff['tier']); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Orders -->
                <div class="bg-white rounded-xl p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-black flex items-center gap-2">
                            <i class="fa-solid fa-clock-rotate-left text-blue-500"></i>
                            Đơn Hàng Gần Đây
                        </h3>
                        <a href="orders.php" class="text-sm text-indigo-600 hover:text-indigo-800 font-bold">
                            Xem tất cả <i class="fa-solid fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                    
                    <?php if (empty($recentOrders)): ?>
                    <p class="text-slate-500 text-center py-8">Chưa có đơn hàng nào</p>
                    <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($recentOrders as $order): ?>
                        <div class="flex items-center gap-4 p-3 bg-slate-50 rounded-lg">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center <?php 
                                echo $order['status'] === 'pending' ? 'bg-yellow-100 text-yellow-600' : 
                                    ($order['status'] === 'approved' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'); 
                            ?>">
                                <i class="fa-solid fa-<?php echo $order['status'] === 'pending' ? 'clock' : ($order['status'] === 'approved' ? 'check' : 'times'); ?>"></i>
                            </div>
                            <div class="flex-1">
                                <div class="font-bold text-slate-800"><?php echo htmlspecialchars($order['username']); ?></div>
                                <div class="text-xs text-slate-500"><?php echo $order['plan_label'] ?? $order['plan']; ?></div>
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-slate-800"><?php echo number_format($order['amount'], 0, ',', '.'); ?>đ</div>
                                <div class="text-xs text-slate-500"><?php echo date('d/m H:i', strtotime($order['created_at'])); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Mobile Bottom Navigation -->
    <nav class="mobile-bottom-nav flex">
        <a href="index.php" class="active">
            <i class="fa-solid fa-gauge-high"></i>
            <span>Dashboard</span>
        </a>
        <a href="orders.php">
            <i class="fa-solid fa-shopping-cart"></i>
            <span>Đơn Hàng</span>
            <?php if ($pendingCount > 0): ?>
            <span style="position: absolute; top: 0.5rem; right: 1rem; background: #ef4444; color: white; font-size: 0.65rem; padding: 0.125rem 0.375rem; border-radius: 9999px; font-weight: bold;"><?php echo $pendingCount; ?></span>
            <?php endif; ?>
        </a>
        <a href="users.php">
            <i class="fa-solid fa-users"></i>
            <span>Users</span>
        </a>
        <a href="affiliates.php">
            <i class="fa-solid fa-handshake"></i>
            <span>CTV</span>
        </a>
        <a href="reports.php">
            <i class="fa-solid fa-chart-line"></i>
            <span>Báo Cáo</span>
        </a>
    </nav>

    <script>
        // Mobile Sidebar Toggle
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileOverlay');
            sidebar.classList.toggle('mobile-open');
            overlay.classList.toggle('active');
            
            // Prevent body scroll when sidebar open
            if (sidebar.classList.contains('mobile-open')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        }
        
        // Auto-close sidebar when clicking any navigation link on mobile
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileOverlay');
            const navLinks = sidebar.querySelectorAll('a[href]:not([target="_blank"])');
            
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    // Only close on mobile (when sidebar is in mobile mode)
                    if (window.innerWidth <= 1024) {
                        sidebar.classList.remove('mobile-open');
                        overlay.classList.remove('active');
                        document.body.style.overflow = '';
                    }
                });
            });
        });
        
        // Initialize Flatpickr for date range
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

        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chartLabels); ?>,
                datasets: [{
                    label: 'Doanh thu (VNĐ)',
                    data: <?php echo json_encode($chartRevenueData); ?>,
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('vi-VN') + 'đ';
                            }
                        }
                    }
                }
            }
        });

        // Users Chart
        const usersCtx = document.getElementById('usersChart').getContext('2d');
        new Chart(usersCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chartLabels); ?>,
                datasets: [{
                    label: 'Users mới',
                    data: <?php echo json_encode($chartUsersData); ?>,
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
        
        // Auto-refresh dashboard every 60 seconds
        const DASHBOARD_REFRESH = 60000;
        let lastRevenue = <?php echo $filteredRevenue; ?>;
        let lastPending = <?php echo $pendingCount; ?>;
        
        async function refreshDashboard() {
            try {
                const response = await fetch('index.php?ajax=1&filter=<?php echo $filterType; ?>&from=<?php echo $dateFrom; ?>&to=<?php echo $dateTo; ?>', {
                    method: 'GET',
                    headers: { 'Cache-Control': 'no-cache' }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    
                    // Check if anything changed
                    if (data.pending !== lastPending || data.todayRevenue !== lastRevenue) {
                        console.log('📊 Dashboard data changed - Reloading...');
                        window.location.reload();
                    }
                }
            } catch (e) {
                console.log('Dashboard refresh check failed:', e);
            }
        }
        
        // Check every minute
        setInterval(refreshDashboard, DASHBOARD_REFRESH);
        console.log('✅ Dashboard auto-refresh: Every 60 seconds');
    </script>

</body>
</html>
