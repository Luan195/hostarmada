<?php
/**
 * Session Initialization - Must be included BEFORE any output
 * This file properly configures and starts PHP sessions
 */

// Configure session settings BEFORE starting session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.cookie_samesite', 'Lax');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load remember_me functions (will auto-load functions.php inside)
require_once __DIR__ . '/remember_me.php';

// ✅ AUTO-LOGIN VIA REMEMBER TOKEN (ONLY if not logged in)
// This runs ONCE per session, not every request
if (!isset($_SESSION['user_login']) || $_SESSION['user_login'] !== true) {
    autoLoginViaRememberToken();
}

// Session security - Regenerate ID periodically
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
    $_SESSION['created'] = time();
} elseif (time() - ($_SESSION['created'] ?? 0) > 1800) { // 30 minutes
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

// Check session timeout (from config.php)
if (isset($_SESSION['login_time'])) {
    $timeout = defined('SESSION_TIMEOUT') ? SESSION_TIMEOUT : 3600;
    if (time() - $_SESSION['login_time'] > $timeout) {
        session_unset();
        session_destroy();
        header('Location: login.php?timeout=1');
        exit();
    }
}

// ✅ AUTO-SYNC TIER FROM users.json (THROTTLED to every 30s)
// This ensures user sees updated tier immediately after admin approval (just F5)
// But doesn't slow down every single page load
if (isset($_SESSION['user_login']) && $_SESSION['user_login'] === true && isset($_SESSION['username'])) {
    $lastTierCheck = $_SESSION['last_tier_check'] ?? 0;
    $now = time();
    
    // Only check tier if more than 30 seconds passed OR tier not set yet
    if (!isset($_SESSION['tier']) || ($now - $lastTierCheck) > 30) {
        $username = $_SESSION['username'];
        
        // Load latest user data from JSON
        $usersFile = __DIR__ . '/../data/users.json';
        if (file_exists($usersFile)) {
            $users = json_decode(file_get_contents($usersFile), true) ?? [];
            
            if (isset($users[$username])) {
               $latestTier = $users[$username]['tier'] ?? 'free';
$latestExpires = $users[$username]['tier_expires_at'] ?? null;

// ✅ CHECK EXPIRE TRƯỚC
if (!empty($latestExpires) && strtotime($latestExpires) > time()) {
    $finalTier = $latestTier;
} else {
    $finalTier = 'free';
}

// ✅ LUÔN UPDATE SESSION (KHÔNG SO SÁNH NỮA)
$_SESSION['tier'] = $finalTier;
$_SESSION['tier_expires'] = $latestExpires;
                
                // Update last check timestamp
                $_SESSION['last_tier_check'] = $now;
            }
        }
    }
}
?>