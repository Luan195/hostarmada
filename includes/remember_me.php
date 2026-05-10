<?php
/**
 * Remember Me Token Management
 * Handles persistent login functionality
 */

// Require functions.php for loadDB() and saveDB()
if (!function_exists('loadDB')) {
    require_once __DIR__ . '/functions.php';
}

/**
 * Generate secure remember token
 */
function generateRememberToken() {
    return bin2hex(random_bytes(32)); // 64-character token
}

/**
 * Set remember me cookie
 */
function setRememberMeCookie($username, $token, $days = 30) {
    $expiry = time() + ($days * 24 * 60 * 60);
    
    // Set cookie with secure flags
    setcookie(
        'remember_token',
        $token,
        [
            'expires' => $expiry,
            'path' => '/',
            'domain' => '',
            'secure' => false, // Set to true if using HTTPS
            'httponly' => true,
            'samesite' => 'Lax'
        ]
    );
    
    setcookie(
        'remember_user',
        $username,
        [
            'expires' => $expiry,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Lax'
        ]
    );
}

/**
 * Clear remember me cookies
 */
function clearRememberMeCookies() {
    setcookie('remember_token', '', time() - 3600, '/');
    setcookie('remember_user', '', time() - 3600, '/');
}

/**
 * Save remember token to user data
 */
function saveRememberToken($username, $token) {
    $users = loadDB('users.json');
    
    if (!isset($users[$username])) {
        return false;
    }
    
    // Store token with metadata
    $users[$username]['remember_tokens'][] = [
        'token' => password_hash($token, PASSWORD_DEFAULT),
        'created_at' => date('Y-m-d H:i:s'),
        'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ];
    
    // Keep only last 5 tokens (for multiple devices)
    if (count($users[$username]['remember_tokens']) > 5) {
        $users[$username]['remember_tokens'] = array_slice($users[$username]['remember_tokens'], -5);
    }
    
    return saveDB('users.json', $users);
}

/**
 * Verify remember token
 */
function verifyRememberToken($username, $token) {
    $users = loadDB('users.json');
    
    if (!isset($users[$username]) || empty($users[$username]['remember_tokens'])) {
        return false;
    }
    
    foreach ($users[$username]['remember_tokens'] as $savedToken) {
        // Check if token matches and not expired
        if (password_verify($token, $savedToken['token'])) {
            if (strtotime($savedToken['expires_at']) > time()) {
                return true;
            }
        }
    }
    
    return false;
}

/**
 * Remove remember token
 */
function removeRememberToken($username, $token) {
    $users = loadDB('users.json');
    
    if (!isset($users[$username])) {
        return false;
    }
    
    if (!empty($users[$username]['remember_tokens'])) {
        $users[$username]['remember_tokens'] = array_filter(
            $users[$username]['remember_tokens'],
            function($savedToken) use ($token) {
                return !password_verify($token, $savedToken['token']);
            }
        );
        $users[$username]['remember_tokens'] = array_values($users[$username]['remember_tokens']);
    }
    
    return saveDB('users.json', $users);
}

/**
 * Clear all remember tokens for user (logout all devices)
 */
function clearAllRememberTokens($username) {
    $users = loadDB('users.json');
    
    if (!isset($users[$username])) {
        return false;
    }
    
    $users[$username]['remember_tokens'] = [];
    return saveDB('users.json', $users);
}

/**
 * Auto-login via remember token
 */
function autoLoginViaRememberToken() {
    // Check if already logged in
    if (isset($_SESSION['user_login']) && $_SESSION['user_login'] === true) {
        return false; // Already logged in
    }
    
    if (!isset($_COOKIE['remember_token']) || !isset($_COOKIE['remember_user'])) {
        return false; // No remember cookie
    }
    
    $username = $_COOKIE['remember_user'];
    $token = $_COOKIE['remember_token'];
    
    if (verifyRememberToken($username, $token)) {
        // Valid token - auto login
        $users = loadDB('users.json');
        
        if (isset($users[$username])) {
            // Set session
            $_SESSION['user_login'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['tier'] = $users[$username]['tier'] ?? 'free';
            $_SESSION['tier_expires'] = $users[$username]['tier_expires_at'] ?? null;
            $_SESSION['login_time'] = time();
            $_SESSION['last_activity'] = time();
            
            // Update last login
            $users[$username]['last_login'] = date('Y-m-d H:i:s');
            saveDB('users.json', $users);
            
            return true;
        }
    } else {
        // Invalid token - clear cookies
        clearRememberMeCookies();
    }
    
    return false;
}
