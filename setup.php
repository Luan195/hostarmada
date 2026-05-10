<?php
/**
 * HSHOP Analytics - Setup & Health Check
 * Run this file once after installation to verify system status
 * Access: http://yourdomain.com/setup.php
 * Delete this file after setup is complete!
 */

// Proper session initialization
require_once 'includes/session.php';
require_once 'includes/functions.php';

$checks = [];
$allPassed = true;

// Check PHP Version
$phpVersion = phpversion();
$checks['PHP Version'] = [
    'status' => version_compare($phpVersion, '7.4.0', '>='),
    'message' => $phpVersion >= '7.4' ? "✅ PHP $phpVersion (OK)" : "❌ PHP $phpVersion (Requires 7.4+)",
    'required' => true
];
if (!$checks['PHP Version']['status']) $allPassed = false;

// Check /data directory
$checks['Data Directory'] = [
    'status' => is_dir('data') && is_writable('data'),
    'message' => is_dir('data') && is_writable('data') ? '✅ data/ exists and writable' : '❌ data/ not writable (chmod 755)',
    'required' => true
];
if (!$checks['Data Directory']['status']) $allPassed = false;

// Check database files
$checks['Database Files'] = [
    'status' => file_exists('data/users.json') && file_exists('data/keys.json'),
    'message' => (file_exists('data/users.json') && file_exists('data/keys.json')) ? '✅ Database files initialized' : '⚠️ Will be auto-created on first access',
    'required' => false
];

// Check includes files
$checks['Core Files'] = [
    'status' => file_exists('includes/config.php') && file_exists('includes/functions.php'),
    'message' => (file_exists('includes/config.php') && file_exists('includes/functions.php')) ? '✅ Core files present' : '❌ Missing core files!',
    'required' => true
];
if (!$checks['Core Files']['status']) $allPassed = false;

// Check essential pages
$essentialPages = ['index.php', 'login.php', 'scanner.php', 'logout.php'];
$missingPages = [];
foreach ($essentialPages as $page) {
    if (!file_exists($page)) {
        $missingPages[] = $page;
    }
}
$checks['Essential Pages'] = [
    'status' => empty($missingPages),
    'message' => empty($missingPages) ? '✅ All essential pages present' : '❌ Missing: ' . implode(', ', $missingPages),
    'required' => true
];
if (!$checks['Essential Pages']['status']) $allPassed = false;

// Check admin panel
$checks['Admin Panel'] = [
    'status' => file_exists('admin/index.php') && file_exists('admin/keys.php') && file_exists('admin/users.php'),
    'message' => (file_exists('admin/index.php') && file_exists('admin/keys.php') && file_exists('admin/users.php')) ? '✅ Admin panel complete' : '❌ Admin panel incomplete',
    'required' => true
];
if (!$checks['Admin Panel']['status']) $allPassed = false;

// Check session support
$checks['Session Support'] = [
    'status' => function_exists('session_start'),
    'message' => function_exists('session_start') ? '✅ Sessions enabled' : '❌ Sessions not available',
    'required' => true
];
if (!$checks['Session Support']['status']) $allPassed = false;

// Check JSON support
$checks['JSON Support'] = [
    'status' => function_exists('json_encode') && function_exists('json_decode'),
    'message' => (function_exists('json_encode') && function_exists('json_decode')) ? '✅ JSON functions available' : '❌ JSON not supported',
    'required' => true
];
if (!$checks['JSON Support']['status']) $allPassed = false;

// Try to initialize databases
try {
    initDatabases();
    $dbInit = true;
    $dbMessage = '✅ Database initialization successful';
} catch (Exception $e) {
    $dbInit = false;
    $dbMessage = '❌ Database init failed: ' . $e->getMessage();
    $allPassed = false;
}
$checks['Database Init'] = [
    'status' => $dbInit,
    'message' => $dbMessage,
    'required' => true
];

// Check admin account
$users = loadDB('users.json');
$checks['Admin Account'] = [
    'status' => isset($users['admin']),
    'message' => isset($users['admin']) ? '✅ Default admin account created' : '❌ Admin account missing',
    'required' => true
];
if (!$checks['Admin Account']['status']) $allPassed = false;

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup & Health Check - HSHOP Analytics</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-100 min-h-screen p-8">

    <div class="max-w-4xl mx-auto">
        
        <!-- Header -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8 text-center">
            <div class="inline-block bg-gradient-to-br from-red-600 to-red-700 text-white p-4 rounded-2xl mb-4">
                <i class="fa-brands fa-youtube text-5xl"></i>
            </div>
            <h1 class="text-4xl font-black mb-2">HSHOP Analytics</h1>
            <p class="text-slate-600 text-lg">System Setup & Health Check</p>
        </div>

        <!-- Overall Status -->
        <?php if ($allPassed): ?>
        <div class="bg-green-50 border-l-4 border-green-500 p-6 rounded-xl mb-8">
            <div class="flex items-center gap-4">
                <div class="bg-green-500 text-white p-4 rounded-full">
                    <i class="fa-solid fa-check text-3xl"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-black text-green-900 mb-1">✅ System Ready!</h2>
                    <p class="text-green-700">All checks passed. You can start using the system.</p>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="bg-red-50 border-l-4 border-red-500 p-6 rounded-xl mb-8">
            <div class="flex items-center gap-4">
                <div class="bg-red-500 text-white p-4 rounded-full">
                    <i class="fa-solid fa-exclamation-triangle text-3xl"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-black text-red-900 mb-1">⚠️ Action Required</h2>
                    <p class="text-red-700">Some checks failed. Please fix the issues below.</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Checks List -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8">
            <h3 class="text-2xl font-black mb-6">System Checks</h3>
            <div class="space-y-4">
                <?php foreach ($checks as $name => $check): ?>
                <div class="flex items-start gap-4 p-4 rounded-lg <?php echo $check['status'] ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'; ?>">
                    <div class="<?php echo $check['status'] ? 'text-green-600' : 'text-red-600'; ?> text-2xl">
                        <i class="fa-solid fa-<?php echo $check['status'] ? 'check-circle' : 'times-circle'; ?>"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-bold text-slate-800 mb-1"><?php echo $name; ?></h4>
                        <p class="text-sm <?php echo $check['status'] ? 'text-green-700' : 'text-red-700'; ?>">
                            <?php echo $check['message']; ?>
                        </p>
                        <?php if ($check['required'] && !$check['status']): ?>
                        <span class="inline-block mt-2 text-xs bg-red-100 text-red-800 px-2 py-1 rounded font-bold">REQUIRED</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Quick Start Guide -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8">
            <h3 class="text-2xl font-black mb-6">Quick Start Guide</h3>
            <ol class="space-y-4">
                <li class="flex gap-3">
                    <span class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold">1</span>
                    <div>
                        <h4 class="font-bold mb-1">Login as Admin</h4>
                        <p class="text-sm text-slate-600">Username: <code class="bg-slate-100 px-2 py-1 rounded">admin</code> | Password: <code class="bg-slate-100 px-2 py-1 rounded">Admin@123456</code></p>
                    </div>
                </li>
                <li class="flex gap-3">
                    <span class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold">2</span>
                    <div>
                        <h4 class="font-bold mb-1">Add YouTube API Keys</h4>
                        <p class="text-sm text-slate-600">Go to Admin Panel → API Keys → Add your YouTube Data API v3 keys</p>
                    </div>
                </li>
                <li class="flex gap-3">
                    <span class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold">3</span>
                    <div>
                        <h4 class="font-bold mb-1">Change Admin Password</h4>
                        <p class="text-sm text-slate-600">Edit <code class="bg-slate-100 px-2 py-1 rounded">includes/config.php</code> and change DEFAULT_ADMIN_PASS</p>
                    </div>
                </li>
                <li class="flex gap-3">
                    <span class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold">4</span>
                    <div>
                        <h4 class="font-bold mb-1">Configure Site Settings</h4>
                        <p class="text-sm text-slate-600">Update SITE_URL, SITE_NAME, and contact info in config.php</p>
                    </div>
                </li>
                <li class="flex gap-3">
                    <span class="flex-shrink-0 w-8 h-8 bg-red-600 text-white rounded-full flex items-center justify-center font-bold">5</span>
                    <div>
                        <h4 class="font-bold mb-1 text-red-600">Delete This File!</h4>
                        <p class="text-sm text-slate-600">Remove <code class="bg-slate-100 px-2 py-1 rounded">setup.php</code> after successful setup for security</p>
                    </div>
                </li>
            </ol>
        </div>

        <!-- System Info -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8">
            <h3 class="text-2xl font-black mb-6">System Information</h3>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-slate-500 font-bold">PHP Version:</p>
                    <p class="text-slate-800"><?php echo phpversion(); ?></p>
                </div>
                <div>
                    <p class="text-slate-500 font-bold">Server Software:</p>
                    <p class="text-slate-800"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
                </div>
                <div>
                    <p class="text-slate-500 font-bold">Document Root:</p>
                    <p class="text-slate-800 text-xs font-mono"><?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'; ?></p>
                </div>
                <div>
                    <p class="text-slate-500 font-bold">Current Time:</p>
                    <p class="text-slate-800"><?php echo date('Y-m-d H:i:s'); ?></p>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-4">
            <a href="index.php" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-xl text-center transition">
                <i class="fa-solid fa-home mr-2"></i> Go to Homepage
            </a>
            <a href="login.php" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-bold py-4 rounded-xl text-center transition">
                <i class="fa-solid fa-sign-in-alt mr-2"></i> Go to Login
            </a>
            <a href="admin/index.php" class="flex-1 bg-red-600 hover:bg-red-700 text-white font-bold py-4 rounded-xl text-center transition">
                <i class="fa-solid fa-shield-halved mr-2"></i> Admin Panel
            </a>
        </div>

        <!-- Warning -->
        <div class="mt-8 bg-red-50 border-l-4 border-red-500 p-4 rounded-xl">
            <p class="text-red-800 text-sm">
                <i class="fa-solid fa-exclamation-triangle mr-2"></i>
                <strong>Security Warning:</strong> Delete this file (setup.php) after completing setup!
            </p>
        </div>

    </div>

</body>
</html>
