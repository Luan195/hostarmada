<?php
/**
 * Admin API Keys Management
 * Configure shared YouTube API keys and OpenRouter for tier-based access
 */

require_once 'includes/session.php';
require_once 'includes/functions.php';
require_once 'includes/api_pool.php';

// Admin only
if (!isAdmin()) {
    redirect('scanner.php');
}

$apiPool = new APIKeyPool();
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_youtube_key') {
        $key = trim($_POST['youtube_key'] ?? '');
        $name = trim($_POST['key_name'] ?? '');
        $dailyLimit = intval($_POST['daily_limit'] ?? 1500);
        
        if (!empty($key) && !empty($name)) {
            $keysFile = __DIR__ . '/data/admin_api_keys.json';
            $keys = file_exists($keysFile) ? json_decode(file_get_contents($keysFile), true) : ['youtube_shared' => [], 'openrouter' => ['key' => '', 'status' => 'inactive']];
            
            $keys['youtube_shared'][] = [
                'key' => $key,
                'name' => $name,
                'status' => 'active',
                'daily_limit' => $dailyLimit,
                'usage_today' => 0,
                'reset_at' => date('Y-m-d') . ' 00:00:00'
            ];
            
            file_put_contents($keysFile, json_encode($keys, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $message = '✅ YouTube API key added successfully!';
        } else {
            $error = '❌ Please provide both key and name';
        }
    }
    
    if ($action === 'set_openrouter') {
        $key = trim($_POST['openrouter_key'] ?? '');
        $model = trim($_POST['openrouter_model'] ?? 'google/gemini-2.0-flash-exp:free');
        
        $keysFile = __DIR__ . '/data/admin_api_keys.json';
        $keys = file_exists($keysFile) ? json_decode(file_get_contents($keysFile), true) : ['youtube_shared' => [], 'openrouter' => ['key' => '', 'status' => 'inactive']];
        
        $keys['openrouter'] = [
            'key' => $key,
            'model' => $model,
            'status' => !empty($key) ? 'active' : 'inactive'
        ];
        
        file_put_contents($keysFile, json_encode($keys, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $message = !empty($key) ? '✅ OpenRouter key configured!' : '✅ OpenRouter disabled';
    }
    
    if ($action === 'delete_youtube_key') {
        $index = intval($_POST['key_index'] ?? -1);
        
        $keysFile = __DIR__ . '/data/admin_api_keys.json';
        $keys = file_exists($keysFile) ? json_decode(file_get_contents($keysFile), true) : ['youtube_shared' => [], 'openrouter' => ['key' => '', 'status' => 'inactive']];
        
        if (isset($keys['youtube_shared'][$index])) {
            array_splice($keys['youtube_shared'], $index, 1);
            file_put_contents($keysFile, json_encode($keys, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $message = '✅ Key deleted';
        }
    }
}

// Load current keys
$keysFile = __DIR__ . '/data/admin_api_keys.json';
$currentKeys = file_exists($keysFile) ? json_decode(file_get_contents($keysFile), true) : ['youtube_shared' => [], 'openrouter' => ['key' => '', 'status' => 'inactive']];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - API Keys Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-50">
    <div class="max-w-6xl mx-auto px-4 py-8">
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-3xl font-black text-slate-900">
                <i class="fa-solid fa-key text-blue-600"></i> API Keys Management
            </h1>
            <a href="admin.php" class="text-blue-600 hover:text-blue-700 font-bold">
                <i class="fa-solid fa-arrow-left"></i> Back to Admin
            </a>
        </div>

        <?php if ($message): ?>
        <div class="bg-green-50 border-2 border-green-300 rounded-xl p-4 mb-6">
            <p class="text-green-800 font-bold"><?php echo $message; ?></p>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="bg-red-50 border-2 border-red-300 rounded-xl p-4 mb-6">
            <p class="text-red-800 font-bold"><?php echo $error; ?></p>
        </div>
        <?php endif; ?>

        <!-- Tier Strategy Explanation -->
        <div class="bg-gradient-to-br from-blue-50 to-purple-50 border-2 border-blue-300 rounded-2xl p-6 mb-8">
            <h2 class="text-xl font-black text-slate-900 mb-4">
                <i class="fa-solid fa-info-circle text-blue-600"></i> Tier-Based API Strategy
            </h2>
            <div class="grid md:grid-cols-3 gap-4 text-sm">
                <div class="bg-white rounded-xl p-4">
                    <h3 class="font-black text-green-600 mb-2">🚀 TRIAL (39K/3d)</h3>
                    <p class="text-slate-600">Uses <strong>Shared YouTube API keys</strong> below</p>
                    <p class="text-xs text-slate-500 mt-2">Quota shared among all trial users</p>
                </div>
                <div class="bg-white rounded-xl p-4">
                    <h3 class="font-black text-blue-600 mb-2">💎 BASIC (199K/m)</h3>
                    <p class="text-slate-600">Uses <strong>their own API key</strong></p>
                    <p class="text-xs text-slate-500 mt-2">Self-service, no cost to admin</p>
                </div>
                <div class="bg-white rounded-xl p-4">
                    <h3 class="font-black text-yellow-600 mb-2">👑 PREMIUM (1.5M/y)</h3>
                    <p class="text-slate-600">Uses <strong>OpenRouter API</strong> (unlimited)</p>
                    <p class="text-xs text-slate-500 mt-2">Premium experience, no quota limits</p>
                </div>
            </div>
        </div>

        <!-- YouTube API Keys (For Trial Users) -->
        <div class="bg-white rounded-2xl shadow-lg border-2 border-slate-200 p-6 mb-8">
            <h2 class="text-2xl font-black text-slate-900 mb-4">
                <i class="fa-brands fa-youtube text-red-600"></i> Shared YouTube API Keys (Trial Tier)
            </h2>
            <p class="text-sm text-slate-600 mb-6">These keys are shared among Trial users. Add multiple keys for better load balancing.</p>

            <form method="POST" class="bg-slate-50 rounded-xl p-4 mb-6">
                <input type="hidden" name="action" value="add_youtube_key">
                <div class="grid md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Key Name</label>
                        <input type="text" name="key_name" placeholder="e.g., Key 1" 
                               class="w-full px-4 py-2 border-2 border-slate-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">YouTube API Key</label>
                        <input type="text" name="youtube_key" placeholder="AIzaSy..." 
                               class="w-full px-4 py-2 border-2 border-slate-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Daily Limit</label>
                        <input type="number" name="daily_limit" value="1500" 
                               class="w-full px-4 py-2 border-2 border-slate-300 rounded-lg">
                    </div>
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-2 rounded-lg">
                    <i class="fa-solid fa-plus"></i> Add Key
                </button>
            </form>

            <!-- Current Keys -->
            <div class="space-y-3">
                <?php if (empty($currentKeys['youtube_shared'])): ?>
                <p class="text-slate-400 italic">No YouTube API keys configured yet.</p>
                <?php else: ?>
                <?php foreach ($currentKeys['youtube_shared'] as $index => $keyData): ?>
                <div class="bg-slate-50 rounded-lg p-4 flex items-center justify-between">
                    <div class="flex-1">
                        <h3 class="font-bold text-slate-900"><?php echo htmlspecialchars($keyData['name']); ?></h3>
                        <p class="text-xs text-slate-500 font-mono"><?php echo substr($keyData['key'], 0, 20) . '...'; ?></p>
                        <div class="flex gap-4 mt-2 text-xs">
                            <span class="text-green-600">
                                <i class="fa-solid fa-check-circle"></i> <?php echo $keyData['status']; ?>
                            </span>
                            <span class="text-blue-600">
                                Usage: <?php echo $keyData['usage_today']; ?> / <?php echo $keyData['daily_limit']; ?>
                            </span>
                            <span class="text-slate-500">
                                Reset: <?php echo $keyData['reset_at']; ?>
                            </span>
                        </div>
                    </div>
                    <form method="POST" onsubmit="return confirm('Delete this key?')">
                        <input type="hidden" name="action" value="delete_youtube_key">
                        <input type="hidden" name="key_index" value="<?php echo $index; ?>">
                        <button type="submit" class="text-red-600 hover:text-red-700 px-3 py-2">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- OpenRouter API (For Premium Users) -->
        <div class="bg-white rounded-2xl shadow-lg border-2 border-yellow-200 p-6">
            <h2 class="text-2xl font-black text-slate-900 mb-4">
                <i class="fa-solid fa-crown text-yellow-600"></i> OpenRouter API (Premium Tier)
            </h2>
            <p class="text-sm text-slate-600 mb-6">Premium users get unlimited access via OpenRouter. Use free models or paid for better performance.</p>

            <form method="POST" class="bg-yellow-50 rounded-xl p-4">
                <input type="hidden" name="action" value="set_openrouter">
                <div class="grid md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">OpenRouter API Key</label>
                        <input type="text" name="openrouter_key" 
                               value="<?php echo htmlspecialchars($currentKeys['openrouter']['key'] ?? ''); ?>"
                               placeholder="sk-or-v1-..." 
                               class="w-full px-4 py-2 border-2 border-slate-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Model</label>
                        <select name="openrouter_model" class="w-full px-4 py-2 border-2 border-slate-300 rounded-lg">
                            <option value="google/gemini-2.0-flash-exp:free">Gemini 2.0 Flash Exp (FREE)</option>
                            <option value="google/gemini-2.0-flash-thinking-exp:free">Gemini 2.0 Thinking (FREE)</option>
                            <option value="google/gemini-pro-1.5-exp">Gemini Pro 1.5 Exp (FREE)</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="bg-yellow-600 hover:bg-yellow-700 text-white font-bold px-6 py-2 rounded-lg">
                    <i class="fa-solid fa-save"></i> Save OpenRouter Config
                </button>
                <p class="text-xs text-slate-500 mt-3">
                    <i class="fa-solid fa-lightbulb text-yellow-500"></i> 
                    Tip: Use free models to minimize costs while still providing unlimited service to Premium users!
                </p>
            </form>

            <?php if (!empty($currentKeys['openrouter']['key'])): ?>
            <div class="bg-green-50 border border-green-300 rounded-lg p-4 mt-4">
                <p class="text-green-800 font-bold">
                    <i class="fa-solid fa-check-circle"></i> OpenRouter Configured
                </p>
                <p class="text-sm text-slate-600 mt-1">
                    Model: <?php echo $currentKeys['openrouter']['model'] ?? 'N/A'; ?>
                </p>
            </div>
            <?php else: ?>
            <div class="bg-amber-50 border border-amber-300 rounded-lg p-4 mt-4">
                <p class="text-amber-800 font-bold">
                    <i class="fa-solid fa-exclamation-triangle"></i> Not Configured
                </p>
                <p class="text-sm text-slate-600 mt-1">
                    Premium users will need to provide their own API keys until OpenRouter is configured.
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
