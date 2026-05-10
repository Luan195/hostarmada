<?php
/**
 * Checkout - SePay Auto-Activation Flow
 * New flow:
 *   1. Page load → auto-create pending order (no form, no button)
 *   2. Show QR + waiting card together
 *   3. SePay webhook fires → order approved
 *   4. Polling detects → success card → redirect to scanner.php
 */

require_once 'includes/session.php';
require_once 'includes/functions.php';
require_once 'includes/pricing_data.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$pricing = getAllPricingPlans();

$selectedPlan = $_GET['plan'] ?? '12m';
if (!isset($pricing[$selectedPlan])) {
    $selectedPlan = '12m';
}
$plan     = $pricing[$selectedPlan];
$currentUser = getCurrentUser();
$username = $_SESSION['username'];

// ─────────────────────────────────────────
// Generate transfer content (SePay format)
// Format: HSHOP {PHONE_LAST4} {PLAN}
//      or HSHOP {USERNAME} {PLAN} (if no phone)
// ─────────────────────────────────────────
$phone       = $currentUser['phone'] ?? '';
$phoneDigits = preg_replace('/[^0-9]/', '', $phone);
$phoneLast4  = strlen($phoneDigits) >= 4 ? substr($phoneDigits, -4) : null;

$safeUsername    = strtoupper(preg_replace('/[^a-zA-Z0-9_]/', '', $username));
$transferContent = $phoneLast4
    ? "HSHOP {$phoneLast4} " . strtoupper($selectedPlan)
    : "HSHOP {$safeUsername} " . strtoupper($selectedPlan);

// ─────────────────────────────────────────
// AUTO-CREATE PENDING ORDER on page load
// Reuse existing pending order if within 2h
// to avoid duplicates on page refresh
// ─────────────────────────────────────────
$orders         = loadDB('orders.json');
$orderId        = null;
$twoHoursAgo    = time() - 7200;

foreach ($orders as $oid => $o) {
    if (
        $o['username'] === $username &&
        $o['plan']     === $selectedPlan &&
        $o['status']   === 'pending' &&
        strtotime($o['created_at']) > $twoHoursAgo
    ) {
        $orderId = $oid;
        break;
    }
}

if (!$orderId) {
    $orderId = 'ORD' . time() . rand(1000, 9999);
    $orders[$orderId] = [
        'order_id'       => $orderId,
        'username'       => $username,
        'plan'           => $selectedPlan,
        'plan_label'     => $plan['name'],
        'amount'         => $plan['sale_price'],
        'duration_days'  => $plan['duration_days'],
        'customer_name'  => $currentUser['name']  ?? $username,
        'customer_phone' => $currentUser['phone'] ?? '',
        'customer_email' => $currentUser['email'] ?? '',
        'transfer_note'  => $transferContent,
        'payment_proof'  => 'sepay_auto',
        'status'         => 'pending',
        'created_at'     => date('Y-m-d H:i:s'),
        'approved_at'    => null,
        'approved_by'    => null,
    ];
    saveDB('orders.json', $orders);

    // Save initial pending record to user order history
    saveUserOrder($username, [
        'plan'           => $selectedPlan,
        'amount'         => floatval($plan['sale_price']),
        'status'         => 'pending',
        'payment_method' => 'SePay Auto',
        'notes'          => 'Awaiting SePay bank confirmation',
    ]);
}

// ─────────────────────────────────────────
// Build VietQR URL
// ─────────────────────────────────────────
$qrAmount  = $plan['sale_price'];
$vietqrUrl = 'https://img.vietqr.io/image/' . BANK_CODE . '-' . BANK_ACCOUNT_NUMBER . '-compact.png'
    . '?amount='      . $qrAmount
    . '&addInfo='     . urlencode($transferContent)
    . '&accountName=' . urlencode(BANK_ACCOUNT_NAME);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh Toán - HSHOP Analytics</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-6px) scale(0.98); }
            to   { opacity: 1; transform: translateY(0)   scale(1);    }
        }
        .fade-in { animation: fadeIn 0.4s ease; }

        @keyframes pulse-ring {
            0%   { transform: scale(1);    opacity: 1; }
            100% { transform: scale(1.35); opacity: 0; }
        }
        .pulse-ring {
            position: absolute; inset: 0;
            border-radius: 9999px;
            border: 3px solid #3b82f6;
            animation: pulse-ring 1.5s ease-out infinite;
        }

        @keyframes qrPulse {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.85; }
        }
        .qr-img { animation: qrPulse 2.5s ease-in-out infinite; }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-purple-50 min-h-screen">

<!-- Header -->
<header class="bg-white shadow-md py-4">
    <div class="max-w-6xl mx-auto px-4 flex justify-between items-center">
        <a href="index.php" class="text-2xl font-black text-red-600">
            <i class="fa-brands fa-youtube"></i> HSHOP Analytics
        </a>
        <a href="pricing.php" class="text-slate-600 hover:text-slate-900 text-sm">
            <i class="fa-solid fa-arrow-left mr-2"></i> Chọn gói khác
        </a>
    </div>
</header>

<div class="max-w-5xl mx-auto px-4 py-10 fade-in">

    <div class="grid md:grid-cols-2 gap-8">

        <!-- ══════════════════════════════════════
             LEFT: QR Code + Bank Info
        ══════════════════════════════════════ -->
        <div class="bg-white rounded-2xl shadow-xl p-8">

            <!-- Plan badge -->
            <div class="bg-gradient-to-br from-yellow-400 to-orange-500 rounded-xl p-5 mb-6 text-white text-center">
                <div class="text-xs uppercase tracking-widest opacity-80 mb-1">Gói đã chọn</div>
                <div class="text-2xl font-black"><?php echo htmlspecialchars($plan['name']); ?></div>
                <div class="text-3xl font-black mt-2"><?php echo number_format($plan['sale_price'], 0, ',', '.'); ?>đ</div>
                <div class="text-xs opacity-75 mt-1"><?php echo htmlspecialchars($plan['duration']); ?></div>
            </div>

            <!-- QR -->
            <div class="text-center mb-5">
                <p class="text-sm font-bold text-slate-700 mb-3">
                    <i class="fa-solid fa-qrcode text-blue-500 mr-1"></i>
                    Quét QR để chuyển khoản
                </p>
                <div class="inline-block bg-white p-3 rounded-2xl shadow-lg border-2 border-blue-200">
                    <img src="<?php echo $vietqrUrl; ?>"
                         alt="VietQR - Tự động điền thông tin"
                         class="qr-img w-56 h-56 rounded-lg"
                         onerror="this.onerror=null;this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22224%22 height=%22224%22%3E%3Crect fill=%22%23f1f5f9%22 width=%22224%22 height=%22224%22/%3E%3Ctext fill=%22%2394a3b8%22 font-family=%22Arial%22 font-size=%2213%22 x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dominant-baseline=%22middle%22%3ELỗi QR - Kiểm tra kết nối%3C/text%3E%3C/svg%3E';">
                </div>
            </div>

            <!-- Auto-fill info -->
            <div class="bg-green-50 border border-green-200 rounded-xl p-4 space-y-2 text-sm">
                <p class="font-bold text-green-800 flex items-center gap-2">
                    <i class="fa-solid fa-circle-check text-green-500"></i>
                    App ngân hàng tự động điền:
                </p>
                
              <div class="flex justify-between bg-white rounded-lg px-3 py-2">
    <span class="text-slate-500">Tên tài khoản</span>
    <span class="font-bold text-blue-700 text-xs">
        <?php echo htmlspecialchars(BANK_ACCOUNT_NAME); ?>
    </span>
</div> <div class="flex justify-between bg-white rounded-lg px-3 py-2">
    <span class="text-slate-500">Số tài khoản</span>
    <span class="font-bold text-blue-700 text-xs">
        <?php echo htmlspecialchars(BANK_ACCOUNT_NUMBER); ?>
    </span>
</div>
                <div class="flex justify-between bg-white rounded-lg px-3 py-2">
                    <span class="text-slate-500">Số tiền</span>
                    <span class="font-bold text-green-700"><?php echo number_format($qrAmount, 0, ',', '.'); ?>đ</span>
                </div>
                <div class="flex justify-between bg-white rounded-lg px-3 py-2">
                    <span class="text-slate-500">Nội dung</span>
                    <span class="font-bold text-blue-700 text-xs"><?php echo htmlspecialchars($transferContent); ?></span>
                </div>
                <div class="flex justify-between bg-white rounded-lg px-3 py-2">
                    <span class="text-slate-500">Ngân hàng</span>
                    <span class="font-bold text-slate-700 text-xs"><?php echo htmlspecialchars(BANK_NAME); ?></span>
                </div>
                <p class="text-xs text-slate-400 pt-1 italic">
                    💡 Mở app ngân hàng → Quét QR → Thông tin tự động điền → Xác nhận
                </p>
            </div>

        </div>

        <!-- ══════════════════════════════════════
             RIGHT: Waiting → Success card
        ══════════════════════════════════════ -->
        <div>

            <!-- ⏳ Waiting card -->
            <div id="waitingCard" class="bg-white rounded-2xl shadow-xl p-8 text-center">

                <!-- Spinner -->
                <div class="relative w-20 h-20 mx-auto mb-6">
                    <div class="pulse-ring"></div>
                    <div class="absolute inset-0 rounded-full border-4 border-blue-100"></div>
                    <div class="absolute inset-0 rounded-full border-4 border-t-blue-500 border-r-blue-300 animate-spin"></div>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <i class="fa-solid fa-wifi text-blue-500 text-xl"></i>
                    </div>
                </div>

                <h2 class="text-xl font-black text-slate-800 mb-1">Đang Chờ Thanh Toán</h2>
                <p class="text-slate-400 text-sm mb-6">Hệ thống lắng nghe giao dịch từ ngân hàng qua SePay...</p>

                <!-- Order info -->
                <div class="bg-slate-50 rounded-xl p-4 mb-5 text-left space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Mã đơn:</span>
                        <span class="font-mono font-bold text-slate-700 text-xs"><?php echo $orderId; ?></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Gói:</span>
                        <span class="font-bold text-blue-600"><?php echo htmlspecialchars($plan['name']); ?></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Số tiền:</span>
                        <span class="font-bold text-green-600"><?php echo number_format($plan['sale_price'], 0, ',', '.'); ?>đ</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Nội dung CK:</span>
                        <span class="font-bold text-blue-600 text-xs"><?php echo htmlspecialchars($transferContent); ?></span>
                    </div>
                </div>

                <!-- Status -->
                <div class="bg-blue-50 rounded-xl p-3 mb-4">
                    <p class="text-blue-600 font-semibold text-sm animate-pulse">
                        <i class="fa-solid fa-satellite-dish mr-1"></i>
                        Đang kiểm tra giao dịch...
                    </p>
                    <p class="text-slate-400 text-xs mt-1">Tự động kiểm tra sau <span id="countdown" class="font-bold text-blue-600">5</span> giây</p>
                </div>

                <!-- Steps -->
                <div class="text-left space-y-2 mb-5">
                    <div class="flex items-center gap-2 text-xs text-slate-500">
                        <i class="fa-solid fa-circle-check text-green-500 text-base w-5"></i>
                        <span>Quét QR hoặc chuyển khoản thủ công</span>
                    </div>
                    <div class="flex items-center gap-2 text-xs text-slate-500">
                        <i class="fa-solid fa-circle-dot text-blue-400 text-base w-5 animate-pulse"></i>
                        <span>SePay nhận xác nhận từ ngân hàng</span>
                    </div>
                    <div class="flex items-center gap-2 text-xs text-slate-400">
                        <i class="fa-regular fa-circle text-slate-300 text-base w-5"></i>
                        <span>Hệ thống kích hoạt tài khoản tự động</span>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 text-xs text-slate-400">
                    Chưa được kích hoạt sau 15 phút?
                    <a href="orders-history.php" class="text-blue-500 hover:underline font-semibold ml-1">Kiểm tra đơn hàng</a>
                </div>
            </div>

            <!-- ✅ Success card (hidden) -->
            <div id="successCard" class="hidden bg-white rounded-2xl shadow-xl p-8 text-center fade-in">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-5">
                    <i class="fa-solid fa-check text-green-500 text-4xl"></i>
                </div>
                <h2 class="text-2xl font-black text-slate-800 mb-1">Kích Hoạt Thành Công!</h2>
                <p class="text-green-600 font-bold mb-1">Tài khoản của bạn đã được nâng cấp 🎉</p>
                <p class="text-slate-400 text-sm mb-5">
                    Chuyển về trang tìm ngách trong
                    <span id="redirectCount" class="font-bold text-blue-600">3</span> giây...
                </p>
                <div class="w-full bg-slate-100 rounded-full h-2 mb-6">
                    <div id="progressBar" class="bg-green-500 h-2 rounded-full transition-all duration-1000" style="width:0%"></div>
                </div>
                <a href="scanner.php?activated=1"
                   class="bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-bold py-3 px-8 rounded-xl inline-block shadow-lg">
                    <i class="fa-solid fa-rocket mr-2"></i>Vào Tìm Ngách Ngay
                </a>
            </div>

        </div>
    </div>

</div>

<script>
(function () {
    const orderId = <?php echo json_encode($orderId); ?>;
    let pollTimer, cdTimer, cdVal = 5;

    function resetCD() {
        cdVal = 5;
        const el = document.getElementById('countdown');
        if (el) el.textContent = cdVal;
        clearInterval(cdTimer);
        cdTimer = setInterval(() => {
            cdVal--;
            if (el) el.textContent = Math.max(0, cdVal);
            if (cdVal <= 0) clearInterval(cdTimer);
        }, 1000);
    }

    function showSuccess() {
        const w = document.getElementById('waitingCard');
        const s = document.getElementById('successCard');
        if (w) w.classList.add('hidden');
        if (s) s.classList.remove('hidden');

        let rem = 3;
        const ce  = document.getElementById('redirectCount');
        const bar = document.getElementById('progressBar');
        if (bar) setTimeout(() => bar.style.width = '33%', 50);

        const t = setInterval(() => {
            rem--;
            if (ce)  ce.textContent = rem;
            if (bar) bar.style.width = ((3 - rem) / 3 * 100) + '%';
            if (rem <= 0) {
                clearInterval(t);
                window.location.href = 'scanner.php?activated=1';
            }
        }, 1000);
    }

    function checkStatus() {
        fetch('api_check_order_status.php?order_id=' + encodeURIComponent(orderId))
            .then(r => r.json())
            .then(d => {
                if (d.status === 'success' && d.order_status === 'approved') {
                    clearInterval(pollTimer);
                    clearInterval(cdTimer);
                    showSuccess();
                } else {
                    resetCD();
                }
            })
            .catch(() => resetCD());
    }

    // First check fast (3s), then every 5s
    setTimeout(checkStatus, 3000);
    pollTimer = setInterval(checkStatus, 5000);
    resetCD();
})();
</script>

</body>
</html>
