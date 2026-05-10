<?php
/**
 * API Endpoint: Load User API Keys from Server
 * Returns saved API keys to populate localStorage on login
 */

require_once 'includes/session.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$username = $_SESSION['username'];
$users = loadDB('users.json');

if (!isset($users[$username])) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

// Get API keys
$apiKeys = $users[$username]['api_keys'] ?? [];

// Support multi-key format (store as array)
$geminiKeys = isset($apiKeys['gemini_keys']) && is_array($apiKeys['gemini_keys']) 
    ? $apiKeys['gemini_keys'] 
    : (!empty($apiKeys['gemini']) ? [$apiKeys['gemini']] : []);

$youtubeKeys = isset($apiKeys['youtube_keys']) && is_array($apiKeys['youtube_keys']) 
    ? $apiKeys['youtube_keys'] 
    : (!empty($apiKeys['youtube']) ? [$apiKeys['youtube']] : []);

$openrouterKeys = isset($apiKeys['openrouter_keys']) && is_array($apiKeys['openrouter_keys']) 
    ? $apiKeys['openrouter_keys'] 
    : (!empty($apiKeys['openrouter']) ? [$apiKeys['openrouter']] : []);

// Return keys (including multi-key arrays)
$response = [
    'success' => true,
    'data' => [
        'gemini_keys' => $geminiKeys,
        'youtube_keys' => $youtubeKeys,
        'openrouter_keys' => $openrouterKeys,
        'openai_key' => $apiKeys['openai'] ?? '',
        'updated_at' => $apiKeys['updated_at'] ?? null
    ]
];

echo json_encode($response);
