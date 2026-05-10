<?php
require_once 'includes/session.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$currentUser = getCurrentUser();
$username = $_SESSION['username'];

// Get user's order history
$allOrders = loadDB('orders.json');

$orders = [];

// Lọc đúng theo username từ DB
foreach ($allOrders as $order) {
    if (strtolower($order['username']) === strtolower($username)) {

        // GÁN ID CHUẨN (QUAN TRỌNG)
        $order['id'] = $order['order_id'];

        $orders[] = $order;
    }
}

// Sort mới nhất lên đầu
usort($orders, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch Sử Đơn Hàng - HSHOP Analytics</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-slate-50 to-slate-100 min-h-screen">

    <!-- Header -->
    <header class="bg-white shadow-md py-4">
        <div class="max-w-7xl mx-auto px-4 flex justify-between items-center">
            <a href="index.php" class="text-2xl font-black text-red-600">
                <i class="fa-brands fa-youtube"></i> HSHOP Analytics
            </a>
            <a href="scanner.php" class="text-slate-600 hover:text-slate-900">
                <i class="fa-solid fa-arrow-left mr-2"></i> Quay lại
            </a>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-8">
        
        <!-- Page Title -->
        <div class="mb-8">
            <h1 class="text-4xl font-black text-slate-900 mb-2">📦 Lịch Sử Đơn Hàng</h1>
            <p class="text-slate-600 text-lg">Tất cả các gói dịch vụ bạn đã đăng ký</p>
        </div>

        <!-- Orders Table -->
        <div class="bg-white rounded-2xl shadow-lg border-2 border-slate-200 overflow-hidden">
            
            <?php if (empty($orders)): ?>
            <div class="text-center py-20 text-slate-400">
                <i class="fa-solid fa-receipt text-6xl mb-4 opacity-50"></i>
                <p class="text-xl font-bold text-slate-600">Chưa có đơn hàng nào</p>
                <p class="text-sm text-slate-500 mt-2">Bắt đầu sử dụng dịch vụ đầu tiên của bạn!</p>
                <a href="pricing.php" class="inline-block mt-6 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-bold px-8 py-3 rounded-xl hover:shadow-lg transition">
                    <i class="fa-solid fa-crown mr-2"></i>Xem Bảng Giá
                </a>
            </div>
            <?php else: ?>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b-2 border-slate-200 bg-slate-50">
                        <tr>
                            <th class="py-4 px-4 font-black text-slate-700 uppercase text-xs">Mã Đơn</th>
                            <th class="py-4 px-4 font-black text-slate-700 uppercase text-xs">Gói Dịch Vụ</th>
                            <th class="py-4 px-4 font-black text-slate-700 uppercase text-xs text-center">Số Tiền</th>
                            <th class="py-4 px-4 font-black text-slate-700 uppercase text-xs text-center">Phương Thức</th>
                            <th class="py-4 px-4 font-black text-slate-700 uppercase text-xs text-center">Trạng Thái</th>
                            <th class="py-4 px-4 font-black text-slate-700 uppercase text-xs text-right">Ngày Đăng Ký</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($orders as $order): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="py-4 px-4">
                                <span class="font-mono text-xs font-bold text-slate-600"><?php echo htmlspecialchars($order['id']); ?></span>
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex items-center gap-2">
                                    <?php
                                    $planIcons = [
                                        'basic' => ['icon' => 'fa-star', 'color' => 'text-yellow-600'],
                                        'vip' => ['icon' => 'fa-crown', 'color' => 'text-purple-600'],
                                        'trial' => ['icon' => 'fa-flask', 'color' => 'text-blue-600']
                                    ];
                                    $plan = strtolower($order['plan']);
                                    $iconData = $planIcons[$plan] ?? ['icon' => 'fa-tag', 'color' => 'text-slate-600'];
                                    ?>
                                    <i class="fa-solid <?php echo $iconData['icon']; ?> <?php echo $iconData['color']; ?>"></i>
                                    <span class="font-bold text-slate-800 uppercase"><?php echo htmlspecialchars($order['plan']); ?></span>
                                </div>
                            </td>
                            <td class="py-4 px-4 text-center">
                                <span class="font-bold text-green-600"><?php echo number_format($order['amount'], 0, ',', '.'); ?>₫</span>
                            </td>
                            <td class="py-4 px-4 text-center">
                                <span class="text-slate-600 text-xs"><?php echo htmlspecialchars($order['payment_method']); ?></span>
                            </td>
                            <td class="py-4 px-4 text-center">
                                <?php
                                $statusBadges = [
                                    'pending' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-700', 'label' => '⏳ Chờ thanh toán'],
                                    'approved' => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'label' => '✅ Đã thanh toán'],
                                    // 'active' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'label' => '🟢 Đang hoạt động'],
                                    // 'expired' => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'label' => '❌ Hết hạn'],
                                    'rejected' => ['bg' => 'bg-slate-100', 'text' => 'text-slate-700', 'label' => '⚪ Đã hủy']
                                ];
                                $status = strtolower($order['status']);
                                $badge = $statusBadges[$status] ?? $statusBadges['pending'];
                                ?>
                                <span class="<?php echo $badge['bg']; ?> <?php echo $badge['text']; ?> px-3 py-1 rounded-full text-xs font-bold">
                                    <?php echo $badge['label']; ?>
                                </span>
                            </td>
                            <td class="py-4 px-4 text-right">
                                <div class="text-slate-700 font-bold text-xs">
                                    <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                                </div>
                                <div class="text-slate-500 text-xs">
                                    <?php echo time_ago($order['created_at']); ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Statistics Summary -->
        <?php if (!empty($orders)): ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
            <div class="bg-gradient-to-br from-green-500 to-emerald-600 text-white rounded-2xl p-6 shadow-lg">
                <div class="flex items-center gap-3 mb-2">
                    <i class="fa-solid fa-check-circle text-3xl"></i>
                    <div class="text-sm opacity-90">Đơn thành công</div>
                </div>
                <div class="text-4xl font-black">
                    <?php 
                    $successCount = count(array_filter($orders, function($o) {
                        return in_array(strtolower($o['status']), ['paid', 'active']);
                    }));
                    echo $successCount;
                    ?>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-purple-500 to-pink-600 text-white rounded-2xl p-6 shadow-lg">
                <div class="flex items-center gap-3 mb-2">
                    <i class="fa-solid fa-sack-dollar text-3xl"></i>
                    <div class="text-sm opacity-90">Tổng đã chi</div>
                </div>
                <div class="text-4xl font-black">
                    <?php 
                    $totalSpent = array_sum(array_map(function($o) {
                        return in_array(strtolower($o['status']), ['paid', 'active']) ? floatval($o['amount']) : 0;
                    }, $orders));
                    echo number_format($totalSpent / 1000, 0) . 'K';
                    ?>₫
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-blue-500 to-cyan-600 text-white rounded-2xl p-6 shadow-lg">
                <div class="flex items-center gap-3 mb-2">
                    <i class="fa-solid fa-clock-rotate-left text-3xl"></i>
                    <div class="text-sm opacity-90">Đơn gần nhất</div>
                </div>
                <div class="text-2xl font-black">
                    <?php 
                    if (!empty($orders)) {
                        echo time_ago($orders[0]['created_at']);
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </main>

    <footer class="bg-slate-900 text-slate-400 py-8 mt-16">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-sm">© <?php echo date('Y'); ?> HSHOP Media. Hotline: <strong class="text-red-500"><?php echo SUPPORT_HOTLINE; ?></strong></p>
        </div>
    </footer>

</body>
</html>

<?php
function time_ago($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'Vừa xong';
    } elseif ($diff < 3600) {
        $mins = round($diff / 60);
        return "$mins phút trước";
    } elseif ($diff < 86400) {
        $hours = round($diff / 3600);
        return "$hours giờ trước";
    } elseif ($diff < 604800) {
        $days = round($diff / 86400);
        return "$days ngày trước";
    } else {
        return date('d/m/Y', $timestamp);
    }
}
?>
