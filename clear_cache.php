<?php
/**
 * Cache Clearer - HSHOP Analytics
 * Clears all cached data to refresh the website
 */

require_once 'includes/config.php';

echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Clear Cache - HSHOP Analytics</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; margin: 10px 0; border-radius: 5px; }
        h1 { color: #333; }
        ul { list-style: none; padding: 0; }
        li { padding: 8px 0; border-bottom: 1px solid #eee; }
    </style>
</head>
<body>
    <h1>🗑️ Cache Clearer - HSHOP Analytics</h1>\n";

$cleared = 0;
$errors = 0;

// Clear PHP OPcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "<div class='success'>✅ PHP OPcache cleared successfully</div>\n";
    $cleared++;
} else {
    echo "<div class='info'>ℹ️ OPcache not enabled</div>\n";
}

// Clear session files
$sessionPath = session_save_path();
if (!empty($sessionPath) && is_dir($sessionPath)) {
    $sessions = glob($sessionPath . '/sess_*');
    foreach ($sessions as $sessionFile) {
        if (is_file($sessionFile)) {
            unlink($sessionFile);
        }
    }
    echo "<div class='success'>✅ Cleared " . count($sessions) . " session files</div>\n";
    $cleared++;
}

// Clear template cache (if exists)
$templateCache = BASE_PATH . 'assets/cache/';
if (is_dir($templateCache)) {
    $files = glob($templateCache . '*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    echo "<div class='success'>✅ Cleared template cache</div>\n";
    $cleared++;
} else {
    // Create cache directory
    mkdir($templateCache, 0755, true);
    echo "<div class='info'>ℹ️ Created cache directory</div>\n";
}

// Clear browser cache via meta tags (add to all pages)
echo "<div class='warning'>⚠️ To clear browser cache:<br>";
echo "1. Press <strong>Ctrl + Shift + Delete</strong><br>";
echo "2. Select 'Cached images and files'<br>";
echo "3. Click 'Clear data'<br>";
echo "Or add <code>?v=" . time() . "</code> to URLs to force refresh</div>\n";

// Verify database files
echo "<h2>📊 Database Status</h2>\n";
$databases = [
    'users.json' => DATA_PATH . 'users.json',
    'orders.json' => DATA_PATH . 'orders.json',
    'keys.json' => DATA_PATH . 'keys.json'
];

foreach ($databases as $name => $path) {
    if (file_exists($path)) {
        $size = filesize($path);
        $content = file_get_contents($path);
        $records = count(json_decode($content, true));
        echo "<div class='success'>✅ $name: " . number_format($size) . " bytes, $records records</div>\n";
    } else {
        echo "<div class='warning'>⚠️ $name not found</div>\n";
        $errors++;
    }
}

// Check production mode
echo "<h2>⚙️ Configuration Status</h2>\n";
if (defined('PRODUCTION_MODE')) {
    if (PRODUCTION_MODE) {
        echo "<div class='success'>✅ Production Mode: ON (errors hidden)</div>\n";
    } else {
        echo "<div class='warning'>⚠️ Production Mode: OFF (errors visible) - Enable for live site!</div>\n";
    }
} else {
    echo "<div class='warning'>⚠️ PRODUCTION_MODE not defined</div>\n";
}

echo "<h2>✅ Summary</h2>\n";
echo "<div class='info'>Cleared $cleared cache items, $errors errors</div>\n";
echo "<p><a href='index.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Homepage</a></p>\n";
echo "<p><a href='admin/index.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Admin Panel</a></p>\n";

echo "</body></html>";
?>
