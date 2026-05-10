<?php
/**
 * Admin API Keys Overview - CONFIDENTIAL
 * Shows all API keys stored by users in the system
 * Only accessible by admin
 */
require_once '../includes/session.php';
require_once '../includes/functions.php';

// Check admin access
if (!isAdmin()) {
    redirect('login.php');
}

// Load users data
$users = loadDB('users.json');

// Collect all API keys
$apiKeysData = [];
foreach ($users as $username => $user) {
    if (!empty($user['api_keys'])) {
        $apiKeysData[] = [
            'username' => $username,
            'email' => $user['email'],
            'tier' => $user['tier'],
            'api_keys' => $user['api_keys'],
            'created_at' => $user['created_at']
        ];
    }
}

// Stats
$totalUsers = count($users);
$usersWithKeys = count($apiKeysData);
$totalGeminiKeys = 0;
$totalYoutubeKeys = 0;
$totalOpenRouterKeys = 0;
$totalOpenAIKeys = 0;

foreach ($apiKeysData as $data) {
    if (!empty($data['api_keys']['gemini'])) $totalGeminiKeys++;
    if (!empty($data['api_keys']['youtube'])) $totalYoutubeKeys++;
    if (!empty($data['api_keys']['openrouter'])) $totalOpenRouterKeys++;
    if (!empty($data['api_keys']['openai'])) $totalOpenAIKeys++;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Keys Overview - Admin</title>
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
                <a href="keys.php" class="hover:text-yellow-400"><i class="fa-solid fa-key mr-1"></i> API Keys</a>
                <a href="api_keys_overview.php" class="text-yellow-400 font-bold"><i class="fa-solid fa-eye mr-1"></i> Keys Overview</a>
                <a href="logout.php" class="hover:text-red-400"><i class="fa-solid fa-sign-out mr-1"></i> Logout</a>
            </nav>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 py-8">
        
        <!-- Warning Banner -->
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg">
            <div class="flex items-center">
                <i class="fa-solid fa-shield-halved text-2xl mr-3"></i>
                <div>
                    <p class="font-bold">⚠️ CONFIDENTIAL - Admin Only</p>
                    <p class="text-sm">Trang này chứa thông tin nhạy cảm. Không chia sẻ với bất kỳ ai.</p>
                </div>
            </div>
        </div>

        <h2 class="text-3xl font-black mb-8">📊 API Keys Overview</h2>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <div class="text-slate-600 text-sm font-bold mb-2">Total Users</div>
                <div class="text-3xl font-black text-slate-900"><?php echo $totalUsers; ?></div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <div class="text-slate-600 text-sm font-bold mb-2">Users with Keys</div>
                <div class="text-3xl font-black text-blue-600"><?php echo $usersWithKeys; ?></div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <div class="text-slate-600 text-sm font-bold mb-2">Gemini Keys</div>
                <div class="text-3xl font-black text-purple-600"><?php echo $totalGeminiKeys; ?></div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <div class="text-slate-600 text-sm font-bold mb-2">YouTube Keys</div>
                <div class="text-3xl font-black text-red-600"><?php echo $totalYoutubeKeys; ?></div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <div class="text-slate-600 text-sm font-bold mb-2">OpenRouter Keys</div>
                <div class="text-3xl font-black text-green-600"><?php echo $totalOpenRouterKeys; ?></div>
            </div>
        </div>

        <!-- API Keys Table -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-6 border-b border-slate-200 flex items-center justify-between">
                <h3 class="font-bold text-xl">All User API Keys (<?php echo $usersWithKeys; ?>)</h3>
                <button onclick="exportToCSV()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-4 py-2 rounded-lg text-sm">
                    <i class="fa-solid fa-download mr-2"></i> Export CSV
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 border-b">
                        <tr>
                            <th class="p-4 font-bold text-slate-600">Username</th>
                            <th class="p-4 font-bold text-slate-600">Email</th>
                            <th class="p-4 font-bold text-slate-600">Tier</th>
                            <th class="p-4 font-bold text-slate-600">Gemini API</th>
                            <th class="p-4 font-bold text-slate-600">YouTube API</th>
                            <th class="p-4 font-bold text-slate-600">OpenRouter</th>
                            <th class="p-4 font-bold text-slate-600">OpenAI</th>
                            <th class="p-4 font-bold text-slate-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <?php if (empty($apiKeysData)): ?>
                        <tr>
                            <td colspan="8" class="p-8 text-center text-slate-500">
                                <i class="fa-solid fa-inbox text-4xl mb-2"></i>
                                <p>Chưa có user nào lưu API key</p>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($apiKeysData as $data): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="p-4 font-bold"><?php echo htmlspecialchars($data['username']); ?></td>
                            <td class="p-4 text-slate-600"><?php echo htmlspecialchars($data['email']); ?></td>
                            <td class="p-4"><?php echo getTierBadge($data['tier']); ?></td>
                            <td class="p-4">
                                <?php if (!empty($data['api_keys']['gemini'])): ?>
                                <span class="text-xs font-mono text-purple-600 cursor-pointer" onclick="copyToClipboard('<?php echo htmlspecialchars($data['api_keys']['gemini']); ?>')" title="Click to copy">
                                    <?php echo substr($data['api_keys']['gemini'], 0, 20); ?>...
                                    <i class="fa-solid fa-copy ml-1"></i>
                                </span>
                                <?php else: ?>
                                <span class="text-slate-400 text-xs">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4">
                                <?php if (!empty($data['api_keys']['youtube'])): ?>
                                <span class="text-xs font-mono text-red-600 cursor-pointer" onclick="copyToClipboard('<?php echo htmlspecialchars($data['api_keys']['youtube']); ?>')" title="Click to copy">
                                    <?php echo substr($data['api_keys']['youtube'], 0, 20); ?>...
                                    <i class="fa-solid fa-copy ml-1"></i>
                                </span>
                                <?php else: ?>
                                <span class="text-slate-400 text-xs">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4">
                                <?php if (!empty($data['api_keys']['openrouter'])): ?>
                                <span class="text-xs font-mono text-green-600 cursor-pointer" onclick="copyToClipboard('<?php echo htmlspecialchars($data['api_keys']['openrouter']); ?>')" title="Click to copy">
                                    <?php echo substr($data['api_keys']['openrouter'], 0, 20); ?>...
                                    <i class="fa-solid fa-copy ml-1"></i>
                                </span>
                                <?php else: ?>
                                <span class="text-slate-400 text-xs">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4">
                                <?php if (!empty($data['api_keys']['openai'])): ?>
                                <span class="text-xs font-mono text-blue-600 cursor-pointer" onclick="copyToClipboard('<?php echo htmlspecialchars($data['api_keys']['openai']); ?>')" title="Click to copy">
                                    <?php echo substr($data['api_keys']['openai'], 0, 20); ?>...
                                    <i class="fa-solid fa-copy ml-1"></i>
                                </span>
                                <?php else: ?>
                                <span class="text-slate-400 text-xs">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4">
                                <button onclick="viewFullKeys('<?php echo htmlspecialchars($data['username']); ?>')" class="text-blue-600 hover:text-blue-800 font-bold">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- View Full Keys Modal -->
    <div id="viewModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-2xl p-8 max-w-2xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <h3 class="text-2xl font-black mb-6">API Keys Details</h3>
            <div id="modalContent" class="space-y-4"></div>
            <button onclick="closeViewModal()" class="mt-6 w-full bg-slate-300 hover:bg-slate-400 text-slate-800 font-bold py-3 rounded-lg">
                Close
            </button>
        </div>
    </div>

    <script>
        const apiKeysData = <?php echo json_encode($apiKeysData); ?>;

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Đã copy API key vào clipboard!');
            });
        }

        function viewFullKeys(username) {
            const userData = apiKeysData.find(u => u.username === username);
            if (!userData) return;

            const keys = userData.api_keys;
            let html = `
                <div class="mb-4">
                    <p class="text-slate-600 text-sm">User: <strong class="text-slate-900">${username}</strong></p>
                    <p class="text-slate-600 text-sm">Email: <strong class="text-slate-900">${userData.email}</strong></p>
                </div>
            `;

            if (keys.gemini) {
                html += `
                    <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                        <p class="text-sm font-bold text-purple-900 mb-2">Gemini API Key</p>
                        <p class="text-xs font-mono text-purple-700 break-all">${keys.gemini}</p>
                        <button onclick="copyToClipboard('${keys.gemini}')" class="mt-2 text-xs text-purple-600 hover:text-purple-800">
                            <i class="fa-solid fa-copy mr-1"></i> Copy
                        </button>
                    </div>
                `;
            }

            if (keys.youtube) {
                html += `
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <p class="text-sm font-bold text-red-900 mb-2">YouTube API Key</p>
                        <p class="text-xs font-mono text-red-700 break-all">${keys.youtube}</p>
                        <button onclick="copyToClipboard('${keys.youtube}')" class="mt-2 text-xs text-red-600 hover:text-red-800">
                            <i class="fa-solid fa-copy mr-1"></i> Copy
                        </button>
                    </div>
                `;
            }

            if (keys.openrouter) {
                html += `
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <p class="text-sm font-bold text-green-900 mb-2">OpenRouter API Key</p>
                        <p class="text-xs font-mono text-green-700 break-all">${keys.openrouter}</p>
                        <button onclick="copyToClipboard('${keys.openrouter}')" class="mt-2 text-xs text-green-600 hover:text-green-800">
                            <i class="fa-solid fa-copy mr-1"></i> Copy
                        </button>
                    </div>
                `;
            }

            if (keys.openai) {
                html += `
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <p class="text-sm font-bold text-blue-900 mb-2">OpenAI API Key</p>
                        <p class="text-xs font-mono text-blue-700 break-all">${keys.openai}</p>
                        <button onclick="copyToClipboard('${keys.openai}')" class="mt-2 text-xs text-blue-600 hover:text-blue-800">
                            <i class="fa-solid fa-copy mr-1"></i> Copy
                        </button>
                    </div>
                `;
            }

            document.getElementById('modalContent').innerHTML = html;
            document.getElementById('viewModal').classList.remove('hidden');
        }

        function closeViewModal() {
            document.getElementById('viewModal').classList.add('hidden');
        }

        function exportToCSV() {
            let csv = 'Username,Email,Tier,Gemini API,YouTube API,OpenRouter API,OpenAI API\n';
            apiKeysData.forEach(data => {
                csv += `"${data.username}","${data.email}","${data.tier}","${data.api_keys.gemini || ''}","${data.api_keys.youtube || ''}","${data.api_keys.openrouter || ''}","${data.api_keys.openai || ''}"\n`;
            });
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `api_keys_export_${new Date().toISOString().split('T')[0]}.csv`;
            a.click();
        }
    </script>

</body>
</html>
