<?php
// =====================================================
// HSHOP Analytics - System Configuration
// =====================================================

// SYSTEM SETTINGS
define('SITE_NAME', 'HSHOP Analytics');
define('SITE_URL', 'https://yourdomain.com'); // Change this to your domain
define('ADMIN_EMAIL', 'support@hungniwaco.shop');

// PAYMENT INFORMATION
define('BANK_NAME', 'VP Bank (Ngân hàng Việt Nam Thịnh Vượng)');
define('BANK_CODE', 'VPB');           // Bank code for VietQR API (MB = MB Bank)
define('BANK_ACCOUNT_NUMBER', '0944851719');
define('BANK_ACCOUNT_NAME', 'NGUYEN HUU HUNG');
define('VIETQR_IMAGE', 'assets/images/sepay-qr-ndgroup.png'); // SePay QR - Multi-wallet support

// SECURITY
define('SESSION_TIMEOUT', 3600); // 1 hour
define('PASSWORD_SALT', 'NDG_2024_SECURE_SALT_CHANGE_THIS'); // Change this!

// PATHS
define('BASE_PATH', __DIR__ . '/../');
define('DATA_PATH', BASE_PATH . 'data/');
define('INCLUDES_PATH', BASE_PATH . 'includes/');

// DATABASE FILES (JSON)
define('USERS_DB', DATA_PATH . 'users.json');
define('KEYS_DB', DATA_PATH . 'keys.json');
define('ORDERS_DB', DATA_PATH . 'orders.json');

// USER TIERS
define('TIER_FREE', 'free');
define('TIER_TRIAL', 'trial'); // NEW: 39K VND / 3 ngày
define('TIER_BASIC', 'basic');
define('TIER_VIP', 'vip');
define('TIER_ADMIN', 'admin');

// TIER PRICING (VND) - BIG PLAY STRATEGY
define('PRICE_TRIAL', 39000); // 39K VND / 3 ngày
define('PRICE_BASIC_MONTHLY', 99000); // 99K VND / tháng (1 tháng)
define('PRICE_BASIC_YEARLY', 653400); // 653K VND / năm (12 tháng - 45% off)
define('PRICE_VIP_MONTHLY', 99000); // 99K VND / tháng (same as basic for now)
define('PRICE_VIP_YEARLY', 653400); // 653K VND / năm (12 tháng)

// TIER FEATURES
$TIER_FEATURES = [
    TIER_FREE => [
        'scanner_access' => false, // BLOCKED! Không cho tìm kiếm
        'max_searches_per_day' => 0, // Không được search
        'export_csv' => false,
        'api_priority' => 'blocked',
        'support_level' => 'none',
        'can_view_settings' => false, // Không có nút Settings
        'can_view_details' => false, // Không xem chi tiết video
        'show_teaser' => true // Hiển thị 1 kênh blur để tease
    ],
    TIER_TRIAL => [
        'scanner_access' => true,
        'max_searches_per_day' => -1, // ✅ UNLIMITED (user tự gắn API)
        'export_csv' => false, // Trial không export được
        'ai_deep_dive' => false, // Trial không có AI Deep Dive
        'api_priority' => 'normal',
        'support_level' => 'email',
        'can_view_settings' => true,
        'can_view_details' => true,
        'duration_days' => 3 // Hết hạn sau 3 ngày
    ],
    TIER_BASIC => [
        'scanner_access' => true,
        'max_searches_per_day' => -1, // ✅ UNLIMITED (user tự gắn API)
        'export_csv' => true,
        'ai_deep_dive' => false, // Basic không có AI Deep Dive
        'api_priority' => 'normal',
        'support_level' => 'email',
        'can_view_settings' => true,
        'can_view_details' => true
    ],
    TIER_VIP => [
        'scanner_access' => true,
        'max_searches_per_day' => -1, // ✅ UNLIMITED (user tự gắn API)
        'export_csv' => true,
        'ai_deep_dive' => true, // ⭐ PREMIUM EXCLUSIVE: AI Deep Dive with Gemini
        'api_priority' => 'high',
        'support_level' => 'priority',
        'can_view_settings' => true,
        'can_view_details' => true,
        'affiliate_enabled' => true, // VIP có thêm affiliate features
        'commission_rate' => 0.20 // 20%
    ]
];

// AFFILIATE SETTINGS
define('AFFILIATE_COMMISSION', 0.20); // 20% commission
define('MIN_PAYOUT', 50); // Minimum $50 to request payout
define('COOKIE_DURATION', 30); // 30 days affiliate cookie

// PAYMENT GATEWAY (For future integration)
define('PAYMENT_MODE', 'sandbox'); // 'live' or 'sandbox'
define('PAYPAL_CLIENT_ID', ''); // Add your PayPal Client ID
define('STRIPE_PUBLIC_KEY', ''); // Add your Stripe Public Key
define('STRIPE_SECRET_KEY', ''); // Add your Stripe Secret Key

// DEFAULT ADMIN ACCOUNT
define('DEFAULT_ADMIN_USER', 'admin');
define('DEFAULT_ADMIN_PASS', 'Admin@123456'); // CHANGE THIS IMMEDIATELY!

// AI DEEP DIVE LIMITS (Free tier)
define('AI_DEEP_DIVE_FREE_LIMIT', 2); // Free users get 2 AI deep dive per month
define('AI_DEEP_DIVE_RESET_DAYS', 30); // Reset after 30 days

// API RATE LIMITING
define('API_RATE_LIMIT_FREE', 1); // 1 request per day for free users (strict limit)
define('API_RATE_LIMIT_BASIC', 50); // 50 requests per hour for basic
define('API_RATE_LIMIT_VIP', -1); // unlimited for VIP

// CONTACT & SUPPORT
define('SUPPORT_HOTLINE', '0944851719');
define('SUPPORT_EMAIL', 'support@HSHOP.com');
define('TELEGRAM_SUPPORT', '#');

// ADMIN CREDENTIALS
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD_HASH', password_hash('300891nD$#@', PASSWORD_BCRYPT)); // Change password here

// TIMEZONE
date_default_timezone_set('Asia/Ho_Chi_Minh');

// ERROR REPORTING
// Set PRODUCTION_MODE to true when live, false for development
define('PRODUCTION_MODE', true); // ✅ SET TO TRUE FOR LIVE SITE

if (PRODUCTION_MODE) {
    // Production: Hide all errors from users
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
} else {
    // Development: Show all errors
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
}

// SESSION CONFIGURATION (Must be set BEFORE session_start())
// These are now handled in includes/session.php to avoid warnings
// DO NOT call ini_set() for session settings here - it's too late if session already started

?>
