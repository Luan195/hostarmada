<?php
/**
 * API: Check Order Activation Status
 * Called by checkout.php polling JS to detect when SePay webhook activates the order
 * GET: api_check_order_status.php?order_id=ORD123
 */

require_once 'includes/session.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$orderId = trim($_GET['order_id'] ?? '');

if (empty($orderId)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing order_id']);
    exit;
}

$orders = loadDB('orders.json');

if (!isset($orders[$orderId])) {
    echo json_encode(['status' => 'error', 'message' => 'Order not found']);
    exit;
}

$order = $orders[$orderId];

// Security: only the order owner can check
$currentUsername = $_SESSION['username'] ?? '';
if (strcasecmp($order['username'], $currentUsername) !== 0) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

echo json_encode([
    'status'       => 'success',
    'order_status' => $order['status'],
    'plan'         => $order['plan'],
    'plan_label'   => $order['plan_label'] ?? '',
    'approved_at'  => $order['approved_at'] ?? null,
]);
