<?php
// Proper session initialization
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/pricing_data.php'; // ✅ For tier mapping
require_once '../config/lixitet.php'; // 🧧 Lì Xì Tết (SECURE - outside public)

// Check admin access
if (!isAdmin()) {
    redirect('login.php');
}

// Handle order approval
if (isset($_POST['approve_order'])) {
    $orderId = sanitize($_POST['order_id']);
    $orders = loadDB('orders.json');
    
    if (isset($orders[$orderId])) {
        $order = $orders[$orderId];
        $username = $order['username'];
        $planKey = $order['plan']; // trial, 1m, 3m, 6m, 12m
        $durationDays = $order['duration_days'];
        $amount = $order['amount'];
        
        // ✅ MAP PLAN TO TIER
        // Support both regular plans and Tet campaign plans
        $tierMap = [
            'trial' => TIER_TRIAL,
            '1m' => TIER_BASIC,
            '3m' => TIER_BASIC,
            '6m' => TIER_VIP,
            '12m' => TIER_VIP,
            // 🎊 TET CAMPAIGN PLANS
            'tet_6m' => TIER_VIP,   // Tet 6 months → VIP
            'tet_12m' => TIER_VIP   // Tet 12 months → VIP
        ];
        $newTier = $tierMap[$planKey] ?? TIER_TRIAL;
        
        // Update order status
        $orders[$orderId]['status'] = 'approved';
        $orders[$orderId]['approved_at'] = date('Y-m-d H:i:s');
        $orders[$orderId]['approved_by'] = $_SESSION['username'];
        saveDB('orders.json', $orders);
        
     // Activate tier for user
$users = loadDB('users.json');

if (isset($users[$username])) {

    $currentExpire = $users[$username]['tier_expires_at'] ?? null;

    // 🔥 Nếu còn hạn → cộng dồn
    if (!empty($currentExpire) && strtotime($currentExpire) > time()) {

        $newExpire = date(
            'Y-m-d H:i:s',
            strtotime($currentExpire . " +$durationDays days")
        );

    } else {

        // 🔥 Nếu hết hạn → tính lại từ hiện tại
        $newExpire = date(
            'Y-m-d H:i:s',
            strtotime("+$durationDays days")
        );
    }

    // =========================
    // 🔥 CHỐNG DOWNGRADE
    // =========================
    $tierRank = [
        TIER_FREE  => 0,
        TIER_TRIAL => 1,
        TIER_BASIC => 2,
        TIER_VIP   => 3
    ];

    $currentTier = $users[$username]['tier'] ?? TIER_FREE;

    if (isset($tierRank[$newTier]) && isset($tierRank[$currentTier])) {
        if ($tierRank[$newTier] < $tierRank[$currentTier]) {
            $newTier = $currentTier;
        }
    }

    // =========================
    // UPDATE USER
    // =========================
    $users[$username]['tier'] = $newTier;
    $users[$username]['tier_expires_at'] = $newExpire;
    $users[$username]['tier_updated_at'] = date('Y-m-d H:i:s');

    saveDB('users.json', $users);
            // ✅ UPDATE SESSION IMMEDIATELY IF USER IS ONLINE
            // This allows user to F5 and see new tier without logout
            if (session_status() === PHP_SESSION_ACTIVE) {
                // Check if approved user is currently logged in
                if (isset($_SESSION['username']) && $_SESSION['username'] === $username) {
                    $_SESSION['tier'] = $newTier;
                    $_SESSION['tier_expires'] = $users[$username]['tier_expires_at'];
                }
                
                // ✅ ALTERNATIVE: Update session file directly for the user
                // This works even if user is on different page
                $sessionPath = session_save_path();
                if (!empty($sessionPath) && is_dir($sessionPath)) {
                    $sessions = glob($sessionPath . '/sess_*');
                    foreach ($sessions as $sessionFile) {
                        $sessionData = file_get_contents($sessionFile);
                        if (strpos($sessionData, $username) !== false) {
                            // Decode session data
                            session_decode($sessionData);
                            if (isset($_SESSION['username']) && $_SESSION['username'] === $username) {
                                $_SESSION['tier'] = $newTier;
                                $_SESSION['tier_expires'] = $users[$username]['tier_expires_at'];
                                // Re-encode and save
                                $newSessionData = session_encode();
                                file_put_contents($sessionFile, $newSessionData);
                                break;
                            }
                        }
                    }
                }
            }
        }
        
        $successMessage = "Đã kích hoạt gói $planKey cho user $username! Tier: $newTier";
    }
}

// Handle order rejection
if (isset($_POST['reject_order'])) {
    $orderId = sanitize($_POST['order_id']);
    $orders = loadDB('orders.json');
    
    if (isset($orders[$orderId])) {
        $orders[$orderId]['status'] = 'rejected';
        $orders[$orderId]['rejected_at'] = date('Y-m-d H:i:s');
        $orders[$orderId]['rejected_by'] = $_SESSION['username'];
        saveDB('orders.json', $orders);
        $successMessage = "Đã từ chối đơn hàng $orderId!";
    }
}

// ❌ Handle order deletion (for test data cleanup)
// if (isset($_POST['delete_order'])) {
//     $orderId = sanitize($_POST['order_id']);
//     $orders = loadDB('orders.json');
    
//     if (isset($orders[$orderId])) {
//         unset($orders[$orderId]);
//         saveDB('orders.json', $orders);
//         $successMessage = "⚠️ Đã xóa vĩnh viễn đơn hàng $orderId!";
//     }
// }

// Handle AJAX request for auto-refresh
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json');
    $orders = loadDB('orders.json');
    
    $pendingOrders = array_filter($orders, fn($o) => $o['status'] === 'pending');
    $approvedOrders = array_filter($orders, fn($o) => $o['status'] === 'approved');
    
    $pendingCount = count($pendingOrders);
    $approvedCount = count($approvedOrders);
    
    // Get today's revenue
    $today = date('Y-m-d');
    $todayRevenue = 0;
    foreach ($approvedOrders as $order) {
        if (!empty($order['approved_at']) && date('Y-m-d', strtotime($order['approved_at'])) === $today) {
            $todayRevenue += floatval($order['amount'] ?? 0);
        }
    }
    
    echo json_encode([
        'pending' => $pendingCount,
        'approved' => $approvedCount,
        'todayRevenue' => $todayRevenue,
        'lastUpdate' => date('H:i:s')
    ]);
    exit;
}

// Load orders
$orders = loadDB('orders.json');
usort($orders, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));

$pendingOrders = array_filter($orders, fn($o) => $o['status'] === 'pending');
$approvedOrders = array_filter($orders, fn($o) => $o['status'] === 'approved');
$rejectedOrders = array_filter($orders, fn($o) => $o['status'] === 'rejected');
$pendingCount = count($pendingOrders);
$currentPage = 'orders';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Đơn Hàng - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar { width: 260px; min-height: 100vh; }
        .main-content { margin-left: 260px; }
        .nav-item { transition: all 0.2s ease; }
        .nav-item:hover { background: linear-gradient(90deg, rgba(99,102,241,0.1) 0%, transparent 100%); }
        .nav-item.active { background: linear-gradient(90deg, #6366f1 0%, #8b5cf6 100%); color: white !important; }
        .nav-item.active i, .nav-item.active span { color: white !important; }
        @media (max-width: 1024px) { .sidebar { display: none; } .main-content { margin-left: 0; } }
        
        /* Auto-refresh indicator */
        .refresh-indicator {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #10b981;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            z-index: 9999;
            display: none;
            animation: slideIn 0.3s ease;
        }
        .refresh-indicator.show { display: block; }
        @keyframes slideIn {
            from { transform: translateX(400px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .spinner {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
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
            <a href="orders.php" class="nav-item active flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:text-white">
                <i class="fa-solid fa-shopping-cart w-5"></i><span>Đơn Hàng</span>
                <?php if ($pendingCount > 0): ?><span class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full"><?php echo $pendingCount; ?></span><?php endif; ?>
            </a>
            <a href="users.php" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:text-white">
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

    <!-- Refresh Indicator -->
    <div id="refreshIndicator" class="refresh-indicator">
        <span class="spinner"></span>
        <span style="margin-left: 8px;">Đang cập nhật đơn hàng...</span>
    </div>

    <!-- Main Content -->
    <main class="main-content min-h-screen">
        <header class="bg-white shadow-sm px-6 py-4 sticky top-0 z-30">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-black text-slate-800">Quản Lý Đơn Hàng</h1>
                    <p class="text-sm text-slate-500"><span id="pendingCountDisplay"><?php echo $pendingCount; ?></span> đơn chờ duyệt</p>
                </div>
                <div class="text-xs text-slate-500">
                    <i class="fa-solid fa-rotate"></i> Tự động cập nhật mỗi 30s
                </div>
            </div>
        </header>

        <div class="p-6">
        
        <?php if (isset($successMessage)): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg">
            <i class="fa-solid fa-check-circle mr-2"></i> <?php echo $successMessage; ?>
        </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-yellow-500 rounded-xl p-6 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-3xl font-black"><?php echo count($pendingOrders); ?></div>
                        <div class="text-sm opacity-90">Đơn chờ duyệt</div>
                    </div>
                    <i class="fa-solid fa-clock text-5xl opacity-20"></i>
                </div>
            </div>
            
            <div class="bg-green-500 rounded-xl p-6 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-3xl font-black"><?php echo count($approvedOrders); ?></div>
                        <div class="text-sm opacity-90">Đã duyệt</div>
                    </div>
                    <i class="fa-solid fa-check-circle text-5xl opacity-20"></i>
                </div>
            </div>
            
            <div class="bg-red-500 rounded-xl p-6 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-3xl font-black"><?php echo count($rejectedOrders); ?></div>
                        <div class="text-sm opacity-90">Đã từ chối</div>
                    </div>
                    <i class="fa-solid fa-times-circle text-5xl opacity-20"></i>
                </div>
            </div>
        </div>

        <!-- Pending Orders -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
            <h2 class="text-2xl font-black mb-6 flex items-center gap-2">
                <i class="fa-solid fa-clock text-yellow-500"></i>
                Đơn Hàng Chờ Duyệt (<?php echo count($pendingOrders); ?>)
            </h2>

            <?php if (empty($pendingOrders)): ?>
            <p class="text-slate-500 text-center py-8">Không có đơn hàng chờ duyệt</p>
            <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($pendingOrders as $order): ?>
                <div class="border-2 border-yellow-200 bg-yellow-50 rounded-lg p-6">
                    <div class="grid md:grid-cols-3 gap-6">
                        <!-- Order Info -->
                        <div>
                            <div class="text-xs text-slate-500 mb-1">Mã đơn hàng</div>
                            <div class="font-mono font-bold text-lg mb-3"><?php echo $order['order_id']; ?></div>
                            
                            <div class="space-y-2 text-sm">
                                <div><strong>User:</strong> <?php echo htmlspecialchars($order['username']); ?></div>
                                <div><strong>Họ tên:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></div>
                                <div><strong>SĐT:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></div>
                                <div><strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?></div>
                                <div><strong>Gói:</strong> <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded font-bold"><?php echo $order['plan_label']; ?></span></div>
                                <div><strong>Số tiền:</strong> <span class="text-red-600 font-bold"><?php echo number_format($order['amount'], 0, ',', '.'); ?>đ</span></div>
                                <div><strong>Thời gian:</strong> <?php echo $order['created_at']; ?></div>
                            </div>
                        </div>

                        <!-- Payment Proof -->
                        <div class="text-center">
                            <div class="text-sm font-bold mb-2">Chứng từ chuyển khoản:</div>
                            <a href="../<?php echo $order['payment_proof']; ?>" target="_blank">
                                <img src="../<?php echo $order['payment_proof']; ?>" 
                                     class="max-w-full h-64 object-contain mx-auto border-2 border-slate-200 rounded-lg hover:border-blue-500 transition">
                            </a>
                            <p class="text-xs text-slate-500 mt-2">Click để xem full size</p>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-col gap-3">
                            <form method="POST" onsubmit="return confirm('Xác nhận DUYỆT đơn hàng này?');">
                                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                <button type="submit" name="approve_order" 
                                        class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg transition">
                                    <i class="fa-solid fa-check mr-2"></i> Duyệt & Kích VIP
                                </button>
                            </form>
                            
                            <form method="POST" onsubmit="return confirm('Xác nhận TỪ CHỐI đơn hàng này?');">
                                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                <button type="submit" name="reject_order" 
                                        class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded-lg transition">
                                    <i class="fa-solid fa-times mr-2"></i> Từ Chối
                                </button>
                            </form>
                            
                            <div class="text-xs text-slate-600 mt-2 p-3 bg-white rounded border">
                                <strong>Nội dung CK:</strong><br>
                                <code class="text-blue-600"><?php echo htmlspecialchars($order['transfer_note']); ?></code>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Recent Orders History -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-2xl font-black mb-6 flex items-center gap-2">
                <i class="fa-solid fa-history text-blue-500"></i>
                Lịch Sử Đơn Hàng
            </h2>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-100">
                        <tr>
                            <th class="px-4 py-3 text-left">Mã đơn</th>
                            <th class="px-4 py-3 text-left">User</th>
                            <th class="px-4 py-3 text-left">Gói</th>
                            <th class="px-4 py-3 text-left">Số tiền</th>
                            <th class="px-4 py-3 text-left">Trạng thái</th>
                            <th class="px-4 py-3 text-left">Thời gian</th>
                            <th class="px-4 py-3 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $recentOrders = array_slice($orders, 0, 20);
                        foreach ($recentOrders as $order): 
                        ?>
                        <tr class="border-b hover:bg-slate-50">
                            <td class="px-4 py-3 font-mono text-xs"><?php echo $order['order_id']; ?></td>
                            <td class="px-4 py-3 font-bold"><?php echo htmlspecialchars($order['username']); ?></td>
                                                        <td class="px-4 py-3">
                                                            <?php 
                                                            // Display plan label with Tet badge if it's a Tet campaign order
                                                            echo htmlspecialchars($order['plan_label']); 
                                                            if (isset($order['campaign']) && $order['campaign'] === 'tet2026') {
                                                                echo ' <span class="inline-block bg-red-600 text-white text-xs font-bold px-2 py-1 rounded-full">🎊 TẾT</span>';
                                                            }
                                                            ?>
                                                        </td>
                            <td class="px-4 py-3 font-bold text-red-600"><?php echo number_format($order['amount'], 0, ',', '.'); ?>đ</td>
                            <td class="px-4 py-3">
                                <?php if ($order['status'] === 'pending'): ?>
                                <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs font-bold">Chờ duyệt</span>
                                <?php elseif ($order['status'] === 'approved'): ?>
                                <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-bold">Đã duyệt</span>
                                <?php else: ?>
                                <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs font-bold">Từ chối</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-xs"><?php echo $order['created_at']; ?></td>
                            <td class="px-4 py-3">
                                <form method="POST" style="display: inline;" onsubmit="return confirm('⚠️ XÓA VĨNH VIỄN đơn hàng này?\n\nLưu ý: Hành động này KHÔNG THỂ HOÀN TÁC!');">
                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                   <button 
    type="button"
    class="text-gray-400 opacity-50 cursor-not-allowed pointer-events-none font-bold text-xs"
    title="Xóa đơn hàng (Disabled)"
    onclick="return false;"
>
    <i class="fa-solid fa-trash"></i>
</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        </div>
    </main>

    <script>
    // Auto-refresh orders page every 30 seconds
    const REFRESH_INTERVAL = 30000; // 30 seconds
    let lastPendingCount = <?php echo $pendingCount; ?>;
    
    function showRefreshIndicator(message) {
        const indicator = document.getElementById('refreshIndicator');
        indicator.innerHTML = '<span class="spinner"></span><span style="margin-left: 8px;">' + (message || 'Đang cập nhật...') + '</span>';
        indicator.classList.add('show');
        setTimeout(() => {
            indicator.classList.remove('show');
        }, 2000);
    }
    
    function showNotification(title, body) {
        // Try browser notification
        if (window.Notification && Notification.permission === 'granted') {
            new Notification(title, {
                body: body,
                icon: 'https://<?php echo getBaseUrl(); ?>/assets/images/logo.png',
                badge: 'https://<?php echo getBaseUrl(); ?>/assets/images/logo.png'
            });
        }
        
        // Also show toast notification
        const toast = document.createElement('div');
        toast.className = 'fixed top-20 right-4 bg-gradient-to-r from-green-500 to-emerald-500 text-white px-6 py-4 rounded-xl shadow-2xl z-[9999] animate-pulse';
        toast.innerHTML = '<div class="font-bold text-lg">🔔 ' + title + '</div><div class="text-sm opacity-90">' + body + '</div>';
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 5000);
    }
    
    async function checkNewOrders() {
        try {
            showRefreshIndicator('Kiểm tra đơn hàng mới...');
            
            const response = await fetch('orders.php?ajax=1', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Cache-Control': 'no-cache'
                }
            });
            
            if (!response.ok) throw new Error('Network error');
            
            const data = await response.json();
            
            // Check for new pending orders
            if (data.pending !== lastPendingCount) {
                const diff = data.pending - lastPendingCount;
                console.log('📦 Order change detected: ' + lastPendingCount + ' → ' + data.pending);
                
                if (diff > 0) {
                    showNotification('Đơn hàng mới!', 'Có ' + diff + ' đơn chờ duyệt');
                    // Play notification sound
                    try {
                        const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2teleQAEAAA=');
                        audio.volume = 0.5;
                        audio.play().catch(() => {});
                    } catch(e) {}
                }
                
                // Auto reload page
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            }
            
            lastPendingCount = data.pending;
            
            // Update display
            const countEl = document.getElementById('pendingCountDisplay');
            if (countEl) countEl.textContent = data.pending;
            
        } catch (error) {
            console.error('Error checking orders:', error);
        }
    }
    
    // Request notification permission on first user interaction
    document.addEventListener('click', function requestNotifPermission() {
        if (window.Notification && Notification.permission === 'default') {
            Notification.requestPermission().then(permission => {
                console.log('🔔 Notification permission:', permission);
            });
        }
        document.removeEventListener('click', requestNotifPermission);
    }, { once: true });
    
    // Also check immediately after 3 seconds (for initial load)
    setTimeout(checkNewOrders, 3000);
    
    // Start auto-refresh
    setInterval(checkNewOrders, REFRESH_INTERVAL);
    
    // Manual refresh button
    function manualRefresh() {
        showRefreshIndicator('Đang tải lại...');
        window.location.reload();
    }
    
    console.log('✅ Auto-refresh started - Checking for new orders every 30s');
    console.log('💡 Tip: Keep this tab open to receive real-time notifications');
    </script>

</body>
</html>
