<?php
/**
 * API Endpoint: Save User API Keys to Server
 * 
 * Purpose: Synchronize client-side API keys (localStorage) to server-side (users.json)
 * so that FREE AI Tools can access Gemini keys via PHP export
 * 
 * Method: POST
 * Input: JSON body with gemini_keys array, openrouter_key, youtube_keys array
 */

require_once 'includes/session.php';
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Get current user
$currentUser = getCurrentUser();
if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

// Parse JSON input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
    exit;
}

// Extract API keys from input (support both single key and multi-key array)
$geminiInput = isset($input['gemini_keys']) ? $input['gemini_keys'] : (isset($input['gemini_key']) ? $input['gemini_key'] : null);
$youtubeInput = isset($input['youtube_keys']) ? $input['youtube_keys'] : (isset($input['youtube_key']) ? $input['youtube_key'] : null);
$openrouterInput = isset($input['openrouter_keys']) ? $input['openrouter_keys'] : (isset($input['openrouter_key']) ? $input['openrouter_key'] : null);
$openaiKey = isset($input['openai_key']) ? trim($input['openai_key']) : null;

// Handle arrays - use first key for backward compatibility
$geminiKey = null;
if (is_array($geminiInput)) {
    $geminiKey = !empty($geminiInput) ? trim($geminiInput[0]) : null;
} elseif (is_string($geminiInput)) {
    $geminiKey = trim($geminiInput);
}

$youtubeKey = null;
if (is_array($youtubeInput)) {
    $youtubeKey = !empty($youtubeInput) ? trim($youtubeInput[0]) : null;
} elseif (is_string($youtubeInput)) {
    $youtubeKey = trim($youtubeInput);
}

$openrouterKey = null;
if (is_array($openrouterInput)) {
    $openrouterKey = !empty($openrouterInput) ? trim($openrouterInput[0]) : null;
} elseif (is_string($openrouterInput)) {
    $openrouterKey = trim($openrouterInput);
}

// Load users database
$users = loadDB('users.json');
$username = $_SESSION['username'];

if (!isset($users[$username])) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'User not found in database']);
    exit;
}

// Initialize api_keys if not exists
if (!isset($users[$username]['api_keys'])) {
    $users[$username]['api_keys'] = [];
}

// Update API keys - support both single key and multi-key array
// For backward compatibility, store both formats (single key + array)
if ($geminiKey !== null && $geminiKey !== '') {
    $users[$username]['api_keys']['gemini'] = $geminiKey; // Single key (legacy)
    if (is_array($geminiInput) && count($geminiInput) > 1) {
        $users[$username]['api_keys']['gemini_keys'] = $geminiInput; // Multi-key array
    }
}

if ($youtubeKey !== null && $youtubeKey !== '') {
    $users[$username]['api_keys']['youtube'] = $youtubeKey; // Single key (legacy)
    if (is_array($youtubeInput) && count($youtubeInput) > 1) {
        $users[$username]['api_keys']['youtube_keys'] = $youtubeInput; // Multi-key array
    }
}

if ($openrouterKey !== null && $openrouterKey !== '') {
    $users[$username]['api_keys']['openrouter'] = $openrouterKey; // Single key (legacy)
    if (is_array($openrouterInput) && count($openrouterInput) > 1) {
        $users[$username]['api_keys']['openrouter_keys'] = $openrouterInput; // Multi-key array
    }
}

if ($openaiKey !== null && $openaiKey !== '') {
    $users[$username]['api_keys']['openai'] = $openaiKey;
}

// Add timestamp
$users[$username]['api_keys']['updated_at'] = date('Y-m-d H:i:s');

// Save to database
if (saveDB('users.json', $users)) {
    // Update session
    $_SESSION['api_keys'] = $users[$username]['api_keys'];
    
    echo json_encode([
        'success' => true,
        'message' => 'API keys saved successfully',
        'data' => [
            'gemini_count' => is_array($geminiInput) ? count($geminiInput) : (!empty($geminiKey) ? 1 : 0),
            'youtube_count' => is_array($youtubeInput) ? count($youtubeInput) : (!empty($youtubeKey) ? 1 : 0),
            'openrouter_count' => is_array($openrouterInput) ? count($openrouterInput) : (!empty($openrouterKey) ? 1 : 0),
            'openai' => !empty($users[$username]['api_keys']['openai']),
            'updated_at' => $users[$username]['api_keys']['updated_at']
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to save to database']);
}
