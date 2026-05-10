<?php
/**
 * API: Increment AI Deep Dive Usage
 * Called via AJAX when free user completes AI Deep Dive analysis
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

// Increment usage
$result = incrementAIDeepDiveUsage($username);

header('Content-Type: application/json');
echo json_encode([
    'success' => $result,
    'message' => $result ? 'Usage incremented' : 'Failed to increment'
]);