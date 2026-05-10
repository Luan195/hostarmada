<?php
/**
 * SePay Webhook - Auto activate order
 * URL: https://yourdomain.com/sepay_webhook.php
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/pricing_data.php';

header('Content-Type: application/json');

// Only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Only POST']);
    exit;
}

// Get data
$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (!$data) $data = $_POST;

// Log
$logFile = 'data/sepay_webhook.log';
if (!is_dir('data')) mkdir('data', 0755, true);

function logMsg($msg) {
    global $logFile;
    file_put_contents($logFile, date('Y-m-d H:i:s') . " | $msg\n", FILE_APPEND);
}

// Extract
$amount = floatval($data['transferAmount'] ?? 0);
$content = trim($data['content'] ?? '');
$transactionId = $data['id'] ?? '';

// Validate
if ($amount <= 0 || empty($content)) {
    logMsg("❌ Invalid data: amount=$amount content=$content");
    echo json_encode(['status' => 'error']);
    exit;
}

logMsg("Received: $amount | $content");

// ===============================
// PARSE CONTENT: HSHOP 5678 1M
// ===============================
$username = '';
$plan = '';

// Chuẩn hóa về chữ hoa
$upper = strtoupper($content);


if (preg_match('/HSHOP\s+([A-Z0-9_]+)\s+(TRIAL|1M|3M|6M|12M)/', $upper, $matches)) {

    $username = strtolower($matches[1]);
    $plan     = strtolower($matches[2]);

    logMsg("✅ REGEX MATCH OK");
} else {
    logMsg("❌ REGEX FAIL");
}

logMsg("Parsed → user=$username | plan=$plan");
if (strpos($upper, 'HSHOP') !== false) {

    $afterHSHOP = substr($upper, strpos($upper, 'HSHOP'));

    logMsg("After HSHOP: $afterHSHOP");

    // cắt bỏ phần sau dấu "-"
    $clean = explode('-', $afterHSHOP)[0];

    logMsg("Cleaned: $clean");

    // normalize space
    $clean = preg_replace('/\s+/', ' ', trim($clean));

    $parts = explode(' ', $clean);

    if (count($parts) >= 3) {
        $username = strtolower($parts[1]);
        $plan     = strtolower($parts[2]);
    }
}

// fallback nếu vẫn lỗi
if (empty($username) || empty($plan)) {

    logMsg("Fallback parsing...");

    $parts = explode(' ', strtoupper($content));

    if (count($parts) >= 3 && $parts[0] === 'HSHOP') {
        $username = strtolower($parts[1]);
        $plan     = strtolower($parts[2]);
    }
}

logMsg("Parsed → user=$username | plan=$plan");

// ===============================
// FIND ORDER
// ===============================
$orders = loadDB('orders.json');
$orderId = '';
$orderFound = false;

foreach ($orders as $oid => $o) {
    if ($o['status'] === 'pending') {
        $diff = abs(floatval($o['amount']) - $amount);

        if (
            strtolower($o['username']) === strtolower($username) &&
            $diff <= 5000
        ) {
            $orderId = $oid;
            $orderFound = true;
            break;
        }
    }
}

// AUTO CREATE
if (!$orderFound) {

    $pricing = getAllPricingPlans();

    if (!isset($pricing[$plan])) {
        logMsg("❌ Plan not found: $plan");
        echo json_encode(['status' => 'error']);
        exit;
    }

    $planData = $pricing[$plan];
    $expected = floatval($planData['sale_price']);

    if (abs($amount - $expected) > 5000) {
        logMsg("❌ Amount mismatch: $amount vs $expected");
        echo json_encode(['status' => 'error']);
        exit;
    }

    $orderId = 'ORD' . time() . rand(1000, 9999);

    $orders[$orderId] = [
        'order_id' => $orderId,
        'username' => $username,
        'plan' => $plan,
        'amount' => $expected,
        'duration_days' => $planData['duration_days'],
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s')
    ];

    logMsg("Auto-created order: $orderId");
}
// ✅ UPDATE FULL INFO
$orders[$orderId]['status'] = 'approved';
$orders[$orderId]['approved_at'] = date('Y-m-d H:i:s');
$orders[$orderId]['transaction_id'] = $transactionId;
$orders[$orderId]['paid_amount'] = $amount;
$orders[$orderId]['paid_at'] = date('Y-m-d H:i:s');

// 🔥 QUAN TRỌNG: save ngay
saveDB('orders.json', $orders);

logMsg("✅ Order updated: $orderId");

// ===============================
// ACTIVATE USER
// ===============================
$users = loadDB('users.json');

$tierMap = [
    '1m' => 'basic',
    '3m' => 'basic',
    '6m' => 'vip',
    '12m' => 'vip'
];

$tier = $tierMap[$plan] ?? 'basic';
$days = $orders[$orderId]['duration_days'];

if (isset($users[$username])) {

    $now = time();

    $currentExpire = $users[$username]['tier_expires_at'] ?? null;
    $currentTier   = $users[$username]['tier'] ?? 'free';

    $expireTime = $currentExpire ? strtotime($currentExpire) : 0;

    // =========================
    // 🔥 LOG DEBUG (RẤT QUAN TRỌNG)
    // =========================
    logMsg("DEBUG: currentExpire=$currentExpire | expireTime=$expireTime | now=$now");

    // =========================
    // 🔥 LOGIC CỘNG DỒN CHUẨN
    // =========================
    if ($expireTime > $now) {
        // còn hạn → cộng dồn
        $newExpireTime = $expireTime + ($days * 86400);
        logMsg("➡️ EXTEND FROM OLD EXPIRE");
    } else {
        // hết hạn → tính từ hiện tại
        $newExpireTime = $now + ($days * 86400);
        logMsg("➡️ RESET FROM NOW");
    }

    $newExpire = date('Y-m-d H:i:s', $newExpireTime);

    // =========================
    // CHỐNG DOWNGRADE
    // =========================
    $tierRank = [
        'free'  => 0,
        'trial' => 1,
        'basic' => 2,
        'vip'   => 3
    ];

    if ($tierRank[$tier] < $tierRank[$currentTier]) {
        $tier = $currentTier;
    }

    // =========================
    // UPDATE
    // =========================
    $users[$username]['tier'] = $tier;
    $users[$username]['tier_expires_at'] = $newExpire;
    $users[$username]['tier_updated_at'] = date('Y-m-d H:i:s');

    file_put_contents('data/users.json', json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    logMsg("✅ UPDATED: $username → $tier | expire=$newExpire");
}

echo json_encode([
    'status' => 'success',
    'user' => $username,
    'tier' => $tier
]);