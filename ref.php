<?php
/**
 * Short Referral Link Handler
 * Redirects: domain.com/ref/CODE → login.php?mode=register&ref=CODE
 * 
 * Professional affiliate link shortener
 */

// Get referral code from URL
$refCode = $_GET['code'] ?? '';

// Alternative: Get from path if .htaccess not working
if (empty($refCode) && isset($_SERVER['PATH_INFO'])) {
    $refCode = trim($_SERVER['PATH_INFO'], '/');
}

// Validate referral code (alphanumeric, 6-12 chars)
if (!empty($refCode) && preg_match('/^[A-Z0-9]{6,12}$/i', $refCode)) {
    // Redirect to registration page with referral code
    header('Location: /login.php?mode=register&ref=' . strtoupper($refCode));
    exit;
}

// Invalid or missing code - redirect to homepage
header('Location: /index.php');
exit;
