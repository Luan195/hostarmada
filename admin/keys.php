<?php
// Proper session initialization
require_once '../includes/session.php';
require_once '../includes/functions.php';

// Check admin access
if (!isAdmin()) {
    redirect('login.php');
}

$message = '';
$error = '';

// Handle Add YouTube Key
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_youtube_key') {
    $apiKey = sanitize($_POST['api_key']);
    if (!empty($apiKey)) {
        if (addYouTubeKey($apiKey)) {
            $message = 'YouTube API Key đã được thêm thành công!';
        } else {
            $error = 'Key đã tồn tại hoặc lỗi khi thêm!';
        }
    }
}

// Handle Remove YouTube Key
if (isset($_GET['remove_youtube_key'])) {
    $keyToRemove = $_GET['remove_youtube_key'];
    if (removeYouTubeKey($keyToRemove)) {
        $message = 'Đã xóa YouTube API Key!';
    }
}

// Handle Update Gemini Key
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_gemini') {
    $geminiKey = sanitize($_POST['gemini_key']);
    $keys = loadDB('keys.json');
    $keys['gemini_api_key'] = $geminiKey;
    $keys['last_updated'] = date('Y-m-d H:i:s');
    saveDB('keys.json', $keys);
    $message = 'Gemini API Key đã được cập nhật!';
}

// Handle Update OpenRouter Key
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_openrouter') {
    $openrouterKey = sanitize($_POST['openrouter_key']);
    $keys = loadDB('keys.json');
    $keys['openrouter_api_key'] = $openrouterKey;
    $keys['last_updated'] = date('Y-m-d H:i:s');
    saveDB('keys.json', $keys);
    $message = 'OpenRouter API Key đã được cập nhật!';
}

$keys = getAPIKeys();
$youtubeKeys = $keys['youtube_api_keys'] ?? [];
$geminiKey = $keys['gemini_api_key'] ?? '';
$openrouterKey = $keys['openrouter_api_key'] ?? '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Keys Management - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-100">

    <!-- Admin Header -->
    <header class="bg-slate-900 text-white py-4 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 flex justify-between items-center">
            <h1 class="text-2xl font-black">
                <i class="fa-solid fa-user-shield text-yellow-400 mr-2"></i> Admin Panel
            </h1>
            <nav class="flex gap-4 text-sm">
                <a href="index.php" class="hover:text-yellow-400"><i class="fa-solid fa-dashboard mr-1"></i> Dashboard</a>
                <a href="orders.php" class="hover:text-yellow-400"><i class="fa-solid fa-shopping-cart mr-1"></i> Đơn Hàng</a>
                <a href="users.php" class="hover:text-yellow-400"><i class="fa-solid fa-users mr-1"></i> Users</a>
                <a href="keys.php" class="text-yellow-400 font-bold"><i class="fa-solid fa-key mr-1"></i> API Keys</a>
                <a href="logout.php" class="hover:text-red-400"><i class="fa-solid fa-sign-out mr-1"></i> Logout</a>
            </nav>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 py-8">
            <h2 class="text-3xl font-black mb-8">API Keys Management</h2>

            <?php if ($message): ?>
            <div class="mb-4 bg-green-50 border-l-4 border-green-500 p-4 rounded">
                <p class="text-green-700"><i class="fa-solid fa-check-circle mr-2"></i><?php echo $message; ?></p>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-4 rounded">
                <p class="text-red-700"><i class="fa-solid fa-exclamation-circle mr-2"></i><?php echo $error; ?></p>
            </div>
            <?php endif; ?>

            <!-- YouTube API Keys -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-xl flex items-center gap-2">
                        <i class="fa-brands fa-youtube text-red-600"></i> YouTube Data API Keys
                    </h3>
                    <span class="text-sm text-slate-500"><?php echo count($youtubeKeys); ?> keys active</span>
                </div>

                <!-- Add New Key Form -->
                <form method="POST" class="mb-6">
                    <input type="hidden" name="action" value="add_youtube_key">
                    <div class="flex gap-2">
                        <input type="text" name="api_key" required 
                               class="flex-1 px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-500 outline-none"
                               placeholder="AIzaSy... (Paste YouTube Data API v3 Key)">
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold px-6 py-2 rounded-lg transition">
                            <i class="fa-solid fa-plus mr-2"></i> Add Key
                        </button>
                    </div>
                    <p class="text-xs text-slate-500 mt-2">
                        <i class="fa-solid fa-info-circle mr-1"></i> Multiple keys will rotate automatically to avoid quota limits.
                        <a href="https://console.cloud.google.com/apis/library/youtube.googleapis.com" target="_blank" class="text-blue-600 hover:underline ml-2">Get API Key →</a>
                    </p>
                </form>

                <!-- Keys List -->
                <div class="space-y-2">
                    <?php if (empty($youtubeKeys)): ?>
                    <div class="text-center py-8 text-slate-400">
                        <i class="fa-solid fa-key text-4xl mb-2"></i>
                        <p>No YouTube API keys configured yet</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($youtubeKeys as $key): ?>
                        <div class="flex items-center justify-between bg-slate-50 p-4 rounded-lg border border-slate-200">
                            <div class="flex items-center gap-3 flex-1">
                                <i class="fa-solid fa-key text-slate-400"></i>
                                <code class="font-mono text-sm text-slate-700"><?php echo substr($key, 0, 20) . '...' . substr($key, -10); ?></code>
                            </div>
                            <a href="?remove_youtube_key=<?php echo urlencode($key); ?>" 
                               onclick="return confirm('Are you sure you want to remove this key?')"
                               class="text-red-600 hover:text-red-800 font-bold text-sm">
                                <i class="fa-solid fa-trash mr-1"></i> Remove
                            </a>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Gemini AI Key -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
                <h3 class="font-bold text-xl flex items-center gap-2 mb-4">
                    <i class="fa-solid fa-brain text-purple-600"></i> Gemini AI API Key (Google)
                </h3>
                <form method="POST">
                    <input type="hidden" name="action" value="update_gemini">
                    <div class="flex gap-2">
                        <input type="text" name="gemini_key" 
                               value="<?php echo htmlspecialchars($geminiKey); ?>"
                               class="flex-1 px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-purple-500 outline-none"
                               placeholder="AIzaSy... (Gemini API Key)">
                        <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-bold px-6 py-2 rounded-lg transition">
                            <i class="fa-solid fa-save mr-2"></i> Update
                        </button>
                    </div>
                    <p class="text-xs text-slate-500 mt-2">
                        <i class="fa-solid fa-info-circle mr-1"></i> Used for AI-powered query generation and content analysis.
                        <a href="https://makersuite.google.com/app/apikey" target="_blank" class="text-blue-600 hover:underline ml-2">Get Gemini Key →</a>
                    </p>
                </form>
            </div>

            <!-- OpenRouter Key -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h3 class="font-bold text-xl flex items-center gap-2 mb-4">
                    <i class="fa-solid fa-network-wired text-indigo-600"></i> OpenRouter API Key
                </h3>
                <form method="POST">
                    <input type="hidden" name="action" value="update_openrouter">
                    <div class="flex gap-2">
                        <input type="text" name="openrouter_key" 
                               value="<?php echo htmlspecialchars($openrouterKey); ?>"
                               class="flex-1 px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                               placeholder="sk-or-v1-... (OpenRouter API Key)">
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-6 py-2 rounded-lg transition">
                            <i class="fa-solid fa-save mr-2"></i> Update
                        </button>
                    </div>
                    <p class="text-xs text-slate-500 mt-2">
                        <i class="fa-solid fa-info-circle mr-1"></i> Access GPT-4, Claude 3.5, and other premium models.
                        <a href="https://openrouter.ai/keys" target="_blank" class="text-blue-600 hover:underline ml-2">Get OpenRouter Key →</a>
                    </p>
                </form>
            </div>

    </div>

</body>
</html>
