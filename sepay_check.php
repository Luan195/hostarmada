<?php
/**
 * SePay Auto-Check - Chạy định kỳ để kiểm tra giao dịch mới
 * Cron job: chạy mỗi 1-5 phút
 * URL: https://<?php echo getBaseUrl(); ?>/sepay_check.php
 */

require_once 'includes/session.php';
require_once 'includes/functions.php';
require_once 'includes/pricing_data.php';

// SePay API Configuration
$sepayApiKey = 'YOUR_SEPAY_API_KEY'; // Thay bằng API Key của bạn
$sepayApiSecret = 'YOUR_SEPAY_API_SECRET'; // Thay bằng API Secret

$logFile = 'data/sepay_check.log';

// Log start
$log = date('Y-m-d H:i:s') . " - Starting SePay check\n";
file_put_contents($logFile, $log, FILE_APPEND);

// Get pending orders
$orders = loadDB('orders.json');
$pendingOrders = [];

foreach ($orders as $orderId => $order) {
    if ($order['status'] === 'pending') {
        $pendingOrders[$orderId] = $order;
    }
}

if (empty($pendingOrders)) {
    $log = date('Y-m-d H:i:s') . " - No pending orders\n";
    file_put_contents($logFile, $log, FILE_APPEND);
    exit('No pending orders');
}

$log = "Found " . count($pendingOrders) . " pending orders\n";
file_put_contents($logFile, $log, FILE_APPEND);

// SePay API endpoint for transaction list
// Lưu ý: API endpoint có thể thay đổi theo từng tài khoản
$apiUrl = 'https://my.sepay.vn/userapi/transactions/list'; // SePay transaction list API

// Nếu không có API key, sử dụng webhook là chính
// Script này là backup khi webhook không hoạt động

// Demo: Giả lập kiểm tra - trong thực tế sẽ gọi API
/*
try {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $sepayApiKey,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $transactions = json_decode($response, true);
    
    foreach ($transactions as $tx) {
        $amount = floatval($tx['amount']);
        $content = $tx['transfer_content'] ?? '';
        
        // Match với pending orders
        foreach ($pendingOrders as $orderId => $order) {
            $expectedContent = strtolower($order['username'] . ' ' . $order['plan']);
            if (stripos(strtolower($content), strtolower($order['username'])) !== false) {
                if (abs($amount - floatval($order['amount'])) <= 5000) {
                    // Process payment...
                    processPayment($orderId, $order, $tx);
                }
            }
        }
    }
} catch (Exception $e) {
    $log = "Error: " . $e->getMessage() . "\n";
    file_put_contents($logFile, $log, FILE_APPEND);
}
*/

$log = date('Y-m-d H:i:s') . " - Check completed\n";
file_put_contents($logFile, $log, FILE_APPEND);

// Dummy response for cron
echo "OK - Processed " . count($pendingOrders) . " pending orders";