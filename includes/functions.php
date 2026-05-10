<?php
// =====================================================
//  Analytics - Core Functions Library
// =====================================================

require_once __DIR__ . '/config.php';

// =====================================================
// DATABASE FUNCTIONS
// =====================================================

/**
 * Load JSON database file
 */
function loadDB($filename) {
    $filepath = DATA_PATH . $filename;
    if (!file_exists($filepath)) {
        return [];
    }
    $content = file_get_contents($filepath);
    return json_decode($content, true) ?? [];
}

/**
 * Save data to JSON database
 */
function saveDB($filename, $data) {
    $filepath = DATA_PATH . $filename;
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return file_put_contents($filepath, $json) !== false;
}

/**
 * Initialize database files if they don't exist
 */
function initDatabases() {
    // Create data directory if not exists
    if (!file_exists(DATA_PATH)) {
        mkdir(DATA_PATH, 0755, true);
    }
    
    // Initialize users.json with default admin
    if (!file_exists(USERS_DB)) {
        $defaultUsers = [
            'admin' => [
                'username' => DEFAULT_ADMIN_USER,
                'password' => password_hash(DEFAULT_ADMIN_PASS, PASSWORD_BCRYPT),
                'email' => ADMIN_EMAIL,
                'tier' => TIER_ADMIN,
                'created_at' => date('Y-m-d H:i:s'),
                'last_login' => null,
                'affiliate_code' => generateAffiliateCode('admin'),
                'referred_by' => null,
                'earnings' => 0,
                'status' => 'active'
            ]
        ];
        saveDB('users.json', $defaultUsers);
    }
    
    // Initialize keys.json
    if (!file_exists(KEYS_DB)) {
        $defaultKeys = [
            'youtube_api_keys' => [],
            'gemini_api_key' => '',
            'openrouter_api_key' => '',
            'last_updated' => date('Y-m-d H:i:s')
        ];
        saveDB('keys.json', $defaultKeys);
    }
    
    // Initialize orders.json
    if (!file_exists(ORDERS_DB)) {
        saveDB('orders.json', []);
    }
}

// =====================================================
// AUTHENTICATION FUNCTIONS
// =====================================================

/**
 * User login
 */
function loginUser($username, $password, $rememberMe = false) {
    // Validate inputs
    if (empty($username) || empty($password)) {
        return ['success' => false, 'message' => 'Tên đăng nhập và mật khẩu không được để trống'];
    }
    
    $users = loadDB('users.json');
    
    if (!isset($users[$username])) {
        return ['success' => false, 'message' => 'Tên đăng nhập không tồn tại'];
    }
    
    $user = $users[$username];
    
    // Check if password field exists
    if (!isset($user['password']) || empty($user['password'])) {
        return ['success' => false, 'message' => 'Dữ liệu tài khoản không hợp lệ'];
    }
    
    // Check password
    if (!password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Mật khẩu không chính xác'];
    }
    
    // Check account status
    if (!isset($user['status']) || $user['status'] !== 'active') {
        return ['success' => false, 'message' => 'Tài khoản đã bị khóa'];
    }
    
    // Update last login
    $users[$username]['last_login'] = date('Y-m-d H:i:s');
    saveDB('users.json', $users);
    
    // Set session
    $_SESSION['user_login'] = true;
    $_SESSION['username'] = $username;
    $_SESSION['tier'] = $user['tier'] ?? 'free';
    $_SESSION['tier_expires'] = $user['tier_expires_at'] ?? null;
    $_SESSION['user_email'] = $user['email'] ?? '';
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    
    // ✅ HANDLE REMEMBER ME
    if ($rememberMe) {
        require_once __DIR__ . '/remember_me.php';
        $token = generateRememberToken();
        setRememberMeCookie($username, $token);
        saveRememberToken($username, $token);
    }
    
    return ['success' => true, 'message' => 'Đăng nhập thành công', 'user' => $user];
}

/**
 * User registration
 */
function registerUser($username, $password, $email, $refCode = null) {
    $users = loadDB('users.json');
    
    // Validate username
    if (isset($users[$username])) {
        return ['success' => false, 'message' => 'Tên đăng nhập đã tồn tại'];
    }
    
    if (strlen($username) < 3 || strlen($username) > 20) {
        return ['success' => false, 'message' => 'Tên đăng nhập phải từ 3-20 ký tự'];
    }
    
    // Validate username format (letters and numbers only, no special characters)
    if (!preg_match('/^[a-zA-Z0-9]+$/', $username)) {
        return ['success' => false, 'message' => 'Tên đăng nhập chỉ được chứa chữ cái và số, không chứa ký tự đặc biệt'];
    }
    
    // Validate password
    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Mật khẩu phải ít nhất 6 ký tự'];
    }
    
    // Validate email - Allow all valid email formats including special chars like +, _, ., -
    // Use a more permissive regex that matches RFC 5321 standard
    $emailPattern = '/^[a-zA-Z0-9.!#$%&\'*+\\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)+$/';
    if (!preg_match($emailPattern, $email)) {
        return ['success' => false, 'message' => 'Email không hợp lệ'];
    }
    
    // Check referral code
    $referrer = null;
    if ($refCode) {
        $referrer = findUserByAffiliateCode($refCode);
        if (!$referrer) {
            return ['success' => false, 'message' => 'Mã giới thiệu không hợp lệ'];
        }
    }
    
    // Create new user
    $users[$username] = [
        'username' => $username,
        'password' => password_hash($password, PASSWORD_BCRYPT),
        'email' => $email,
        'tier' => TIER_FREE,
        'created_at' => date('Y-m-d H:i:s'),
        'last_login' => null,
        'affiliate_code' => generateAffiliateCode($username),
        'referred_by' => $refCode,
        'earnings' => 0,
        'status' => 'active',
        'searches_today' => 0,
        'last_search_date' => null
    ];
    
    saveDB('users.json', $users);
    
    return ['success' => true, 'message' => 'Đăng ký thành công'];
}

/**
 * Logout user
 */
function logoutUser() {
    // Clear remember me cookies and tokens
    if (isset($_COOKIE['remember_token']) && isset($_COOKIE['remember_user'])) {
        require_once __DIR__ . '/remember_me.php';
        $username = $_COOKIE['remember_user'];
        $token = $_COOKIE['remember_token'];
        removeRememberToken($username, $token);
        clearRememberMeCookies();
    }
    
    session_unset();
    session_destroy();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_login']) && $_SESSION['user_login'] === true;
}

/**
 * Check if admin is logged in (separate from user login)
 */
function isAdmin() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}
function loginAdmin($username, $password) {
    // Đọc file users
    $users = json_decode(file_get_contents(__DIR__ . '/../data/users.json'), true);

    if (!$users) {
        return ['success' => false, 'message' => 'Không đọc được dữ liệu user'];
    }

    foreach ($users as $user) {
        if (
            $user['username'] === $username &&
            password_verify($password, $user['password'])
        ) {
            // Check quyền admin
            if ($user['tier'] !== 'admin') {
                return ['success' => false, 'message' => 'Bạn không có quyền admin'];
            }

            // Login thành công
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_login_time'] = time();

            return ['success' => true];
        }
    }

    return ['success' => false, 'message' => 'Sai tài khoản hoặc mật khẩu!'];
}

/**
 * Admin login function
 */
// function loginAdmin($username, $password) {
//     if ($username === ADMIN_USERNAME && password_verify($password, ADMIN_PASSWORD_HASH)) {
//         $_SESSION['admin_logged_in'] = true;
//         $_SESSION['admin_username'] = $username;
//         $_SESSION['admin_login_time'] = time();
//         return ['success' => true];
//     }
//     return ['success' => false, 'message' => 'Sai tài khoản hoặc mật khẩu!'];
// }

/**
 * Admin logout function
 */
function logoutAdmin() {
    unset($_SESSION['admin_logged_in']);
    unset($_SESSION['admin_username']);
    unset($_SESSION['admin_login_time']);
}

/**
 * Check if user is VIP tier (for regular users, not admin)
 */
function isVipUser() {
    return isLoggedIn() && $_SESSION['tier'] === TIER_VIP;
}

/**
 * Get current user data
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $users = loadDB('users.json');
    $username = $_SESSION['username'];
    
    return $users[$username] ?? null;
}

/**
 * Update user tier
 * @param string $duration - Can be number of days (e.g., "30", "90", "365") or "permanent"
 */
function updateUserTier($username, $newTier, $duration = '30') {
    $users = loadDB('users.json');
    
    if (!isset($users[$username])) {
        return false;
    }
    
    $users[$username]['tier'] = $newTier;
    $users[$username]['tier_updated_at'] = date('Y-m-d H:i:s');
    
    // ✅ CALCULATE EXPIRY DATE BASED ON DAYS OR PERMANENT
    if ($duration === 'permanent') {
        $users[$username]['tier_expires_at'] = null; // No expiry
    } elseif (is_numeric($duration)) {
        // Duration is number of days (3, 30, 90, 180, 365, etc.)
        $days = (int)$duration;
        $users[$username]['tier_expires_at'] = date('Y-m-d H:i:s', strtotime("+$days days"));
    } else {
        // Fallback for old format (monthly/yearly) - deprecated
        if ($duration === 'monthly') {
            $users[$username]['tier_expires_at'] = date('Y-m-d H:i:s', strtotime('+30 days'));
        } elseif ($duration === 'yearly') {
            $users[$username]['tier_expires_at'] = date('Y-m-d H:i:s', strtotime('+365 days'));
        } else {
            $users[$username]['tier_expires_at'] = date('Y-m-d H:i:s', strtotime('+30 days')); // default
        }
    }
    
    // ✅ UPDATE SESSION IF USER IS CURRENTLY LOGGED IN
    if (isset($_SESSION['username']) && $_SESSION['username'] === $username) {
        $_SESSION['tier'] = $newTier;
        $_SESSION['tier_expires'] = $users[$username]['tier_expires_at'];
    }
    
    return saveDB('users.json', $users);
}

// =====================================================
// PERMISSION & FEATURE CHECKS
// =====================================================

/**
 * Check if user has access to a feature
 */
function hasFeature($feature) {
    global $TIER_FEATURES;
    
    if (!isLoggedIn()) {
        return false;
    }
    
    $tier = $_SESSION['tier'] ?? 'free';
    
    return $TIER_FEATURES[$tier][$feature] ?? false;
}

/**
 * Get user's daily search limit
 */
function getDailySearchLimit() {
    global $TIER_FEATURES;
    
    if (!isLoggedIn()) {
        return 0;
    }
    
    $tier = $_SESSION['tier'] ?? 'free';
    return $TIER_FEATURES[$tier]['max_searches_per_day'] ?? 0;
}

/**
 * Check and increment search count
 */
function canPerformSearch() {
    if (!isLoggedIn()) {
        return ['allowed' => false, 'message' => 'Vui lòng đăng nhập'];
    }
    
    $username = $_SESSION['username'];
    $users = loadDB('users.json');
    
    // Check if user exists in database
    if (!isset($users[$username])) {
        return ['allowed' => false, 'message' => 'User không tồn tại'];
    }
    
    $user = $users[$username];
    
    $limit = getDailySearchLimit();
    
    // Unlimited for VIP/Admin
    if ($limit === -1) {
        return ['allowed' => true, 'remaining' => -1];
    }
    
    // Initialize tracking fields if not exist
    if (!isset($user['last_search_date'])) {
        $user['last_search_date'] = null;
    }
    if (!isset($user['searches_today'])) {
        $user['searches_today'] = 0;
    }
    
    // Reset counter if new day
    $today = date('Y-m-d');
    if ($user['last_search_date'] !== $today) {
        $users[$username]['searches_today'] = 0;
        $users[$username]['last_search_date'] = $today;
        $user['searches_today'] = 0; // Update local variable
    }
    
    // Check limit
    if ($user['searches_today'] >= $limit) {
        return ['allowed' => false, 'message' => "Bạn đã hết lượt tìm kiếm hôm nay ($limit/$limit)"];
    }
    
    // Increment counter
    $users[$username]['searches_today']++;
    saveDB('users.json', $users);
    
    $remaining = $limit - $users[$username]['searches_today'];
    
    return ['allowed' => true, 'remaining' => $remaining];
}

// =====================================================
// AFFILIATE FUNCTIONS
// =====================================================

/**
 * Generate unique affiliate code
 */
function generateAffiliateCode($username) {
    return strtoupper(substr(md5($username . time()), 0, 8));
}

/**
 * Find user by affiliate code
 */
function findUserByAffiliateCode($code) {
    $users = loadDB('users.json');
    
    foreach ($users as $user) {
        if ($user['affiliate_code'] === $code) {
            return $user;
        }
    }
    
    return null;
}

/**
 * Add earnings to affiliate
 */
function addAffiliateEarnings($affiliateCode, $amount) {
    $users = loadDB('users.json');
    
    foreach ($users as $username => $user) {
        if (isset($user['affiliate_code']) && $user['affiliate_code'] === $affiliateCode) {
            // Initialize earnings if not exist
            if (!isset($users[$username]['earnings'])) {
                $users[$username]['earnings'] = 0;
            }
            $users[$username]['earnings'] += $amount;
            saveDB('users.json', $users);
            return true;
        }
    }
    
    return false;
}

// =====================================================
// API KEY MANAGEMENT
// =====================================================

/**
 * Get active API keys
 */
function getAPIKeys() {
    return loadDB('keys.json');
}

/**
 * Add YouTube API key
 */
function addYouTubeKey($apiKey) {
    $keys = loadDB('keys.json');
    
    if (!in_array($apiKey, $keys['youtube_api_keys'])) {
        $keys['youtube_api_keys'][] = $apiKey;
        $keys['last_updated'] = date('Y-m-d H:i:s');
        return saveDB('keys.json', $keys);
    }
    
    return false;
}

/**
 * Remove YouTube API key
 */
function removeYouTubeKey($apiKey) {
    $keys = loadDB('keys.json');
    $keys['youtube_api_keys'] = array_values(array_diff($keys['youtube_api_keys'], [$apiKey]));
    $keys['last_updated'] = date('Y-m-d H:i:s');
    return saveDB('keys.json', $keys);
}

/**
 * Get random YouTube API key (for rotation)
 */
function getRandomYouTubeKey() {
    $keys = loadDB('keys.json');
    $youtubeKeys = $keys['youtube_api_keys'] ?? [];
    
    if (empty($youtubeKeys)) {
        return null;
    }
    
    return $youtubeKeys[array_rand($youtubeKeys)];
}

// =====================================================
// UTILITY FUNCTIONS
// =====================================================

/**
 * Sanitize input
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Redirect helper
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Get tier display name
 */
function getTierName($tier) {
    $names = [
        TIER_FREE => 'Free',
        TIER_TRIAL => 'Dùng Thử',
        TIER_BASIC => 'Basic',
        TIER_VIP => 'VIP',
        TIER_ADMIN => 'Admin'
    ];
    return $names[$tier] ?? 'Unknown';
}

/**
 * Get tier badge HTML
 */
function getTierBadge($tier) {
    $badges = [
        TIER_FREE => '<span class="badge bg-secondary">Free</span>',
        TIER_TRIAL => '<span class="badge bg-info text-dark">Dùng Thử</span>',
        TIER_BASIC => '<span class="badge bg-primary">Basic</span>',
        TIER_VIP => '<span class="badge bg-warning text-dark">VIP</span>',
        TIER_ADMIN => '<span class="badge bg-danger">Admin</span>'
    ];
    return $badges[$tier] ?? '';
}

// =====================================================
// AI DEEP DIVE USAGE TRACKING
// =====================================================

/**
 * Get AI Deep Dive usage info for current user
 */
function getAIDeepDiveUsage($username) {
    $users = loadDB('users.json');
    
    if (!isset($users[$username])) {
        return ['used' => 0, 'limit' => 0, 'remaining' => 0, 'can_use' => false];
    }
    
    $user = $users[$username];
    $tier = $user['tier'] ?? 'free';
    
    // VIP/Trial/Basic users have unlimited access
    if (in_array($tier, [TIER_VIP, TIER_TRIAL, TIER_BASIC, TIER_ADMIN])) {
        return ['used' => 0, 'limit' => -1, 'remaining' => -1, 'can_use' => true, 'unlimited' => true];
    }
    
    // Free tier - check limit
    $used = $user['ai_deep_dive_used'] ?? 0;
    $lastMonth = $user['ai_deep_dive_month'] ?? '';
    $currentMonth = date('Y-m'); // Format: 2026-03
    
    // Reset if new month
    if ($lastMonth !== $currentMonth) {
        $used = 0;
        $users[$username]['ai_deep_dive_used'] = 0;
        $users[$username]['ai_deep_dive_month'] = $currentMonth;
        saveDB('users.json', $users);
    }
    
    $limit = AI_DEEP_DIVE_FREE_LIMIT; // 2 per month
    $remaining = max(0, $limit - $used);
    $canUse = $used < $limit;
    
    return [
        'used' => $used,
        'limit' => $limit,
        'remaining' => $remaining,
        'can_use' => $canUse,
        'unlimited' => false,
        'current_month' => $currentMonth
    ];
}

/**
 * Increment AI Deep Dive usage for user
 */
function incrementAIDeepDiveUsage($username) {
    $users = loadDB('users.json');
    
    if (!isset($users[$username])) {
        return false;
    }
    
    $tier = $users[$username]['tier'] ?? 'free';
    
    // Skip for paid tiers
    if (in_array($tier, [TIER_VIP, TIER_TRIAL, TIER_BASIC, TIER_ADMIN])) {
        return true;
    }
    
    $currentMonth = date('Y-m');
    $lastMonth = $users[$username]['ai_deep_dive_month'] ?? '';
    
    // Reset if new month
    if ($lastMonth !== $currentMonth) {
        $users[$username]['ai_deep_dive_used'] = 1;
        $users[$username]['ai_deep_dive_month'] = $currentMonth;
    } else {
        $users[$username]['ai_deep_dive_used'] = ($users[$username]['ai_deep_dive_used'] ?? 0) + 1;
    }
    
    return saveDB('users.json', $users);
}

/**
 * Save AI Deep Dive analysis to history
 */
function saveAIDeepDiveHistory($username, $channelData) {
    $keys = loadDB('keys.json');
    
    $historyEntry = [
        'id' => uniqid('ai_'),
        'username' => $username,
        'channel_name' => $channelData['name'] ?? '',
        'channel_id' => $channelData['channelId'] ?? '',
        'channel_url' => $channelData['url'] ?? '',
        'analyzed_at' => date('Y-m-d H:i:s'),
        'subscribers' => $channelData['subscribers'] ?? 0,
        'total_videos' => $channelData['totalVideos'] ?? 0,
        'total_views' => $channelData['totalViews'] ?? 0,
        'ai_response' => $channelData['aiResponse'] ?? '' // Store AI analysis result
    ];
    
    // Add to history array
    $keys['ai_deep_dive_history'][] = $historyEntry;
    
    // Keep only last 100 analyses to save space
    if (count($keys['ai_deep_dive_history']) > 100) {
        array_shift($keys['ai_deep_dive_history']); // Remove oldest
    }
    
    $keys['last_updated'] = date('Y-m-d H:i:s');
    
    return saveDB('keys.json', $keys);
}

/**
 * Get AI Deep Dive history for a user
 */
function getAIDeepDiveHistory($username, $limit = 20) {
    $keys = loadDB('keys.json');
    $history = $keys['ai_deep_dive_history'] ?? [];
    
    // Filter by username
    $userHistory = array_filter($history, function($entry) use ($username) {
        return $entry['username'] === $username;
    });
    
    // Sort by date (newest first)
    usort($userHistory, function($a, $b) {
        return strtotime($b['analyzed_at']) - strtotime($a['analyzed_at']);
    });
    
    // Limit results
    return array_slice($userHistory, 0, $limit);
}

/**
 * Save user order to database
 */
function saveUserOrder($username, $orderData) {
    $keys = loadDB('keys.json');
    
    $order = [
        'id' => uniqid('ORD_'),
        'username' => $username,
        'plan' => $orderData['plan'] ?? '',
        'amount' => $orderData['amount'] ?? 0,
        'status' => $orderData['status'] ?? 'pending',
        'payment_method' => $orderData['payment_method'] ?? '',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
        'notes' => $orderData['notes'] ?? ''
    ];
    
    $keys['user_orders'][] = $order;
    
    // Keep only last 1000 orders
    if (count($keys['user_orders']) > 1000) {
        array_shift($keys['user_orders']);
    }
    
    $keys['last_updated'] = date('Y-m-d H:i:s');
    
    return saveDB('keys.json', $keys) ? $order : false;
}

/**
 * Get user's order history
 */
function getUserOrders($username, $limit = 50) {
    $keys = loadDB('keys.json');
    $orders = $keys['user_orders'] ?? [];
    
    // Filter by username
    $userOrders = array_filter($orders, function($order) use ($username) {
        return $order['username'] === $username;
    });
    
    // Sort by date (newest first)
    usort($userOrders, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    // Limit results
    return array_slice($userOrders, 0, $limit);
}
function getBaseUrl(){
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    return $protocol . $_SERVER['HTTP_HOST'];
}

// Initialize databases on first load
initDatabases();

?>


