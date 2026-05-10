<?php
/**
 * API: Save AI Deep Dive Analysis History
 * Called via AJAX when analysis completes
 */

require_once 'includes/session.php';
require_once 'includes/functions.php';

// Check if logged in
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$username = $_SESSION['username'];

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

// Validate required fields
if (empty($data['name']) || empty($data['channelId'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing channel info']);
    exit;
}

// Prepare channel data
$channelData = [
    'name' => $data['name'],
    'channelId' => $data['channelId'],
    'url' => $data['url'] ?? '',
    'subscribers' => intval($data['subscribers'] ?? 0),
    'totalVideos' => intval($data['totalVideos'] ?? 0),
    'totalViews' => intval($data['totalViews'] ?? 0),
    'aiResponse' => $data['aiResponse'] ?? ''
];

// Save to history
$result = saveAIDeepDiveHistory($username, $channelData);

header('Content-Type: application/json');
echo json_encode([
    'success' => $result,
    'message' => $result ? 'History saved successfully' : 'Failed to save history'
]);
