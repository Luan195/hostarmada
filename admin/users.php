<?php
// Proper session initialization
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/pricing_data.php';

// Check admin access
if (!isAdmin()) {
    redirect('login.php');
}

$message = '';
$error = '';

// Handle Tier Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_tier') {
    $username = sanitize($_POST['username']);
    $newTier = sanitize($_POST['tier']);
    $duration = sanitize($_POST['duration']);
    
    $users = loadDB('users.json');
    $pricingPlans = getAllPricingPlans();
    
    // Map tier + duration to pricing plans for commission
    $commissionAmount = 0;
    if ($newTier === TIER_TRIAL) {
        $commissionAmount = $pricingPlans['trial']['sale_price'] ?? 39000;
    } elseif ($newTier === TIER_BASIC) {
        if ($duration == 30) $commissionAmount = $pricingPlans['1m']['sale_price'] ?? 99000;
        elseif ($duration == 90) $commissionAmount = $pricingPlans['3m']['sale_price'] ?? 199000;
    } elseif ($newTier === TIER_VIP) {
        if ($duration == 180) $commissionAmount = $pricingPlans['6m']['sale_price'] ?? 399000;
        elseif ($duration == 365) $commissionAmount = $pricingPlans['12m']['sale_price'] ?? 699000;
    }
    
    if (updateUserTier($username, $newTier, $duration)) {
        // Process affiliate commission
        if (isset($users[$username]['referred_by']) && !empty($users[$username]['referred_by']) && $commissionAmount > 0) {
            $referralCode = $users[$username]['referred_by'];
            $users = loadDB('users.json');
            
            foreach ($users as $uname => $user) {
                if (($user['affiliate_code'] ?? '') === $referralCode && ($user['tier'] ?? '') === TIER_VIP) {
                    $commission = $commissionAmount * 0.20;
                    $users[$uname]['earnings'] = ($users[$uname]['earnings'] ?? 0) + $commission;
                    $users[$uname]['total_referrals'] = ($users[$uname]['total_referrals'] ?? 0) + 1;
                    saveDB('users.json', $users);
                    $message = "✅ Đã cập nhật tier cho user $username! 💰 +".number_format($commission, 0, ',', '.')."đ hoa hồng cho CTV";
                    break;
                }
            }
            if (empty($message)) {
                $message = "✅ Đã cập nhật tier cho user $username! (CTV không đủ điều kiện nhận hoa hồng)";
            }
        } else {
            $message = "✅ Đã cập nhật tier cho user $username!";
        }
    } else {
        $error = "Lỗi khi cập nhật tier!";
    }
}

// Handle Status Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_status') {
    $username = sanitize($_POST['username']);
    $newStatus = sanitize($_POST['status']);
    
    $users = loadDB('users.json');
    if (isset($users[$username])) {
        $users[$username]['status'] = $newStatus;
        saveDB('users.json', $users);
        $message = "✅ Đã cập nhật trạng thái user $username!";
    }
}

// Handle Reset Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    $username = sanitize($_POST['username']);
    $newPassword = $_POST['new_password'] ?? '';
    
    if (empty($newPassword) || strlen($newPassword) < 6) {
        $error = "⚠️ Mật khẩu mới phải có ít nhất 6 ký tự!";
    } elseif ($username === 'admin') {
        $error = "⚠️ Không thể reset password cho user admin!";
    } else {
        $users = loadDB('users.json');
        if (isset($users[$username])) {
            $users[$username]['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
            $users[$username]['password_reset_at'] = date('Y-m-d H:i:s');
            if (saveDB('users.json', $users)) {
                $message = "✅ Đã reset password cho user <strong>$username</strong>!<br>🔑 Mật khẩu mới: <code class='bg-yellow-100 px-2 py-1 rounded'>$newPassword</code>";
            } else {
                $error = "Lỗi khi lưu password!";
            }
        }
    }
}

// Handle Delete User
if (isset($_GET['delete_user'])) {
    $username = $_GET['delete_user'];
    $users = loadDB('users.json');
    if ($username !== 'admin' && isset($users[$username])) {
        unset($users[$username]);
        saveDB('users.json', $users);
        $message = "✅ Đã xóa user $username!";
    } else {
        $error = "Không thể xóa user admin!";
    }
}

// Load users
$users = loadDB('users.json');

// =====================================================
// 🔍 SEARCH & FILTER
// =====================================================
$search = $_GET['search'] ?? '';
$filterTier = $_GET['tier'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$sortBy = $_GET['sort'] ?? 'newest';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;

// Filter users
$filteredUsers = $users;

if (!empty($search)) {
    $filteredUsers = array_filter($filteredUsers, function($user, $username) use ($search) {
        $searchLower = strtolower($search);
        return stripos($username, $search) !== false ||
               stripos($user['email'] ?? '', $search) !== false ||
               stripos($user['affiliate_code'] ?? '', $search) !== false ||
               stripos($user['referred_by'] ?? '', $search) !== false;
    }, ARRAY_FILTER_USE_BOTH);
}

if (!empty($filterTier)) {
    $filteredUsers = array_filter($filteredUsers, fn($u) => ($u['tier'] ?? 'free') === $filterTier);
}

if (!empty($filterStatus)) {
    $filteredUsers = array_filter($filteredUsers, fn($u) => ($u['status'] ?? 'active') === $filterStatus);
}

// Sort
switch ($sortBy) {
    case 'oldest':
        uasort($filteredUsers, fn($a, $b) => strtotime($a['created_at'] ?? 0) - strtotime($b['created_at'] ?? 0));
        break;
    case 'username':
        ksort($filteredUsers);
        break;
    case 'tier':
        $tierOrder = ['admin' => 0, 'vip' => 1, 'basic' => 2, 'trial' => 3, 'free' => 4];
        uasort($filteredUsers, fn($a, $b) => ($tierOrder[$a['tier'] ?? 'free'] ?? 5) - ($tierOrder[$b['tier'] ?? 'free'] ?? 5));
        break;
    default: // newest
        uasort($filteredUsers, fn($a, $b) => strtotime($b['created_at'] ?? 0) - strtotime($a['created_at'] ?? 0));
}

// Pagination
$totalUsers = count($filteredUsers);
$totalPages = ceil($totalUsers / $perPage);
$offset = ($page - 1) * $perPage;
$pagedUsers = array_slice($filteredUsers, $offset, $perPage, true);

// Pending orders count
$orders = loadDB('orders.json');
$pendingCount = count(array_filter($orders, fn($o) => ($o['status'] ?? '') === 'pending'));

$currentPage = 'users';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Users - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Desktop Sidebar */
        .sidebar { width: 260px; min-height: 100vh; transition: transform 0.3s ease; }
        .main-content { margin-left: 260px; transition: margin 0.3s ease; }
        .nav-item { transition: all 0.2s ease; }
        .nav-item:hover { background: linear-gradient(90deg, rgba(99,102,241,0.1) 0%, transparent 100%); }
        .nav-item.active { 
            background: linear-gradient(90deg, #6366f1 0%, #8b5cf6 100%); 
            color: white !important;
        }
        .nav-item.active i, .nav-item.active span { color: white !important; }
        
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
                padding-bottom: 70px;
            }
            .mobile-header { display: flex; }
            .desktop-header { display: none; }
            
            /* Make tables scrollable on mobile */
            .table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            /* Hide less important columns on mobile */
            .hide-mobile {
                display: none;
            }
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
            position: relative;
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
        </div>
        
        <nav class="p-4 space-y-2">
            <a href="index.php" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:text-white">
                <i class="fa-solid fa-gauge-high w-5"></i><span>Dashboard</span>
            </a>
            <a href="orders.php" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:text-white">
                <i class="fa-solid fa-shopping-cart w-5"></i><span>Đơn Hàng</span>
                <?php if ($pendingCount > 0): ?><span class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full"><?php echo $pendingCount; ?></span><?php endif; ?>
            </a>
            <a href="users.php" class="nav-item active flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:text-white">
                <i class="fa-solid fa-users w-5"></i><span>Quản Lý Users</span>
            </a>
            <!--<a href="affiliates.php" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:text-white">-->
            <!--    <i class="fa-solid fa-handshake w-5"></i><span>Cộng Tác Viên</span>-->
            <!--</a>-->
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
        <!-- Mobile Header -->
        <header class="mobile-header bg-white shadow-sm px-4 py-3 items-center justify-between sticky top-0 z-30">
            <button onclick="toggleMobileSidebar()" class="p-2 hover:bg-slate-100 rounded-lg">
                <i class="fa-solid fa-bars text-xl text-slate-700"></i>
            </button>
            <h1 class="text-lg font-black text-slate-800">Users</h1>
            <a href="logout.php" class="p-2 hover:bg-red-50 rounded-lg text-red-600">
                <i class="fa-solid fa-sign-out-alt"></i>
            </a>
        </header>
        
        <!-- Desktop Header -->
        <header class="desktop-header bg-white shadow-sm px-6 py-4 sticky top-0 z-30">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-black text-slate-800">Quản Lý Users</h1>
                    <p class="text-sm text-slate-500"><?php echo $totalUsers; ?> users (<?php echo count($users); ?> tổng)</p>
                </div>
            </div>
        </header>

        <div class="p-6">
            <?php if ($message): ?>
            <div class="mb-4 bg-green-50 border-l-4 border-green-500 p-4 rounded-lg">
                <p class="text-green-700"><?php echo $message; ?></p>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                <p class="text-red-700"><?php echo $error; ?></p>
            </div>
            <?php endif; ?>

            <!-- Search & Filter Bar -->
            <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
                <form method="GET" class="flex flex-wrap gap-4 items-end">
                    <!-- Search -->
                    <div class="flex-1 min-w-[250px]">
                        <label class="block text-xs font-bold text-slate-600 mb-1">Tìm kiếm</label>
                        <div class="relative">
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Username, email, affiliate code..."
                                   class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        </div>
                    </div>
                    
                    <!-- Filter Tier -->
                    <div class="w-40">
                        <label class="block text-xs font-bold text-slate-600 mb-1">Tier</label>
                        <select name="tier" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <option value="">Tất cả</option>
                            <option value="free" <?php echo $filterTier === 'free' ? 'selected' : ''; ?>>Free</option>
                            <option value="trial" <?php echo $filterTier === 'trial' ? 'selected' : ''; ?>>Trial</option>
                            <option value="basic" <?php echo $filterTier === 'basic' ? 'selected' : ''; ?>>Basic</option>
                            <option value="vip" <?php echo $filterTier === 'vip' ? 'selected' : ''; ?>>VIP</option>
                            <option value="admin" <?php echo $filterTier === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                    
                    <!-- Filter Status -->
                    <div class="w-40">
                        <label class="block text-xs font-bold text-slate-600 mb-1">Trạng thái</label>
                        <select name="status" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <option value="">Tất cả</option>
                            <option value="active" <?php echo $filterStatus === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="banned" <?php echo $filterStatus === 'banned' ? 'selected' : ''; ?>>Banned</option>
                        </select>
                    </div>
                    
                    <!-- Sort -->
                    <div class="w-40">
                        <label class="block text-xs font-bold text-slate-600 mb-1">Sắp xếp</label>
                        <select name="sort" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <option value="newest" <?php echo $sortBy === 'newest' ? 'selected' : ''; ?>>Mới nhất</option>
                            <option value="oldest" <?php echo $sortBy === 'oldest' ? 'selected' : ''; ?>>Cũ nhất</option>
                            <option value="username" <?php echo $sortBy === 'username' ? 'selected' : ''; ?>>Tên A-Z</option>
                            <option value="tier" <?php echo $sortBy === 'tier' ? 'selected' : ''; ?>>Theo Tier</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg font-bold transition">
                        <i class="fa-solid fa-filter mr-1"></i> Lọc
                    </button>
                    
                    <a href="users.php" class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-4 py-2 rounded-lg font-bold transition">
                        <i class="fa-solid fa-times mr-1"></i> Reset
                    </a>
                </form>
            </div>

            <!-- Users Table -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 border-b">
                            <tr>
                                <th class="px-4 py-3 text-left font-bold text-slate-600">Username</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600">Email</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600">Tier</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600">Hết hạn</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600">Status</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600">Đăng ký</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600">Affiliate</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <?php foreach ($pagedUsers as $username => $user): 
                                $tierExpires = $user['tier_expires_at'] ?? $user['tier_expires'] ?? null;
                                $isExpired = $tierExpires && strtotime($tierExpires) < time();
                            ?>
                            <tr class="hover:bg-slate-50 <?php echo $isExpired ? 'bg-red-50' : ''; ?>">
                                <td class="px-4 py-3">
                                    <div class="font-bold text-slate-800"><?php echo htmlspecialchars($username); ?></div>
                                    <?php if (!empty($user['referred_by'])): ?>
                                    <div class="text-xs text-slate-500">Ref: <?php echo $user['referred_by']; ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-slate-600"><?php echo htmlspecialchars($user['email']); ?></td>
                                <td class="px-4 py-3"><?php echo getTierBadge($user['tier'] ?? 'free'); ?></td>
                                <td class="px-4 py-3">
                                    <?php if ($tierExpires): ?>
                                    <span class="text-xs <?php echo $isExpired ? 'text-red-600 font-bold' : 'text-slate-600'; ?>">
                                        <?php echo date('d/m/Y', strtotime($tierExpires)); ?>
                                        <?php if ($isExpired): ?><br><span class="text-red-500">ĐÃ HẾT HẠN</span><?php endif; ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="text-xs text-slate-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded-full text-xs font-bold <?php echo ($user['status'] ?? 'active') === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                                        <?php echo ucfirst($user['status'] ?? 'active'); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600">
                                    <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
                                    <br><span class="text-slate-400"><?php echo $user['last_login'] ? date('d/m H:i', strtotime($user['last_login'])) : 'Never'; ?></span>
                                </td>
                                <td class="px-4 py-3">
                                    <code class="text-xs bg-slate-100 px-2 py-1 rounded"><?php echo $user['affiliate_code']; ?></code>
                                    <?php if (($user['earnings'] ?? 0) > 0): ?>
                                    <div class="text-xs text-green-600 font-bold mt-1"><?php echo number_format($user['earnings'], 0, ',', '.'); ?>đ</div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <button onclick="openEditModal('<?php echo $username; ?>', '<?php echo $user['tier'] ?? 'free'; ?>', '<?php echo $user['status'] ?? 'active'; ?>')" 
                                                class="text-blue-600 hover:text-blue-800 p-1" title="Edit">
                                            <i class="fa-solid fa-edit"></i>
                                        </button>
                                        <?php if ($username !== 'admin'): ?>
                                        <a 
    class="text-gray-400 cursor-not-allowed opacity-50 p-1"
    title="Delete (Disabled)"
    onclick="return false;"
>
    <i class="fa-solid fa-trash"></i>
</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="px-4 py-4 border-t bg-slate-50 flex items-center justify-between">
                    <div class="text-sm text-slate-600">
                        Hiển thị <?php echo $offset + 1; ?>-<?php echo min($offset + $perPage, $totalUsers); ?> / <?php echo $totalUsers; ?> users
                    </div>
                    <div class="flex gap-1">
                        <?php 
                        $queryParams = $_GET;
                        for ($i = 1; $i <= $totalPages; $i++): 
                            $queryParams['page'] = $i;
                        ?>
                        <a href="?<?php echo http_build_query($queryParams); ?>" 
                           class="px-3 py-1 rounded <?php echo $i === $page ? 'bg-indigo-600 text-white' : 'bg-white border hover:bg-slate-100'; ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Edit Modal -->
    <div id="editModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-2xl p-6 max-w-md w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-black">Edit User</h3>
                <button onclick="closeEditModal()" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-times text-xl"></i>
                </button>
            </div>
            
            <form method="POST" id="editForm">
                <input type="hidden" name="username" id="edit_username">
                
                <!-- Keep current filters -->
                <?php foreach ($_GET as $key => $value): ?>
                <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>">
                <?php endforeach; ?>
                
                <div class="space-y-4">
                    <!-- Tier -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Tier</label>
                        <select name="tier" id="edit_tier" class="w-full px-3 py-2 border rounded-lg">
                            <option value="free">Free</option>
                            <option value="trial">Trial</option>
                            <option value="basic">Basic</option>
                            <option value="vip">VIP</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    
                    <!-- Duration -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Thời hạn</label>
                        <select name="duration" class="w-full px-3 py-2 border rounded-lg">
                            <option value="3">3 ngày (Trial)</option>
                            <option value="30">1 tháng</option>
                            <option value="90">3 tháng</option>
                            <option value="180">6 tháng</option>
                            <option value="365">12 tháng</option>
                            <option value="permanent">Permanent</option>
                        </select>
                    </div>
                    
                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Status</label>
                        <select name="status" id="edit_status" class="w-full px-3 py-2 border rounded-lg">
                            <option value="active">Active</option>
                            <option value="banned">Banned</option>
                        </select>
                    </div>
                    
                    <!-- Reset Password -->
                    <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg">
                        <label class="block text-sm font-bold text-amber-800 mb-1">
                            <i class="fa-solid fa-key mr-1"></i> Reset Password
                        </label>
                        <input type="password" name="new_password" id="new_password" placeholder="Mật khẩu mới (min 6 ký tự)"
                               class="w-full px-3 py-2 border border-amber-300 rounded-lg mb-2">
                        <button type="button" onclick="generatePassword()" class="text-xs text-amber-700 hover:text-amber-900 font-bold">
                            <i class="fa-solid fa-wand-magic-sparkles mr-1"></i> Tạo ngẫu nhiên
                        </button>
                    </div>
                </div>
                
                <div class="flex gap-2 mt-6">
                   <button 
    class="btn-update-tier opacity-50 cursor-not-allowed pointer-events-none"
    disabled
>
    Update Tier
</button>
                    <button type="submit" name="action" value="change_status" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-bold py-2 rounded-lg">
                        Update Status
                    </button>
                </div>
                
                <button type="submit" name="action" value="reset_password" onclick="return confirmReset()"
                        class="w-full mt-2 bg-amber-600 hover:bg-amber-700 text-white font-bold py-2 rounded-lg">
                    <i class="fa-solid fa-key mr-1"></i> Reset Password
                </button>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(username, tier, status) {
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_tier').value = tier;
            document.getElementById('edit_status').value = status;
            document.getElementById('new_password').value = '';
            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }
        
        function generatePassword() {
            const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#$%';
            let password = '';
            for (let i = 0; i < 12; i++) password += chars.charAt(Math.floor(Math.random() * chars.length));
            const input = document.getElementById('new_password');
            input.value = password;
            input.type = 'text';
            navigator.clipboard.writeText(password);
            alert('✅ Đã copy: ' + password);
            setTimeout(() => input.type = 'password', 3000);
        }
        
        function confirmReset() {
            const pw = document.getElementById('new_password').value;
            if (!pw || pw.length < 6) { alert('Nhập mật khẩu mới (min 6 ký tự)!'); return false; }
            return confirm('Reset password? Mật khẩu mới: ' + pw);
        }
        
        // Close modal on outside click
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) closeEditModal();
        });
    </script>

    <!-- Mobile Bottom Navigation -->
    <nav class="mobile-bottom-nav flex">
        <a href="index.php">
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
        <a href="users.php" class="active">
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
    </script>

</body>
</html>
