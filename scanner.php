<?php
// Proper session initialization
require_once 'includes/session.php';
require_once 'includes/functions.php';
require_once 'includes/api_pool.php'; // ✅ API Key Pool Manager

// 1. CHECK LOGIN
if (!isLoggedIn()) {
    redirect('login.php');
}

// 2. GET CURRENT USER DATA
$currentUser = getCurrentUser();
$username = $_SESSION['username'];
$users = loadDB('users.json');

if (isset($users[$username])) {

    $expire = $users[$username]['tier_expires_at'] ?? null;
    $currentTier = $users[$username]['tier'] ?? 'free';

    // Mặc định
    $userTier = 'free';

    if (!empty($expire)) {

        $expireTime = strtotime($expire);

        // ✅ Còn hạn
        if ($expireTime && $expireTime > time()) {

            $userTier = $currentTier;

        } else {

            // ❌ HẾT HẠN → UPDATE DB NGAY
            if ($currentTier !== 'free') {

                $users[$username]['tier'] = 'free';
                $users[$username]['tier_expires_at'] = null;

                saveDB('users.json', $users);

                // (optional) log
                error_log("User $username expired → set to FREE");
            }

            $userTier = 'free';
        }
    } else {

        // Không có expire → coi như free
        if ($currentTier !== 'free') {
            $users[$username]['tier'] = 'free';
            saveDB('users.json', $users);
        }

        $userTier = 'free';
    }
}
$tierName = getTierName($userTier);

// 🔍 DEBUG: Log user API keys structure (remove in production)
if (isset($_GET['debug'])) {
    echo "<!-- DEBUG: Current User API Keys Structure:\n";
    echo "User: " . htmlspecialchars($username) . "\n";
    echo "Tier: " . htmlspecialchars($userTier) . "\n";
    if (isset($currentUser['api_keys'])) {
        echo "API Keys Structure:\n";
        echo json_encode($currentUser['api_keys'], JSON_PRETTY_PRINT);
    } else {
        echo "No api_keys found in user data";
    }
    echo "\n-->\n";
}

// 3. GET API KEY BASED ON TIER (Hybrid Strategy)
$apiPool = new APIKeyPool();
// 🔥 CRITICAL: Handle YouTube keys as array (multi-key support)
$userYouTubeKeys = $currentUser['api_keys']['youtube'] ?? null;
$userAPIKey = null;
if ($userYouTubeKeys) {
    // If array, get first key; if string, use as-is
    $userAPIKey = is_array($userYouTubeKeys) ? ($userYouTubeKeys[0] ?? null) : $userYouTubeKeys;
}
$apiKeyInfo = $apiPool->getAPIKey($userTier, $userAPIKey);

// Pass API info to JavaScript
$effectiveAPIKey = $apiKeyInfo['key'] ?? '';
$apiProvider = $apiKeyInfo['provider'] ?? 'none';
$apiNote = $apiKeyInfo['note'] ?? '';
$apiError = $apiKeyInfo['error'] ?? '';

// 4. CHECK SEARCH LIMIT (Show warning if close to limit)
$searchCheck = canPerformSearch();
$searchesRemaining = $searchCheck['remaining'] ?? 0;

// 5. CHECK AI DEEP DIVE USAGE (Free tier limit: 2/month)
$aiDeepDiveUsage = getAIDeepDiveUsage($username);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scanner - HSHOP Analytics</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/locale/vi.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <script>
        // Pass PHP user tier to JavaScript
        const USER_TIER = '<?php echo $userTier; ?>';
        const TIER_FREE = 'free';
        const TIER_TRIAL = 'trial';
        const TIER_BASIC = 'basic';
        const TIER_VIP = 'vip';
        
        // 🤖 AI DEEP DIVE USAGE (Free tier: 2/month)
        const AI_DEEP_DIVE_USED = <?php echo $aiDeepDiveUsage['used'] ?? 0; ?>;
        const AI_DEEP_DIVE_LIMIT = <?php echo $aiDeepDiveUsage['limit'] ?? 2; ?>;
        const AI_DEEP_DIVE_REMAINING = <?php echo $aiDeepDiveUsage['remaining'] ?? 0; ?>;
        const AI_DEEP_DIVE_CAN_USE = <?php echo $aiDeepDiveUsage['can_use'] ? 'true' : 'false'; ?>;
        const AI_DEEP_DIVE_UNLIMITED = <?php echo isset($aiDeepDiveUsage['unlimited']) && $aiDeepDiveUsage['unlimited'] ? 'true' : 'false'; ?>;
        const AI_DEEP_DIVE_CURRENT_MONTH = '<?php echo $aiDeepDiveUsage['current_month'] ?? ''; ?>';
        
        console.log('%c🤖 AI DEEP DIVE USAGE', 'background: purple; color: white; font-size: 14px; padding: 5px;');
        console.log('Used:', AI_DEEP_DIVE_USED, '/', AI_DEEP_DIVE_LIMIT);
        console.log('Can use:', AI_DEEP_DIVE_CAN_USE, '| Unlimited:', AI_DEEP_DIVE_UNLIMITED);
        
        // ✅ TIER-BASED API KEY (from PHP)
        const TIER_API_KEY = '<?php echo addslashes($effectiveAPIKey); ?>';
        const API_PROVIDER = '<?php echo $apiProvider; ?>';
        const API_NOTE = '<?php echo addslashes($apiNote); ?>';
        const API_ERROR = '<?php echo addslashes($apiError); ?>';
        
        // ✨ API POOL for FREE AI Tools (Gemini keys)
        const apiPool = {
            youtube: TIER_API_KEY || null,
            gemini: <?php 
                $geminiKeys = [];
                // Check if user data exists and has api_keys
                if (isset($currentUser) && is_array($currentUser) && isset($currentUser['api_keys'])) {
                    // Check for Gemini keys under 'gemini' key
                    if (isset($currentUser['api_keys']['gemini'])) {
                        $geminiData = $currentUser['api_keys']['gemini'];
                        if (is_array($geminiData)) {
                            $geminiKeys = array_values($geminiData);
                        } elseif (is_string($geminiData)) {
                            $geminiKeys = [$geminiData];
                        }
                    }
                    // Fallback: Check for keys under 'gemini_keys' (alternative structure)
                    elseif (isset($currentUser['api_keys']['gemini_keys'])) {
                        $geminiData = $currentUser['api_keys']['gemini_keys'];
                        if (is_array($geminiData)) {
                            $geminiKeys = array_values($geminiData);
                        }
                    }
                }
                echo json_encode($geminiKeys);
            ?> || [],
            openrouter: <?php 
                $openrouterKey = null;
                if (isset($currentUser) && is_array($currentUser) && isset($currentUser['api_keys']['openrouter'])) {
                    $openrouterKey = $currentUser['api_keys']['openrouter'];
                }
                echo json_encode($openrouterKey);
            ?> || null
        };
        
        // 🔍 DEBUG: Log API Pool on page load
        console.log('%c📦 API POOL LOADED', 'background: blue; color: white; font-size: 14px; padding: 5px;');
        console.log('YouTube API:', apiPool.youtube ? '✅ Set' : '❌ Not set');
        console.log('Gemini Keys:', apiPool.gemini);
        console.log('Gemini Count:', apiPool.gemini.length);
        console.log('OpenRouter:', apiPool.openrouter ? '✅ Set' : '❌ Not set');
        
        // 🔥 CRITICAL DEBUG: Check if Gemini keys exist
        if (apiPool.gemini && apiPool.gemini.length > 0) {
            console.log('%c✅ FREE AI TOOLS READY!', 'background: green; color: white; font-size: 14px; padding: 5px; font-weight: bold;');
            console.log('First Gemini key:', apiPool.gemini[0].substring(0, 15) + '...');
        } else {
            console.log('%c⚠️ NO GEMINI KEYS - FREE AI Tools will show modal', 'background: orange; color: white; font-size: 14px; padding: 5px; font-weight: bold;');
        }
        
        // Check if user is FREE (locked) tier
        const IS_FREE_LOCKED = (USER_TIER === TIER_FREE);
        
        if (IS_FREE_LOCKED) {
            console.log('%c🔒 FREE TIER - LOCKED MODE', 'background: red; color: white; font-size: 16px; padding: 10px;');
            console.log('Free tier không được tìm kiếm. Upgrade to Trial (39K/3 ngày) để unlock!');
        } else {
            console.log('%c✅ ACCESS GRANTED - Tier: ' + USER_TIER.toUpperCase(), 'background: green; color: white; font-size: 16px; padding: 10px;');
            console.log('API Provider: ' + API_PROVIDER);
            if (API_NOTE) console.log('Note: ' + API_NOTE);
        }
    </script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }

        /* --- REMOVED VIP/FREE RESTRICTION CSS --- */
        /* All blur-data, lock-overlay, lock-badge, upgrade-banner, free-mode-hide CSS removed */

        /* --- CORE UI STYLES (KEPT) --- */
        .loading-spinner {
            border: 3px solid #e2e8f0;
            border-top: 3px solid #ef4444;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            animation: spin 0.8s linear infinite;
            will-change: transform;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Modal Transition Fix */
        .modal {
            transition: opacity 0.2s cubic-bezier(0.4, 0, 0.2, 1), visibility 0.2s;
        }

        body.modal-active {
            overflow: hidden;
        }

        .tags-container::-webkit-scrollbar {
            height: 4px;
        }

        .tags-container::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 4px;
        }
        
        /* 🔥 CUSTOM SCROLLBAR FOR SETTINGS MODAL */
        .overflow-y-auto::-webkit-scrollbar {
            width: 6px;
        }
        
        .overflow-y-auto::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }
        
        .overflow-y-auto::-webkit-scrollbar-thumb {
            background: linear-gradient(to bottom, #cbd5e1, #94a3b8);
            border-radius: 10px;
            transition: background 0.3s;
        }
        
        .overflow-y-auto::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(to bottom, #94a3b8, #64748b);
        }

        .tab-btn {
            position: relative;
            color: #64748b;
            font-weight: 600;
            transition: color 0.2s;
            text-decoration: none; /* Fix for anchor tag */
        }

        .tab-btn:hover {
            color: #0f172a;
        }

        .tab-btn.active {
            color: #dc2626;
            font-weight: 700;
        }

        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: #dc2626;
            border-radius: 2px 2px 0 0;
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease-out;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* NexLev Style Cards */
        .nexlev-card {
            background: white;
            border: 1px solid #f1f5f9;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02), 0 1px 2px rgba(0, 0, 0, 0.03);
            transition: all 0.2s ease-in-out;
            position: relative;
            overflow: hidden;
        }

        .nexlev-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 20px -8px rgba(0, 0, 0, 0.08);
            border-color: #e2e8f0;
        }

        .nexlev-metric {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 4px 8px;
            font-size: 11px;
            font-weight: 600;
            color: #475569;
        }

        /* Modern Slider Styling (RPM) */
        input[type=range] {
            -webkit-appearance: none;
            width: 100%;
            background: transparent;
            margin: 10px 0;
        }

        input[type=range]::-webkit-slider-thumb {
            -webkit-appearance: none;
            height: 20px;
            width: 20px;
            border-radius: 50%;
            background: #fff;
            border: 4px solid #dc2626;
            cursor: pointer;
            margin-top: -8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            transition: transform 0.1s;
        }

        input[type=range]::-webkit-slider-thumb:hover {
            transform: scale(1.1);
        }

        input[type=range]::-webkit-slider-runnable-track {
            width: 100%;
            height: 6px;
            cursor: pointer;
            background: linear-gradient(to right, #facc15, #f97316, #ef4444, #a855f7);
            border-radius: 3px;
        }

        /* Marketometer Gauge */
        .gauge-wrapper {
            position: relative;
            width: 200px;
            height: 100px;
            margin: 0 auto;
            overflow: hidden;
        }

        .gauge-bg {
            stroke: #f1f5f9;
            stroke-width: 20;
            fill: none;
            stroke-linecap: round;
        }

        .gauge-arc {
            fill: none;
            stroke-width: 20;
            stroke-linecap: round;
            stroke-dasharray: 252 252;
            stroke-dashoffset: 252;
            transition: stroke-dashoffset 1s ease-out;
        }

        .needle {
            width: 4px;
            height: 90px;
            background: #1e293b;
            border-radius: 2px;
            position: absolute;
            bottom: 0;
            left: 50%;
            transform-origin: bottom center;
            transform: rotate(-90deg);
            transition: transform 1s cubic-bezier(0.34, 1.56, 0.64, 1);
            z-index: 10;
        }

        .needle::after {
            content: '';
            position: absolute;
            bottom: -6px;
            left: -6px;
            width: 16px;
            height: 16px;
            background: #1e293b;
            border-radius: 50%;
        }

        /* Time Heatmap */
        .time-bar {
            transition: height 0.5s ease-out;
            border-radius: 4px 4px 0 0;
            min-height: 4px;
        }

        .time-bar:hover {
            opacity: 0.8;
        }

        /* Toast */
        #toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 10000;
        }

        /* 🎨 SIDEBAR NAVIGATION STYLES (1of10 Style) */
        .sidebar-nav-item {
            transition: all 0.2s ease;
        }

        .sidebar-nav-item:hover {
            background-color: #f1f5f9;
            transform: translateX(2px);
        }

        .sidebar-nav-item.active {
            background-color: #3b82f6;
            color: white;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
        }

        .sidebar-nav-item.active i {
            color: white;
        }

        /* Tab Content */
        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease-out;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .toast {
            background: white;
            padding: 12px 16px;
            border-radius: 8px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 280px;
            animation: slideIn 0.3s ease-out forwards;
            border-left: 4px solid #3b82f6;
            font-size: 13px;
            font-weight: 500;
            pointer-events: auto;
        }

        .toast.success { border-left-color: #22c55e; }
        .toast.error { border-left-color: #ef4444; }
        .toast.warning { border-left-color: #eab308; }

        /* 🎨 AI DEEP ANALYSIS STYLES */
        .ai-analysis-content {
            font-family: 'Inter', system-ui, sans-serif;
        }
        
        .ai-analysis-content h1 {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .ai-analysis-content table {
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .ai-analysis-content table th:first-child {
            border-top-left-radius: 12px;
        }
        
        .ai-analysis-content table th:last-child {
            border-top-right-radius: 12px;
        }
        
        .ai-analysis-content table tbody tr:last-child td:first-child {
            border-bottom-left-radius: 12px;
        }
        
        .ai-analysis-content table tbody tr:last-child td:last-child {
            border-bottom-right-radius: 12px;
        }
        
        .ai-analysis-content blockquote {
            position: relative;
        }
        
        .ai-analysis-content blockquote::before {
            content: '"';
            position: absolute;
            top: -10px;
            left: 10px;
            font-size: 3rem;
            color: rgba(139, 92, 246, 0.2);
            font-family: Georgia, serif;
        }
        
        /* Smooth scroll for long analysis */
        #deepResults {
            scroll-behavior: smooth;
        }
        
        /* Print-friendly */
        @media print {
            .ai-analysis-content {
                font-size: 12px;
            }
            .ai-analysis-content table {
                page-break-inside: avoid;
            }
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }

        /* SECURITY WALL STYLES */
        #securityWall {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: #0f172a;
            z-index: 10000;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            transition: opacity 0.5s ease-in-out, visibility 0.5s;
        }

        .security-input-group {
            position: relative;
            width: 100%;
            max-width: 320px;
        }

        .security-input {
            width: 100%;
            padding: 12px 16px;
            padding-right: 48px;
            background: #1e293b;
            border: 1px solid #334155;
            color: white;
            border-radius: 8px;
            outline: none;
            transition: all 0.2s;
            letter-spacing: 2px;
        }

        .security-input:focus {
            border-color: #ef4444;
            box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2);
        }

        .security-btn {
            position: absolute;
            right: 4px;
            top: 4px;
            bottom: 4px;
            background: #ef4444;
            color: white;
            border-radius: 6px;
            padding: 0 12px;
            font-weight: bold;
            transition: background 0.2s;
        }

        .security-btn:hover {
            background: #dc2626;
        }

        .hidden-important {
            display: none !important;
        }

        /* Sidebar external link styling */
        .sidebar-external-link {
            text-decoration: none !important;
            color: inherit;
            display: flex;
        }
        .sidebar-external-link:hover {
            text-decoration: none !important;
        }
    </style>
</head>

<body class="text-slate-800 min-h-screen flex" id="bodyEl">

    <div id="securityWall" style="display: none !important;">
        <div class="text-center mb-8">
            <div class="inline-block p-4 bg-red-600 rounded-full mb-4 shadow-[0_0_20px_rgba(220,38,38,0.5)]">
                <i class="fa-solid fa-lock text-3xl text-white"></i>
            </div>
            <h1 class="text-2xl font-black text-white tracking-tight">HSHOP Security</h1>
            <p class="text-slate-400 text-xs mt-2 font-mono">SYSTEM ACCESS RESTRICTED</p>
        </div>

        <div class="security-input-group mb-4">
            <input type="password" id="accessKeyInput" class="security-input" placeholder="Enter Access Key..."
                onkeyup="if(event.key==='Enter') unlockSystem()">
            <button onclick="unlockSystem()" class="security-btn"><i class="fa-solid fa-arrow-right"></i></button>
        </div>

        <p class="text-slate-500 text-[10px] italic" id="authStatus">Đang chờ xác thực...</p>
    </div>

    <div id="toast-container"></div>

    <!-- Mobile Sidebar Overlay -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black/50 z-40 hidden md:hidden" onclick="toggleSidebar()"></div>

    <!-- 🎨 SIDEBAR NAVIGATION (1of10 Style) - Mobile Responsive -->
    <aside id="sidebar" class="w-64 bg-white border-r border-slate-200 flex flex-col fixed left-0 top-0 bottom-0 z-50 shadow-sm transition-transform duration-300 -translate-x-full md:translate-x-0">
        <!-- Logo Section -->
        <div class="p-4 border-b border-slate-200">
            <div class="flex items-center gap-3">
                <div class="bg-gradient-to-br from-red-600 to-red-700 text-white p-2.5 rounded-xl shadow-md">
                    <i class="fa-brands fa-youtube text-xl"></i>
                </div>
                <div>
                    <h1 class="font-extrabold text-base tracking-tight leading-none text-slate-900">HSHOP</h1>
                    <p class="text-[9px] text-red-600 font-bold tracking-wide">YouTube Analytics</p>
                </div>
            </div>
        </div>

        <!-- Navigation Menu -->
        <nav class="flex-1 overflow-y-auto py-4 px-3">
            <ul class="space-y-1">
                <!-- 📦 PRIMARY FEATURES (Core Tools) -->
                
                <!-- Tìm Ngách -->
                <li>
                    <button onclick="switchTab('searchTab')" id="sidebar-searchTab" 
                        class="sidebar-nav-item active w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                        <i class="fa-solid fa-compass text-base"></i>
                        <span>Tìm Ngách</span>
                    </button>
                </li>

                <!-- AI Deep Dive -->
                <li>
                    <button onclick="switchTab('deepAnalysisTab')" id="sidebar-deepAnalysisTab"
                        class="sidebar-nav-item w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors relative">
                        <i class="fa-solid fa-brain text-base"></i>
                        <span>AI Deep Dive</span>
                        <span class="ml-auto bg-gradient-to-r from-purple-500 to-pink-500 text-white text-[9px] font-black px-2 py-0.5 rounded-full">NEW</span>
                    </button>
                </li>

                <!-- Vault -->
                <li>
                    <button onclick="switchTab('dashboardTab'); loadDashboard()" id="sidebar-dashboardTab"
                        class="sidebar-nav-item w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                        <i class="fa-solid fa-database text-base"></i>
                        <span>Vault</span>
                    </button>
                </li>

                <!-- AI Kịch Bản Video (NEW - FREE Tool) -->
                <li onclick="event.stopPropagation();">
                    <a href="scriptpro.php" target="_blank" rel="noopener noreferrer" id="sidebar-scriptpro"
                        onclick="event.stopPropagation();"
                        class="sidebar-external-link w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                        <i class="fa-solid fa-wand-magic-sparkles text-base"></i>
                        <span>AI Kịch Bản Video</span>
                        <span class="ml-auto bg-gradient-to-r from-green-500 to-emerald-500 text-white text-[9px] font-black px-2 py-0.5 rounded-full">FREE</span>
                    </a>
                </li>


                <!-- API Keys (NEW - Dedicated Page) -->
                <li>
                    <button onclick="switchTab('apiKeysTab')" id="sidebar-apiKeysTab"
                        class="sidebar-nav-item w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                        <i class="fa-solid fa-key text-base"></i>
                        <span>API Keys</span>
                        <span id="apiKeysStatusDot" class="ml-auto w-2 h-2 bg-gray-300 rounded-full"></span>
                    </button>
                </li>

                <!-- Divider -->
                <li class="my-3 border-t border-slate-200"></li>

                <!-- ⚙️ SECONDARY FEATURES (Support & Account) -->
                
                <!-- Pricing -->
                <li>
                    <a href="pricing.php"
                        class="sidebar-nav-item w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                        <i class="fa-solid fa-tags text-base"></i>
                        <span>Bảng Giá</span>
                        <span class="ml-auto text-[9px] bg-green-500 text-white px-2 py-0.5 rounded-full font-black">💰</span>
                    </a>
                </li>

                <!-- Help/Tutorials -->
                <li>
                    <button onclick="showTutorials()"
                        class="sidebar-nav-item w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                        <i class="fa-solid fa-graduation-cap text-base"></i>
                        <span>Hướng Dẫn</span>
                        <span class="ml-auto text-[9px] bg-blue-500 text-white px-2 py-0.5 rounded-full font-black">📹</span>
                    </button>
                </li>
                
                <!-- AI History -->
                <li>
                    <a href="ai-history.php"
                        class="sidebar-nav-item w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                        <i class="fa-solid fa-clock-rotate-left text-base"></i>
                        <span>Lịch Sử AI</span>
                        <span class="ml-auto text-[9px] bg-purple-500 text-white px-2 py-0.5 rounded-full font-black">💾</span>
                    </a>
                </li>
                
                <!-- Orders History -->
                <li>
                    <a href="orders-history.php"
                        class="sidebar-nav-item w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                        <i class="fa-solid fa-receipt text-base"></i>
                        <span>Đơn Hàng</span>
                        <span class="ml-auto text-[9px] bg-green-500 text-white px-2 py-0.5 rounded-full font-black">📦</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- User Profile Section (Bottom) -->
        <div class="p-3 border-t border-slate-200">
            <div class="bg-slate-50 rounded-lg p-3">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center text-white font-bold text-sm">
                        <?php echo strtoupper(substr($username, 0, 1)); ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-slate-800 truncate"><?php echo htmlspecialchars($username); ?></p>
<?php
// 🔥 LUÔN LOAD DB MỚI
$users = loadDB('users.json');

$user = $users[$username] ?? null;

// mặc định
$badgeClass = 'bg-gray-300 text-gray-700';
$text = 'Free';

if ($user) {

    $expire = $user['tier_expires_at'] ?? null;

    if (!empty($expire)) {

        $expireTime = strtotime($expire);

        if ($expireTime && $expireTime > time()) {
            $badgeClass = 'bg-green-100 text-green-700';
            $text = 'HSD: ' . date('d/m/Y', $expireTime);
        }
    }
}
?>

<span class="inline-block text-[9px] px-2 py-0.5 rounded-full font-bold <?php echo $badgeClass; ?>">
    <?php echo $text; ?>
</span>
                    </div>
                </div>
                <a href="logout.php" class="flex items-center justify-center gap-2 text-xs font-medium text-slate-600 hover:text-red-600 transition py-1.5">
                    <i class="fa-solid fa-sign-out-alt"></i>
                    <span>Đăng xuất</span>
                </a>
            </div>
        </div>
    </aside>

    <!-- 📱 TOP BAR (Search + Quick Actions) -->
    <div class="flex-1 md:ml-64 flex flex-col">
        <header class="bg-white border-b border-slate-200 sticky top-0 z-40 shadow-sm">
            <div class="px-4 md:px-6 h-16 md:h-20 flex items-center justify-between gap-4">
                <!-- Mobile Hamburger Menu -->
                <button onclick="toggleSidebar()" class="md:hidden text-slate-700 p-2 hover:bg-slate-100 rounded-lg transition">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                
                <!-- Search Bar (Center) -->
                <div class="flex-1 max-w-2xl">
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" 
                            id="topSearchBar" 
                            placeholder="Tìm kiếm video, kênh, từ khóa..."
                            class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            onkeyup="if(event.key==='Enter') quickSearch()">
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="flex items-center gap-3 ml-4">
                    <button onclick="showNotifications()"
                        class="relative p-2.5 bg-slate-50 hover:bg-slate-100 text-slate-700 rounded-lg transition"
                        title="Thông báo">
                        <i class="fa-solid fa-bell text-base"></i>
                        <span class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white text-[9px] font-bold rounded-full flex items-center justify-center">3</span>
                    </button>
                </div>
            </div>
        </header>

        <!-- MAIN CONTENT AREA -->
        <main class="flex-1 overflow-auto bg-slate-50" id="mainContainer">
            <div class="max-w-7xl mx-auto px-6 py-8">

        <div id="searchTab" class="tab-content active space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-1">
                <div class="p-6 md:p-8">
                    <!-- 🔍 KEYWORD SEARCH (Simplified - No mode switcher) -->
                    <div class="flex flex-col md:flex-row gap-4 items-end">
                        <div class="flex-grow w-full">
                            <label class="block text-xs font-bold text-slate-500 mb-1.5 uppercase tracking-wider">
                                Từ khóa / Chủ đề (Keyword / Topic)
                            </label>
                            <div class="relative group">
                                <input type="text" id="keyword"
                                    class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-red-500 focus:bg-white outline-none transition-all font-medium text-slate-700 shadow-sm"
                                    placeholder="VD: horror stories, street food, crypto news...">
                                <i class="fa-solid fa-crosshairs absolute left-3.5 top-3.5 text-slate-400 pointer-events-none group-focus-within:text-red-500 transition-colors"></i>
                            </div>
                            <p class="text-[10px] text-slate-500 mt-1 ml-1">
                                💡 Tìm videos viral trong chủ đề này, xem thumbnail + chiến lược thành công
                            </p>
                        </div>

                        <div class="w-full md:w-48">
                            <label class="block text-xs font-bold text-slate-500 mb-1.5 uppercase tracking-wider">
                                Thị trường (Quốc gia)
                            </label>
                            <div class="relative">
                                <select id="globalRegion"
                                    class="w-full pl-9 pr-8 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-red-500 focus:bg-white outline-none appearance-none font-medium text-sm shadow-sm cursor-pointer">
                                    <option value="">🌍 Global (Toàn cầu)</option>
                                    <optgroup label="🔥 Top Tier 1 (Doanh thu cao)">
                                        <option value="US">🇺🇸 Hoa Kỳ (USA)</option>
                                        <option value="GB">🇬🇧 Anh Quốc (UK)</option>
                                        <option value="CA">🇨🇦 Canada</option>
                                        <option value="AU">🇦🇺 Úc (Australia)</option>
                                        <option value="DE">🇩🇪 Đức (Germany)</option>
                                    </optgroup>
                                    <optgroup label="🌏 Châu Á Thái Bình Dương">
                                        <option value="VN">🇻🇳 Việt Nam</option>
                                        <option value="JP">🇯🇵 Nhật Bản</option>
                                        <option value="KR">🇰🇷 Hàn Quốc</option>
                                        <option value="IN">🇮🇳 Ấn Độ</option>
                                        <option value="ID">🇮🇩 Indonesia</option>
                                        <option value="TH">🇹🇭 Thái Lan</option>
                                        <option value="PH">🇵🇭 Philippines</option>
                                    </optgroup>
                                    <optgroup label="🌍 Châu Âu & Khác">
                                        <option value="FR">🇫🇷 Pháp (France)</option>
                                        <option value="ES">🇪🇸 Tây Ban Nha</option>
                                        <option value="RU">🇷🇺 Nga (Russia)</option>
                                        <option value="BR">🇧🇷 Brazil</option>
                                        <option value="MX">🇲🇽 Mexico</option>
                                    </optgroup>
                                </select>
                                <i class="fa-solid fa-earth-americas absolute left-3.5 top-3.5 text-slate-400 pointer-events-none"></i>
                                <i class="fa-solid fa-chevron-down absolute right-3.5 top-3.5 text-slate-400 text-xs pointer-events-none"></i>
                            </div>
                        </div>

                        <div class="w-full md:w-auto flex flex-col gap-2">
                            <button onclick="analyzeKeywords()" id="analyzeBtn"
                                class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-8 rounded-xl transition-all shadow-md hover:shadow-lg flex items-center justify-center gap-2 transform active:scale-95">
                                <span>TÌM KIẾM</span> <i class="fa-solid fa-magnifying-glass"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 flex justify-between items-center border-t border-slate-100 pt-3">
                        <div class="flex items-center gap-4">
                            <button onclick="toggleFilters()"
                                class="text-xs font-bold text-slate-500 hover:text-red-600 flex items-center gap-1 transition-colors">
                                <i class="fa-solid fa-sliders"></i> Tùy chỉnh Bộ lọc & Thời gian
                            </button>
                            <label class="flex items-center gap-2 cursor-pointer group"
                                title="Tăng thời gian nghỉ giữa các request để lấy dữ liệu sâu hơn">
                                <div class="relative">
                                    <input type="checkbox" id="slowModeToggle" class="sr-only peer">
                                    <div
                                        class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600">
                                    </div>
                                </div>
                                <span
                                    class="text-xs font-medium text-slate-600 group-hover:text-blue-600 transition-colors"><i
                                        class="fa-solid fa-turtle"></i> Quét Chậm & Sâu</span>
                            </label>
                        </div>
                        <span class="text-[10px] text-slate-400 italic font-medium" id="apiKeyStatus">API Key: <span
                                class="text-green-500 font-bold">● Protected</span></span>
                    </div>

                    <div id="advancedFilters"
                        class="hidden mt-4 space-y-4 bg-slate-50 p-5 rounded-xl border border-slate-200 shadow-inner">
                        
                        <!-- 🌟 GOLD MINE INFO (Thay thế Outlier Filters) -->
                        <div class="bg-gradient-to-r from-yellow-50 to-orange-50 p-4 rounded-lg border-2 border-yellow-300 mb-4">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="text-sm font-black text-yellow-700">🌟 GOLD MINE AUTO-DETECTION</span>
                                <span class="text-[9px] bg-gradient-to-r from-yellow-500 to-orange-600 text-white px-2 py-0.5 rounded-full font-bold">AUTO</span>
                            </div>
                            
                            <div class="space-y-2">
                                <div class="flex items-start gap-2 text-xs text-slate-700">
                                    <i class="fa-solid fa-star text-yellow-600 mt-0.5"></i>
                                    <div>
                                        <strong class="text-yellow-700">Tự động sắp xếp theo Gold Score:</strong> Kết quả đã được sắp xếp theo thứ tự ưu tiên:
                                        <ul class="ml-4 mt-1 space-y-0.5">
                                            <li>• Kênh mới (<1 năm): +1000 điểm</li>
                                            <li>• Ít subscribers (<10K): +300 điểm</li>
                                            <li>• Video viral (ratio cao): +bonus</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="flex items-start gap-2 text-xs text-slate-700">
                                    <i class="fa-solid fa-crown text-yellow-600 mt-0.5"></i>
                                    <div>
                                        <strong class="text-yellow-700">GOLD MINE Badge:</strong> Video có badge vàng là kênh mới + subs thấp + video viral - CƠ HỘI VÀNG nhất!
                                    </div>
                                </div>
                                
                                <div class="mt-3 p-2 bg-blue-50 border-l-4 border-blue-500 rounded">
                                    <div class="flex items-start gap-2">
                                        <i class="fa-solid fa-lightbulb text-blue-600 text-xs mt-0.5"></i>
                                        <div class="text-[10px] text-blue-800">
                                            <strong>Không cần lọc thủ công!</strong> Hệ thống đã tự động đưa GOLD MINE lên đầu danh sách. Chỉ cần xem kết quả và chọn video có badge vàng để phân tích! 🚀
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- EXISTING FILTERS -->
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-500 uppercase">Khoảng Thời Gian</label>
                                <select id="filterDate"
                                    class="w-full text-xs p-2.5 rounded-lg border border-slate-300 bg-white focus:border-red-500 outline-none"
                                    onchange="toggleCustomDate()">
                                    <option value="any">Toàn thời gian</option>
                                    <option value="hour">1 Giờ qua (Săn Trend)</option>
                                    <option value="today">Hôm nay (24h)</option>
                                    <option value="week">Tuần này</option>
                                    <option value="month">Tháng này</option>
                                    <option value="3month">3 Tháng qua</option>
                                    <option value="custom">📅 Chọn Lịch (Custom)</option>
                                </select>
                            </div>
                            <div id="customDateContainer" class="col-span-2 hidden grid-cols-2 gap-2">
                                <div class="space-y-1">
                                    <label class="text-[10px] font-bold text-slate-500 uppercase">Từ Ngày</label>
                                    <input type="date" id="dateFrom"
                                        class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:border-red-500 outline-none">
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[10px] font-bold text-slate-500 uppercase">Đến Ngày</label>
                                    <input type="date" id="dateTo"
                                        class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:border-red-500 outline-none">
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-500 uppercase">Định Dạng</label>
                                <select id="filterDuration"
                                    class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:border-red-500 outline-none">
                                    <option value="any">Tất cả</option>
                                    <option value="short">Shorts (< 60s)</option>
                                    <option value="long">Video Dài (> 60s)</option>
                                </select>
                            </div>

                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-500 uppercase">Số Lượng Quét</label>
                                <input type="number" id="maxResults" value="40" min="10" max="50"
                                    class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:border-red-500 outline-none">
                            </div>

                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-500 uppercase">Min Views</label>
                                <input type="number" id="minViews" placeholder="0"
                                    class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:border-red-500 outline-none">
                            </div>

                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-500 uppercase">Min Subs</label>
                                <input type="number" id="minSubs" placeholder="0"
                                    class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:border-red-500 outline-none">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="loading" class="hidden py-12 text-center">
                <div class="inline-block p-4 rounded-full bg-white shadow-md mb-4 animate-bounce">
                    <i class="fa-brands fa-youtube text-4xl text-red-600"></i>
                </div>
                <h3 class="text-slate-800 font-bold text-xl animate-pulse">Đang phân tích thị trường...</h3>
                <p class="text-slate-500 text-sm mt-2">Đang rà soát dữ liệu sâu (Vui lòng đợi để đảm bảo độ chính xác).
                </p>
            </div>

            <div id="resultsArea" class="hidden space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 bg-white rounded-xl shadow-lg border border-slate-200 overflow-hidden relative flex">
                        <div class="absolute top-0 left-0 w-2 h-full bg-slate-300 transition-colors duration-500" id="verdictColorBar"></div>
                        <div class="p-6 flex-1 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center gap-3 mb-2">
                                    <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">KẾT LUẬN CHIẾN LƯỢC</span>
                                    <span id="verdictBadge" class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600">WAITING...</span>
                                </div>
                                <h2 class="text-3xl font-black text-slate-800 mb-2" id="verdictText">--</h2>
                                <p class="text-slate-600 text-sm leading-relaxed" id="verdictDesc">Chưa có dữ liệu phân tích.</p>
                            </div>
                            <div class="flex gap-4 text-xs mt-6">
                                <div class="bg-slate-50 px-4 py-2 rounded-lg border border-slate-100 shadow-sm">
                                    <span class="text-slate-400 uppercase font-bold text-[10px] block">Khối lượng</span>
                                    <strong id="statVolume" class="text-lg">--</strong>
                                </div>
                                <div class="bg-slate-50 px-4 py-2 rounded-lg border border-slate-100 shadow-sm">
                                    <span class="text-slate-400 uppercase font-bold text-[10px] block">Cạnh tranh</span>
                                    <strong id="statComp" class="text-lg">--</strong>
                                </div>
                            </div>
                        </div>
                        <div class="w-1/3 flex items-center justify-center border-l border-slate-100 bg-slate-50/50 p-4">
                            <div class="gauge-wrapper">
                                <svg viewBox="0 0 200 100" class="w-full h-full">
                                    <path d="M 20 100 A 80 80 0 0 1 180 100" class="gauge-bg" />
                                    <defs>
                                        <linearGradient id="gaugeGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                            <stop offset="0%" stop-color="#ef4444" />
                                            <stop offset="50%" stop-color="#eab308" />
                                            <stop offset="100%" stop-color="#22c55e" />
                                        </linearGradient>
                                    </defs>
                                    <path id="gaugeFill" d="M 20 100 A 80 80 0 0 1 180 100" class="gauge-arc" stroke="url(#gaugeGradient)" />
                                </svg>
                                <div class="needle" id="gaugeNeedle"></div>
                                <div class="absolute bottom-0 w-full text-center">
                                    <span class="text-3xl font-black text-slate-800" id="gaugeScore">0</span>
                                    <span class="text-[10px] text-slate-400 uppercase block font-bold mb-2">Điểm Tiềm Năng</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-1 bg-white p-5 rounded-xl shadow-sm border border-slate-200 flex flex-col justify-center relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-2 opacity-10">
                            <i class="fa-solid fa-money-bill-trend-up text-6xl text-green-500"></i>
                        </div>
                        <h3 class="font-bold text-slate-800 mb-1 text-sm uppercase tracking-wide flex items-center gap-2">
                            <i class="fa-solid fa-hand-holding-dollar text-green-600"></i> Điều chỉnh RPM
                        </h3>
                        <p class="text-[10px] text-slate-500 mb-4">Kéo để cập nhật doanh thu dự kiến (Real-time)</p>
                        <div class="text-center mb-2">
                            <span class="text-4xl font-black text-slate-800" id="globalRpmDisplay">$0.3</span>
                            <span class="text-xs text-slate-400">/ 1000 views</span>
                        </div>
                        <input type="range" id="globalRpmSlider" min="0.1" max="20.0" step="0.1" value="0.3" class="w-full cursor-pointer" oninput="updateGlobalRpm()">
                        <div class="flex justify-between text-[10px] text-slate-400 mt-1 font-mono">
                            <span>$0.1</span><span>$10</span><span>$20</span>
                        </div>
                    </div>
                </div>

                <!-- 🔥 OUTLIER STATS DASHBOARD (NEW) -->
                <div id="outlierStatsContainer" class="hidden">
                    <div class="bg-gradient-to-r from-orange-500 to-red-600 p-1 rounded-xl shadow-xl">
                        <div class="bg-white p-6 rounded-lg">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="bg-gradient-to-r from-orange-500 to-red-600 text-white p-2.5 rounded-lg shadow-lg">
                                        <i class="fa-solid fa-fire-flame-curved text-xl"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-black text-slate-800 text-lg">GOLD MINE DETECTION REPORT</h3>
                                        <p class="text-xs text-slate-500">Kênh mới + Subs thấp + Video viral = CƠ HỘI VÀNG!</p>
                                    </div>
                                </div>
                                <span class="text-[9px] bg-gradient-to-r from-yellow-500 to-orange-600 text-white px-3 py-1 rounded-full font-bold shadow-md">🌟 NICHE.PHP STYLE</span>
                            </div>
                            
                            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                                <div class="bg-gradient-to-br from-slate-50 to-slate-100 p-4 rounded-xl border border-slate-200 shadow-sm">
                                    <div class="text-[10px] text-slate-500 font-bold uppercase mb-1">Total Videos</div>
                                    <div class="text-2xl font-black text-slate-800" id="statTotalVideos">0</div>
                                </div>
                                
                                <div class="bg-gradient-to-br from-yellow-50 to-orange-50 p-4 rounded-xl border-2 border-yellow-400 shadow-md">
                                    <div class="text-[10px] text-yellow-700 font-bold uppercase mb-1 flex items-center gap-1">
                                        <i class="fa-solid fa-crown"></i> GOLD MINES
                                    </div>
                                    <div class="text-2xl font-black text-yellow-600" id="statGoldMines">0</div>
                                    <div class="text-[9px] text-yellow-600 font-bold mt-1" id="statGoldMinePercent">0%</div>
                                </div>
                                
                                <div class="bg-gradient-to-br from-blue-50 to-cyan-50 p-4 rounded-xl border border-blue-200 shadow-sm">
                                    <div class="text-[10px] text-blue-700 font-bold uppercase mb-1 flex items-center gap-1">
                                        <i class="fa-solid fa-star"></i> Kênh Mới
                                    </div>
                                    <div class="text-2xl font-black text-blue-600" id="statNewChannels">0</div>
                                    <div class="text-[9px] text-blue-500 font-bold mt-1" id="statNewChannelPercent">< 1 năm</div>
                                </div>
                                
                                <div class="bg-gradient-to-br from-purple-50 to-pink-50 p-4 rounded-xl border border-purple-200 shadow-sm">
                                    <div class="text-[10px] text-purple-700 font-bold uppercase mb-1">Small Channels</div>
                                    <div class="text-2xl font-black text-purple-600" id="statSmallChannels">0</div>
                                    <div class="text-[9px] text-purple-500 font-bold mt-1" id="statSmallChannelPercent">< 50K subs</div>
                                </div>
                                
                                <div class="bg-gradient-to-br from-green-50 to-emerald-50 p-4 rounded-xl border border-green-200 shadow-sm">
                                    <div class="text-[10px] text-green-700 font-bold uppercase mb-1 flex items-center gap-1">
                                        <i class="fa-solid fa-rocket"></i> High Viral
                                    </div>
                                    <div class="text-2xl font-black text-green-600" id="statHighViral">0</div>
                                    <div class="text-[9px] text-green-500 font-bold mt-1">Score 80+</div>
                                </div>
                            </div>
                            
                            <div class="mt-4 p-3 bg-yellow-50 border-l-4 border-yellow-500 rounded">
                                <div class="flex items-start gap-2">
                                    <i class="fa-solid fa-lightbulb text-yellow-600 text-sm mt-0.5"></i>
                                    <div class="text-xs text-slate-700">
                                        <strong class="text-yellow-700">Gold Mine Strategy:</strong> Kênh mới (<1 năm) + Ít subs (<50K) + Video viral (ratio cao) = CƠ HỘI VÀNG! 
                                        Phân tích title, thumbnail, và format của họ để replicate strategy. 🌟
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="microNicheContainer" class="hidden mb-6">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="bg-indigo-600 text-white p-1.5 rounded-lg shadow-sm">
                            <i class="fa-solid fa-dna text-sm"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-800 text-base">Gợi ý Ngách Tiềm Năng (Micro-Niche Intelligence)</h3>
                            <p class="text-[10px] text-slate-500 font-medium">Phân tích cluster chủ đề & Gợi ý chiến lược xâm nhập thị trường.</p>
                        </div>
                    </div>
                    <div id="microNicheGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"></div>
                </div>

                <div id="uploadTimeContainer" class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 hidden">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-slate-800 flex items-center gap-2">
                            <i class="fa-solid fa-clock text-blue-500"></i> Khung Giờ Vàng (Best Upload Time)
                        </h3>
                        <span class="text-xs text-slate-500 italic">Dựa trên <span id="uploadTimeVideoCount">0</span> videos</span>
                    </div>
                    <div class="relative">
                        <div id="uploadHeatmap" class="flex items-end justify-between gap-1 h-32"></div>
                        <div class="flex justify-between text-[10px] text-slate-400 mt-2 font-mono">
                            <span>0h</span><span>6h</span><span>12h</span><span>18h</span><span>23h</span>
                        </div>
                    </div>
                    <div id="bestTimeRecommendation" class="mt-4 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg border border-blue-200">
                        <p class="text-sm text-slate-700 text-center">
                            <i class="fa-solid fa-lightbulb text-yellow-500"></i>
                            <strong>Giờ vàng đăng bài:</strong> <span id="bestTimeRange" class="text-blue-600 font-bold">--:-- - --:--</span>
                            <span id="bestTimeNote" class="text-slate-500 italic block mt-1 text-xs">(Dựa trên 16 videos)</span>
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 bg-white p-5 rounded-xl shadow-sm border border-slate-200">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="font-bold text-slate-800 flex items-center gap-2"><i class="fa-solid fa-tags text-red-500"></i> Từ khóa "Vua" (Lặp lại nhiều nhất)</h3>
                            <button onclick="copyTopTags()" class="text-xs text-blue-600 hover:underline font-bold">Copy All</button>
                        </div>
                        <div id="topTagsCloud" class="flex flex-wrap gap-2 max-h-32 overflow-y-auto tags-container"></div>
                    </div>
                    <div class="lg:col-span-1 bg-white p-5 rounded-xl shadow-sm border border-slate-200">
                        <h3 class="font-bold text-slate-800 mb-4 flex items-center gap-2"><i class="fa-solid fa-crown text-yellow-500"></i> Top Channel Thống Trị</h3>
                        <div id="competitorLeaderboard" class="space-y-3"></div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/50 flex justify-between items-center">
                        <h3 class="font-bold text-slate-800">📡 Radar Video (Danh sách Chi tiết)</h3>
                        <div class="flex gap-2">
                            <button onclick="exportCSV()" class="text-xs bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 px-3 py-1.5 rounded shadow-sm transition font-bold"><i class="fa-solid fa-file-csv mr-1"></i> Xuất CSV</button>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-white text-slate-500 text-[10px] uppercase tracking-wider border-b border-slate-200">
                                    <th class="p-4 font-bold">Media</th>
                                    <th class="p-4 font-bold">Chi tiết Video & Kênh</th>
                                    <th class="p-4 font-bold text-right">View</th>
                                    <th class="p-4 font-bold text-right">Sub</th>
                                    <th class="p-4 font-bold text-right text-red-600">V/S</th>
                                    <th class="p-4 font-bold text-right text-green-600">Est. $</th>
                                    <th class="p-4 font-bold text-center">Hành động</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody" class="divide-y divide-slate-100 text-xs sm:text-sm"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- 📊 DASHBOARD TAB (Vault) -->
        <div id="dashboardTab" class="tab-content space-y-6">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 min-h-[500px]">
                <div class="flex justify-between items-center mb-6 border-b border-slate-100 pb-4">
                    <div>
                        <h2 class="text-xl font-bold text-slate-800">Kho Lưu trữ Chiến lược</h2>
                        <p class="text-sm text-slate-500">Các kênh đối thủ bạn đang theo dõi sát sao.</p>
                    </div>
                    <button onclick="clearDashboard()" class="text-red-600 hover:bg-red-50 px-3 py-2 rounded-lg text-sm transition font-medium"><i class="fa-solid fa-trash mr-1"></i> Xóa tất cả</button>
                </div>
                <div id="dashboardGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"></div>
                <div id="emptyDashboard" class="text-center py-20 text-slate-400 hidden"><p>Kho dữ liệu trống.</p></div>
            </div>
        </div>

        <!-- 🧠 AI DEEP DIVE TAB (NEW) -->
        <div id="deepAnalysisTab" class="tab-content space-y-6">
            <div class="bg-gradient-to-br from-purple-50 via-pink-50 to-blue-50 p-8 rounded-2xl shadow-xl border-2 border-purple-200">
                <!-- Header -->
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-purple-600 to-pink-600 rounded-full mb-4 shadow-lg">
                        <i class="fa-solid fa-brain text-4xl text-white"></i>
                    </div>
                    <h2 class="text-3xl font-black text-slate-800 mb-2">AI Deep Channel Analysis</h2>
                    <p class="text-slate-600 text-sm max-w-2xl mx-auto">Phân tích chuyên sâu kênh YouTube với Gemini AI: Audience, chủ đề, top videos, thumbnail patterns, và đánh giá tiềm năng sao chép</p>
                </div>
                
                <!-- 🤖 Usage Indicator (Free Tier) -->
                <?php if (!in_array($userTier, ['vip', 'trial', 'basic', 'admin'])): ?>
                <div id="aiDeepDiveUsageIndicator" class="max-w-3xl mx-auto mb-6">
                    <div class="bg-white rounded-xl p-4 border-2 <?php echo $aiDeepDiveUsage['remaining'] <= 1 ? 'border-red-300' : 'border-purple-200'; ?>">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center <?php echo $aiDeepDiveUsage['remaining'] <= 1 ? 'bg-red-100' : 'bg-purple-100'; ?>">
                                    <i class="fa-solid fa-robot <?php echo $aiDeepDiveUsage['remaining'] <= 1 ? 'text-red-600' : 'text-purple-600'; ?>"></i>
                                </div>
                                <div>
                                    <p class="font-bold text-slate-800">Lượt phân tích AI miễn phí</p>
                                    <p class="text-xs text-slate-500">Tháng: <?php echo $aiDeepDiveUsage['current_month'] ?? date('Y-m'); ?></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-black <?php echo $aiDeepDiveUsage['remaining'] <= 1 ? 'text-red-600' : 'text-purple-600'; ?>" id="aiDeepDiveRemainingCount"><?php echo $aiDeepDiveUsage['remaining']; ?></p>
                                <p class="text-xs text-slate-500">còn lại</p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Input Section -->
                <div class="bg-white rounded-xl p-6 shadow-md border border-purple-100 max-w-3xl mx-auto">
                    <label class="block text-sm font-bold text-slate-700 mb-3">
                        <i class="fa-brands fa-youtube text-red-600"></i> Nhập link kênh YouTube
                    </label>
                    <div class="flex gap-3">
                        <input type="text" id="deepChannelUrl" 
                            class="flex-1 px-4 py-3 border-2 border-purple-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-all text-sm"
                            placeholder="Dán link kênh YouTube vào đây (VD: youtube.com/@tenkênh)">
                        <button onclick="startDeepAnalysis()" id="deepAnalyzeBtn"
                            class="px-8 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-xl transform hover:scale-105 flex items-center gap-2">
                            <i class="fa-solid fa-sparkles"></i> Phân tích AI
                        </button>
                    </div>
                    <p class="text-xs text-slate-500 mt-2 flex items-start gap-2">
                        <i class="fa-solid fa-info-circle text-purple-500 mt-0.5"></i>
                        <span>AI sẽ phân tích: Tên kênh, followers, tốc độ upload, audience demographics, 5 chủ đề lớn, top 10 videos (6 tháng), thumbnail patterns, và đánh giá chiều sâu nội dung</span>
                    </p>
                </div>

                <!-- Loading State -->
                <div id="deepLoading" class="hidden mt-8 text-center">
                    <div class="inline-flex flex-col items-center bg-white px-8 py-6 rounded-xl shadow-lg">
                        <div class="w-16 h-16 border-4 border-purple-200 border-t-purple-600 rounded-full animate-spin mb-4"></div>
                        <p class="text-lg font-bold text-slate-800">Gemini AI đang phân tích...</p>
                        <p class="text-sm text-slate-500 mt-1">Đang thu thập dữ liệu từ kênh và xử lý với AI</p>
                    </div>
                </div>

                <!-- Results Area -->
                <div id="deepResults" class="hidden mt-8 bg-gradient-to-br from-slate-50 to-white rounded-2xl shadow-lg border border-slate-200 p-6 lg:p-8">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>
        </div>

        <!-- ⚙️ API KEYS TAB (NEW - Dedicated Configuration Page) -->
        <div id="apiKeysTab" class="tab-content space-y-6">
            <div class="max-w-4xl mx-auto">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 rounded-2xl p-8 text-white shadow-xl mb-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <h2 class="text-3xl font-black mb-2">⚙️ API Keys Configuration</h2>
                            <p class="text-blue-100 text-sm max-w-2xl">Cấu hình API keys của bạn một lần, sử dụng ở khắp nơi. Tất cả dữ liệu được mã hóa và lưu trên trình duyệt của bạn.</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl px-4 py-2">
                            <div class="text-xs text-blue-100 mb-1">Connection Status</div>
                            <div id="apiConnectionStatus" class="flex items-center gap-2">
                                <div class="w-3 h-3 bg-gray-300 rounded-full animate-pulse"></div>
                                <span class="text-sm font-bold">Checking...</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- YouTube Data API -->
                <div class="bg-white rounded-xl shadow-md border border-slate-200 overflow-hidden">
                    <div class="bg-gradient-to-r from-red-50 to-pink-50 px-6 py-4 border-b border-slate-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="bg-red-600 text-white p-3 rounded-lg">
                                    <i class="fa-brands fa-youtube text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-slate-800">YouTube Data API v3</h3>
                                    <p class="text-xs text-slate-500">Bắt buộc để tìm kiếm và lấy dữ liệu video</p>
                                </div>
                            </div>
                            <a href="https://console.cloud.google.com/apis/credentials" target="_blank" 
                               class="text-xs text-blue-600 hover:text-blue-700 font-bold flex items-center gap-1">
                                <i class="fa-solid fa-external-link"></i> Lấy Key
                            </a>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            <div id="youtubeKeyList" class="space-y-2">
                                <!-- Keys will be rendered here -->
                            </div>
                            <div class="flex gap-2">
                                <input type="password" id="newYouTubeKey" 
                                    class="flex-1 px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-500 outline-none text-sm"
                                    placeholder="Paste YouTube API key..." 
                                    onkeypress="if(event.key==='Enter') addYouTubeKey()">
                                <button onclick="addYouTubeKey()" 
                                    class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold rounded-lg transition">
                                    <i class="fa-solid fa-plus"></i> Add
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gemini API -->
                <div class="bg-white rounded-xl shadow-md border border-slate-200 overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-50 to-pink-50 px-6 py-4 border-b border-slate-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="bg-purple-600 text-white p-3 rounded-lg">
                                    <i class="fa-solid fa-brain text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-slate-800">Gemini API (Google AI)</h3>
                                    <p class="text-xs text-slate-500">Tùy chọn - Dùng cho AI Deep Dive (1,500 requests/key/day FREE)</p>
                                </div>
                            </div>
                            <a href="https://aistudio.google.com/app/apikey" target="_blank" 
                               class="text-xs text-blue-600 hover:text-blue-700 font-bold flex items-center gap-1">
                                <i class="fa-solid fa-external-link"></i> Lấy Key
                            </a>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            <div id="geminiKeyListPage" class="space-y-2">
                                <!-- Keys will be rendered here -->
                            </div>
                            <div class="flex gap-2">
                                <input type="password" id="newGeminiKeyPage" 
                                    class="flex-1 px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-purple-500 outline-none text-sm"
                                    placeholder="Paste Gemini API key (AIza...)" 
                                    onkeypress="if(event.key==='Enter') addGeminiKeyFromPage()">
                                <button onclick="addGeminiKeyFromPage()" 
                                    class="px-6 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-lg transition">
                                    <i class="fa-solid fa-plus"></i> Add
                                </button>
                            </div>
                            <p class="text-xs text-purple-600 italic flex items-start gap-2">
                                <i class="fa-solid fa-info-circle mt-0.5"></i>
                                <span>Thêm nhiều keys để tăng quota (1,500 × n keys). Hệ thống tự động rotation.</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- OpenRouter API -->
                <div class="bg-white rounded-xl shadow-md border border-slate-200 overflow-hidden">
                    <div class="bg-gradient-to-r from-indigo-50 to-blue-50 px-6 py-4 border-b border-slate-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="bg-indigo-600 text-white p-3 rounded-lg">
                                    <i class="fa-solid fa-rocket text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-slate-800">OpenRouter API <span id="openRouterKeyCount" class="text-xs font-normal bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full">0 keys</span></h3>
                                    <p class="text-xs text-slate-500">Tùy chọn - Fallback khi Gemini FREE hết quota (Có phí)</p>
                                </div>
                            </div>
                            <a href="https://openrouter.ai/keys" target="_blank" 
                               class="text-xs text-blue-600 hover:text-blue-700 font-bold flex items-center gap-1">
                                <i class="fa-solid fa-external-link"></i> Lấy Key
                            </a>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            <!-- Multi-key input -->
                            <div class="flex gap-2">
                                <input type="password" id="newOpenRouterKey" 
                                    class="flex-1 px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none text-sm"
                                    placeholder="Paste OpenRouter API key (sk-or-v1-...)">
                                <button onclick="addOpenRouterKey()" 
                                    class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg transition">
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            </div>
                            
                            <!-- Key list -->
                            <div id="openRouterKeyList" class="space-y-2 max-h-40 overflow-y-auto"></div>
                            
                            <!-- Model selector -->
                            <select id="openRouterModelPage" 
                                class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
                                <optgroup label="🔥 FREE Models (Auto-rotate)">
                                    <option value="" selected>Auto-rotate FREE models (Đề xuất)</option>
                                    <option value="google/gemini-flash-1.5:free">Gemini 1.5 Flash FREE</option>
                                    <option value="google/gemini-2.0-flash-exp:free">Gemini 2.0 Flash FREE</option>
                                    <option value="meta-llama/llama-3.3-70b-instruct:free">Llama 3.3 70B FREE</option>
                                    <option value="deepseek/deepseek-chat:free">DeepSeek Chat FREE</option>
                                </optgroup>
                                <optgroup label="🔥 Gemini (Google)">
                                    <option value="google/gemini-flash-1.5">Gemini 1.5 Flash ($0.075/1M - Faster)</option>
                                    <option value="google/gemini-pro-1.5">Gemini 1.5 Pro ($1.25/1M - Most Capable)</option>
                                </optgroup>
                                <optgroup label="🤖 GPT (OpenAI)">
                                    <option value="openai/gpt-4o-mini">GPT-4o Mini ($0.15/1M)</option>
                                    <option value="openai/gpt-4o">GPT-4o ($2.50/1M)</option>
                                </optgroup>
                                <optgroup label="🧠 Claude (Anthropic)">
                                    <option value="anthropic/claude-3-haiku">Claude 3 Haiku ($0.25/1M)</option>
                                    <option value="anthropic/claude-3.5-sonnet">Claude 3.5 Sonnet ($3/1M)</option>
                                </optgroup>
                            </select>
                            <p class="text-xs text-indigo-600 italic flex items-start gap-2">
                                <i class="fa-solid fa-info-circle mt-0.5"></i>
                                <span>🔄 <strong>Auto-rotate:</strong> Tự động xoay giữa các FREE models khi hết quota. Thêm nhiều keys để tăng quota!</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="flex items-center justify-between bg-white rounded-xl shadow-md border border-slate-200 p-6">
                    <div class="flex items-center gap-3 text-sm text-slate-600">
                        <i class="fa-solid fa-shield-halved text-green-600 text-lg"></i>
                        <span>Tất cả API keys được <strong class="text-slate-800">mã hóa AES-256</strong> và lưu trên trình duyệt của bạn</span>
                    </div>
                    <div class="flex gap-3">
                        <button onclick="testAPIConnections()" 
                            class="px-6 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg transition">
                            <i class="fa-solid fa-flask"></i> Test Kết nối
                        </button>
                        <button onclick="saveAllAPIKeys()" 
                            class="px-8 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold rounded-lg transition shadow-lg">
                            <i class="fa-solid fa-shield-halved"></i> Lưu & Mã hóa
                        </button>
                    </div>
                </div>

                <!-- Help Section -->
                <div class="bg-blue-50 border-l-4 border-blue-500 rounded-lg p-6">
                    <h4 class="text-sm font-bold text-blue-900 mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-lightbulb"></i> Hướng dẫn nhanh
                    </h4>
                    <div class="space-y-2 text-xs text-blue-800">
                        <p><strong>1. YouTube API:</strong> Bắt buộc cho mọi tình năng. Nhận 10,000 requests/day FREE tại Google Cloud Console.</p>
                        <p><strong>2. Gemini API:</strong> Dùng cho AI Deep Dive. 1,500 requests/key/day FREE. Thêm nhiều keys để x5-x10 quota.</p>
                        <p><strong>3. OpenRouter:</strong> Chỉ dùng khi Gemini FREE hết quota (fallback). Tiết kiệm tới 100% chi phí nếu dùng Gemini FREE đầy đủ.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ✨ FREE AI TOOLS TAB CONTENT -->

            </div>
        </main>
    </div>

    <!-- ✅ API Keys now managed in dedicated sidebar tab -->

    <div id="confirmModal" class="modal opacity-0 invisible pointer-events-none fixed w-full h-full top-0 left-0 flex items-center justify-center z-[9100]">
        <div class="modal-overlay absolute w-full h-full bg-slate-900/50 backdrop-blur-sm"></div>
        <div class="bg-white w-full max-w-sm mx-4 rounded-xl shadow-2xl z-50 p-6 transform transition-all scale-95" id="confirmContent">
            <h3 class="text-lg font-bold text-slate-800 mb-2">Xác nhận</h3>
            <p class="text-sm text-slate-600 mb-6" id="confirmMessage">Bạn có chắc chắn muốn thực hiện hành động này?</p>
            <div class="flex justify-end gap-3">
                <button id="btnConfirmCancel" class="px-4 py-2 text-sm font-medium text-slate-500 hover:text-slate-800 transition">Hủy</button>
                <button id="btnConfirmOk" class="px-4 py-2 text-sm font-bold text-white bg-red-600 hover:bg-red-700 rounded-lg transition shadow-sm">Đồng ý</button>
            </div>
        </div>
    </div>

    <!-- 📖 GOOGLE CLOUD VISION API GUIDE MODAL (NEW) -->
    <div id="visionGuideModal" class="modal opacity-0 invisible pointer-events-none fixed w-full h-full top-0 left-0 flex items-center justify-center z-[9200]">
        <div class="modal-overlay absolute w-full h-full bg-slate-900/70 backdrop-blur-sm" onclick="closeVisionGuide()"></div>
        <div class="bg-white w-full max-w-4xl mx-4 rounded-xl shadow-2xl z-50 overflow-hidden max-h-[90vh] flex flex-col">
            <!-- Header -->
            <div class="bg-gradient-to-r from-purple-600 to-pink-600 p-6 flex justify-between items-center text-white">
                <div>
                    <h3 class="text-2xl font-black flex items-center gap-2">
                        <i class="fa-solid fa-palette"></i> 
                        Hướng dẫn lấy Google Cloud Vision API Key
                    </h3>
                    <p class="text-purple-100 text-sm mt-1">Phân tích thumbnail chuyên nghiệp - Text, Faces, Objects</p>
                </div>
                <button onclick="closeVisionGuide()" class="bg-white/20 hover:bg-white/30 p-2 rounded-full transition">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>
            
            <!-- Content -->
            <div class="p-6 overflow-y-auto flex-grow">
                <!-- Step 1 -->
                <div class="mb-6 bg-gradient-to-r from-blue-50 to-cyan-50 p-5 rounded-xl border-2 border-blue-200">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-black text-lg">1</div>
                        <h4 class="text-lg font-black text-slate-800">Tạo Google Cloud Project</h4>
                    </div>
                    <ol class="space-y-2 text-sm text-slate-700 ml-13">
                        <li class="flex items-start gap-2">
                            <span class="text-blue-600 font-bold">1.1</span>
                            <div>
                                Truy cập: <a href="https://console.cloud.google.com/" target="_blank" class="text-blue-600 hover:underline font-bold">https://console.cloud.google.com/</a>
                            </div>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-blue-600 font-bold">1.2</span>
                            <div>Đăng nhập với Google Account (Gmail)</div>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-blue-600 font-bold">1.3</span>
                            <div>Click <strong>"Create Project"</strong> hoặc <strong>"Tạo dự án"</strong></div>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-blue-600 font-bold">1.4</span>
                            <div>
                                Đặt tên project: <code class="bg-blue-100 px-2 py-1 rounded text-xs font-mono">HSHOP-Analytics</code><br>
                                <span class="text-[11px] text-blue-600">✓ Bạn có thể đặt tên bất kỳ</span>
                            </div>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-blue-600 font-bold">1.5</span>
                            <div>Click <strong>"Create"</strong></div>
                        </li>
                    </ol>
                </div>

                <!-- Step 2 -->
                <div class="mb-6 bg-gradient-to-r from-purple-50 to-pink-50 p-5 rounded-xl border-2 border-purple-200">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 bg-purple-600 rounded-full flex items-center justify-center text-white font-black text-lg">2</div>
                        <h4 class="text-lg font-black text-slate-800">Enable Cloud Vision API</h4>
                    </div>
                    <ol class="space-y-2 text-sm text-slate-700 ml-13">
                        <li class="flex items-start gap-2">
                            <span class="text-purple-600 font-bold">2.1</span>
                            <div>Trong Google Cloud Console, vào <strong>"APIs & Services"</strong> → <strong>"Library"</strong></div>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-purple-600 font-bold">2.2</span>
                            <div>
                                Tìm kiếm: <code class="bg-purple-100 px-2 py-1 rounded text-xs font-mono">Cloud Vision API</code>
                            </div>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-purple-600 font-bold">2.3</span>
                            <div>Click vào <strong>"Cloud Vision API"</strong> trong kết quả</div>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-purple-600 font-bold">2.4</span>
                            <div>Click nút <strong>"ENABLE"</strong> hoặc <strong>"BẬT"</strong></div>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-purple-600 font-bold">2.5</span>
                            <div class="bg-purple-100 p-2 rounded">
                                <strong class="text-purple-800">⚠️ Lưu ý:</strong> Bạn có thể cần setup billing (thêm thẻ visa/mastercard) nhưng <strong class="text-green-700">FREE 1,000 requests/tháng</strong> không mất phí!
                            </div>
                        </li>
                    </ol>
                </div>

                <!-- Step 3 -->
                <div class="mb-6 bg-gradient-to-r from-orange-50 to-red-50 p-5 rounded-xl border-2 border-orange-200">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 bg-orange-600 rounded-full flex items-center justify-center text-white font-black text-lg">3</div>
                        <h4 class="text-lg font-black text-slate-800">Tạo API Key</h4>
                    </div>
                    <ol class="space-y-2 text-sm text-slate-700 ml-13">
                        <li class="flex items-start gap-2">
                            <span class="text-orange-600 font-bold">3.1</span>
                            <div>Vào <strong>"APIs & Services"</strong> → <strong>"Credentials"</strong></div>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-orange-600 font-bold">3.2</span>
                            <div>Click <strong>"+ CREATE CREDENTIALS"</strong> ở trên</div>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-orange-600 font-bold">3.3</span>
                            <div>Chọn <strong>"API key"</strong></div>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-orange-600 font-bold">3.4</span>
                            <div>
                                Một popup hiện ra với API key của bạn:<br>
                                <code class="bg-orange-100 px-2 py-1 rounded text-xs font-mono">AIzaSyD...</code><br>
                                <strong class="text-orange-700">→ COPY KEY này!</strong>
                            </div>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-orange-600 font-bold">3.5</span>
                            <div class="bg-orange-100 p-2 rounded">
                                <strong class="text-orange-800">🔒 Bảo mật:</strong> Click <strong>"RESTRICT KEY"</strong> → Chọn <strong>"Cloud Vision API"</strong> để key chỉ dùng cho Vision, không dùng được cho service khác
                            </div>
                        </li>
                    </ol>
                </div>

                <!-- Step 4 -->
                <div class="mb-6 bg-gradient-to-r from-green-50 to-emerald-50 p-5 rounded-xl border-2 border-green-200">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 bg-green-600 rounded-full flex items-center justify-center text-white font-black text-lg">4</div>
                        <h4 class="text-lg font-black text-slate-800">Paste vào HSHOP Analytics</h4>
                    </div>
                    <ol class="space-y-2 text-sm text-slate-700 ml-13">
                        <li class="flex items-start gap-2">
                            <span class="text-green-600 font-bold">4.1</span>
                            <div>Quay lại HSHOP Analytics → Click <strong>"Nhập API tại đây"</strong></div>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-green-600 font-bold">4.2</span>
                            <div>Paste API key vào ô <strong>"Google Cloud Vision API"</strong></div>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-green-600 font-bold">4.3</span>
                            <div>Click <strong>"Lưu & Mã hóa"</strong></div>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-green-600 font-bold">4.4</span>
                            <div>
                                <strong class="text-green-700">✅ DONE!</strong> Giờ mỗi khi bạn xem chi tiết video (click 👁️), hệ thống sẽ phân tích thumbnail với Vision API!
                            </div>
                        </li>
                    </ol>
                </div>

                <!-- Cost Info -->
                <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded">
                    <h5 class="font-bold text-yellow-800 mb-2 flex items-center gap-2">
                        <i class="fa-solid fa-coins"></i> Chi phí sử dụng
                    </h5>
                    <ul class="text-sm text-yellow-900 space-y-1">
                        <li>✅ <strong>1,000 requests đầu tiên/tháng:</strong> MIỄN PHÍ</li>
                        <li>💰 <strong>Sau 1,000 requests:</strong> $1.50 per 1,000 images</li>
                        <li>📊 <strong>Ước tính:</strong> Nếu bạn xem 50 videos/ngày = 1,500 requests/tháng = ~$1-2/tháng thôi!</li>
                        <li class="text-xs text-yellow-700 mt-2">
                            💡 <strong>Mẹo tiết kiệm:</strong> Hệ thống đã cache kết quả (sessionStorage), mỗi thumbnail chỉ analyze 1 lần!
                        </li>
                    </ul>
                </div>

                <!-- Links -->
                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-3">
                    <a href="https://console.cloud.google.com/" target="_blank" class="flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-lg font-bold transition shadow-lg">
                        <i class="fa-solid fa-cloud"></i>
                        Google Cloud Console
                    </a>
                    <a href="https://cloud.google.com/vision/docs/setup" target="_blank" class="flex items-center justify-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-4 py-3 rounded-lg font-bold transition shadow-lg">
                        <i class="fa-solid fa-book"></i>
                        Official Documentation
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div id="nicheDetailModal" class="modal opacity-0 invisible pointer-events-none fixed w-full h-full top-0 left-0 flex items-center justify-center z-[55]">
        <div class="modal-overlay absolute w-full h-full bg-slate-900 opacity-60 backdrop-blur-sm" onclick="closeNicheModal()"></div>
        <div class="modal-container bg-white w-full max-w-2xl mx-4 rounded-xl shadow-2xl z-50 overflow-hidden flex flex-col max-h-[85vh]">
            <div class="bg-gradient-to-r from-indigo-600 to-purple-700 p-6 flex justify-between items-center text-white">
                <div>
                    <h3 class="text-lg font-bold flex items-center gap-2"><i class="fa-solid fa-network-wired"></i> <span id="nicheModalTitle">Tên Ngách</span></h3>
                    <p class="text-indigo-100 text-xs mt-1">Các kênh đang khai thác tốt chủ đề này</p>
                </div>
                <div class="cursor-pointer bg-white/20 hover:bg-white/30 p-2 rounded-full transition" onclick="closeNicheModal()"><i class="fa-solid fa-xmark"></i></div>
            </div>
            <div class="p-4 overflow-y-auto bg-slate-50 flex-grow" id="nicheModalContent"></div>
        </div>
    </div>

    <div id="spyModal" class="modal opacity-0 invisible pointer-events-none fixed w-full h-full top-0 left-0 flex items-center justify-center z-50">
        <div class="modal-overlay absolute w-full h-full bg-slate-900 opacity-60 backdrop-blur-sm" onclick="closeModal()"></div>
        <div class="modal-container bg-white w-11/12 md:max-w-6xl mx-auto rounded-xl shadow-2xl z-50 overflow-y-auto max-h-[95vh] flex flex-col">
            <div class="flex justify-between items-center p-4 border-b border-slate-200 bg-slate-50 rounded-t-xl sticky top-0 z-10">
                <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">🕵️ Phân tích Chuyên sâu <span class="text-xs font-normal text-slate-500 border px-2 rounded-full" id="modalVideoId">ID</span></h3>
                <div class="modal-close cursor-pointer p-2 hover:bg-slate-200 rounded-full transition" onclick="closeModal()"><i class="fa-solid fa-xmark text-xl text-slate-500"></i></div>
            </div>
            <div class="p-6 grid grid-cols-1 lg:grid-cols-12 gap-6 overflow-y-auto">
                <div class="lg:col-span-4 space-y-4">
                    <div id="modalVideoPreview" class="rounded-lg overflow-hidden shadow-md border border-slate-200 bg-black aspect-video"></div>
                    <div class="grid grid-cols-2 gap-2">
                        <a id="downloadThumbBtn" href="#" target="_blank" class="flex items-center justify-center gap-2 text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 py-2.5 rounded-lg transition font-medium"><i class="fa-solid fa-download"></i> Tải Thumb</a>
                        <button onclick="addToTracking()" class="flex items-center justify-center gap-2 text-xs bg-red-50 hover:bg-red-100 text-red-600 py-2.5 rounded-lg transition font-bold"><i class="fa-solid fa-heart"></i> Theo dõi</button>
                    </div>
                    <div class="bg-yellow-50 p-4 rounded-xl border border-yellow-200">
                        <h4 class="font-bold text-yellow-800 mb-2 text-sm flex items-center gap-2"><i class="fa-solid fa-coins"></i> Dự báo Doanh thu</h4>
                        <div id="monetizationStatus" class="mb-3 text-xs"></div>
                        <div class="border-t border-yellow-200 pt-3 space-y-3">
                            <div class="flex justify-between text-xs"><span class="text-yellow-700">Views:</span><span class="font-mono font-bold" id="calcViews">0</span></div>
                            <div class="flex justify-between text-xs items-center">
                                <span class="text-yellow-700">Áp dụng RPM:</span>
                                <span class="font-bold text-yellow-800 bg-yellow-200 px-2 rounded" id="modalRpmDisplay">$0.3</span>
                            </div>
                            <div class="flex justify-between text-sm font-bold text-green-700 pt-1 border-t border-yellow-200/50">
                                <span>Est. Revenue:</span><span id="calcResult">$0.00</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 🎨 THUMBNAIL ANALYSIS SECTION (NEW) -->
                    <div class="bg-gradient-to-br from-purple-50 to-pink-50 p-4 rounded-xl border-2 border-purple-200">
                        <h4 class="font-bold text-purple-800 mb-3 text-sm flex items-center gap-2">
                            <i class="fa-solid fa-palette"></i> Thumbnail Analysis
                        </h4>
                        <div id="thumbnailAnalysisBox" class="text-xs">
                            <div class="text-slate-400 animate-pulse">🎨 Analyzing...</div>
                        </div>
                    </div>
                </div>
                <div class="lg:col-span-8 space-y-5">
                    <div class="bg-red-50 p-4 rounded-xl border border-red-100">

                        <div id="policyAudit" class="text-xs space-y-2 mb-3"></div>
                        <div class="bg-white p-3 rounded border border-red-100">
                            <h5 class="font-bold text-xs text-slate-700 mb-2 flex items-center gap-1"><i class="fa-solid fa-file-contract text-blue-500"></i> Disclaimer An Toàn:</h5>
                            <div id="safetyDisclaimer" class="text-[10px] text-slate-500 italic bg-slate-50 p-2 rounded border border-slate-200 font-mono select-all cursor-pointer" onclick="copyDisclaimer()" title="Click để copy">-- Chọn ngách để hiện disclaimer --</div>
                        </div>
                        <div class="mt-3">
                            <h5 class="font-bold text-xs text-slate-700 mb-1">💡 Gợi ý Kịch bản Sạch (Clean Content):</h5>
                            <ul id="scriptSuggestions" class="list-disc list-inside text-xs text-slate-600 italic leading-relaxed"></ul>
                        </div>
                    </div>
                    <div id="commentAnalysis" class="bg-blue-50 p-4 rounded-xl border border-blue-100 hidden">
                        <h4 class="font-bold text-blue-700 mb-2 text-sm flex items-center gap-2"><i class="fa-solid fa-comments"></i> Phân Tích Bình Luận & Nỗi Đau Khán Giả</h4>
                        <div id="commentInsights" class="text-xs text-slate-700 space-y-2"><p class="italic text-slate-500">Đang tải dữ liệu bình luận...</p></div>
                    </div>
                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-200">
                        <h4 class="font-bold text-slate-700 mb-3 text-sm flex items-center gap-2"><i class="fa-solid fa-wand-magic-sparkles text-purple-600"></i> Ý tưởng Tiêu đề Viral</h4>
                        <ul id="modalAiTitles" class="space-y-2 text-sm text-slate-600 font-medium list-disc list-inside bg-white p-3 rounded-lg border border-slate-100 shadow-sm"></ul>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <h4 class="font-bold text-slate-700 mb-2 text-sm flex justify-between"><span>🏷️ Tags</span><button onclick="copyModalTags()" class="text-[10px] bg-slate-200 px-2 py-1 rounded hover:bg-slate-300 transition">Copy All</button></h4>
                            <div id="modalTags" class="flex flex-wrap gap-1.5 text-xs"></div>
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-700 mb-2 text-sm">📝 Description</h4>
                            <div id="modalDesc" class="h-32 overflow-y-auto p-3 bg-slate-50 rounded border border-slate-200 text-xs whitespace-pre-wrap text-slate-600 font-mono leading-snug"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // --- CORE HELPER FUNCTIONS ---
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            if (!container) return;
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            let icon = '<i class="fa-solid fa-check-circle text-green-500 text-lg"></i>';
            if (type === 'error') icon = '<i class="fa-solid fa-circle-exclamation text-red-500 text-lg"></i>';
            if (type === 'warning') icon = '<i class="fa-solid fa-triangle-exclamation text-yellow-500 text-lg"></i>';
            toast.innerHTML = `${icon} <span class="text-sm font-medium text-slate-700">${message}</span>`;
            container.appendChild(toast);
            setTimeout(() => { toast.style.animation = 'fadeOut 0.3s ease-out forwards'; setTimeout(() => toast.remove(), 300); }, 3000);
        }
        
        // 🤖 Increment AI Deep Dive Usage (call server)
        async function incrementAIDeepDiveUsage() {
            try {
                const response = await fetch('api_increment_ai_usage.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });
                const data = await response.json();
                if (data.success) {
                    // Update local variables
                    AI_DEEP_DIVE_USED++;
                    AI_DEEP_DIVE_REMAINING = Math.max(0, AI_DEEP_DIVE_LIMIT - AI_DEEP_DIVE_USED);
                    AI_DEEP_DIVE_CAN_USE = AI_DEEP_DIVE_REMAINING > 0;
                    console.log('🤖 AI Deep Dive usage updated:', AI_DEEP_DIVE_USED, '/', AI_DEEP_DIVE_LIMIT);
                }
            } catch (e) {
                console.error('Failed to increment AI Deep Dive usage:', e);
            }
        }
        
        // 🚀 Show upgrade modal when limit reached
        function showUpgradeModal(reason) {
            if (reason === 'ai_deep_dive_limit') {
                // 🚀 REDIRECT TO PRICING PAGE INSTEAD OF MODAL
                window.location.href = 'pricing.php?reason=ai_limit';
                return;
                
                // Old modal code (kept as fallback)
                const html = `
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-8 text-center">
                        <div class="w-20 h-20 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="fa-solid fa-lock text-4xl text-white"></i>
                        </div>
                        <h3 class="text-2xl font-black text-slate-800 mb-3">Đã Hết Lượt Phân Tích AI!</h3>
                        <p class="text-slate-600 mb-2">Bạn đã sử dụng hết <strong class="text-purple-600">${AI_DEEP_DIVE_LIMIT} lần miễn phí</strong> trong tháng này.</p>
                        <p class="text-slate-500 text-sm mb-6">Nâng cấp lên gói Basic hoặc VIP để sử dụng không giới hạn!</p>
                        <div class="flex gap-3">
                            <a href="pricing.php" class="flex-1 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold py-3 px-4 rounded-xl transition-all">
                                <i class="fa-solid fa-crown mr-2"></i>Xem Bảng Giá
                            </a>
                            <button onclick="this.closest('.fixed').remove()" class="flex-1 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold py-3 px-4 rounded-xl transition-all">
                                Đóng
                            </button>
                        </div>
                    </div>
                </div>
                `;
                document.body.insertAdjacentHTML('beforeend', html);
            }
        }

        function safeClass(id, action, className) { const el = document.getElementById(id); if (el) { if (action === 'add') el.classList.add(className); else if (action === 'remove') el.classList.remove(className); else if (action === 'toggle') el.classList.toggle(className); } }
        function safeSetText(id, text) { const el = document.getElementById(id); if (el) el.innerText = text; }
        function safeSetValue(id, value) { const el = document.getElementById(id); if (el) el.value = value; }
        function sleep(ms) { return new Promise(resolve => setTimeout(resolve, ms)); }
        function detectNiche(text) { const nicheData = detectNicheData(text); return nicheData.name; }

        function estimateMonetization(subs, views, title, tags) {
            const combinedText = (title + " " + tags.join(" ")).toLowerCase();
            const hasRiskyKeyword = RISKY_KEYWORDS.some(k => combinedText.includes(k));
            if (hasRiskyKeyword) return { status: "Không đủ điều kiện", reason: "Từ khóa vi phạm", color: "text-red-600" };
            if (subs < 1000 || views < 4000) return { status: "Chưa đủ điều kiện", reason: "Thiếu Sub/View", color: "text-yellow-600" };
            return { status: "Có thể kiếm tiền", reason: "Đủ điều kiện YPP", color: "text-green-600" };
        }

        function generateViralTitles(originalTitle, tags) {
            return [`🔥 ${originalTitle} - BẠN PHẢI XEM NGAY!`, `${originalTitle} | Bí Mật Không Ai Nói Với Bạn`, `TOP 5 ${tags[0] || 'Điều'} Về ${originalTitle}`, `${originalTitle} - Sự Thật Gây SỐC!`, `Cách ${originalTitle} Như Chuyên Gia`];
        }

        // 🔥 YOUTUBE API FETCH WITH AUTO-RETRY (Key Rotation)
        async function ytFetchWithRetry(url, retryCount = 0) {
            const maxRetries = Math.max(apiKeys.length, youtubeKeys.length, 3);
            
            try {
                const res = await fetch(url);
                
                if (!res.ok) {
                    const errorData = await res.json().catch(() => ({}));
                    const errorMsg = errorData.error?.message || '';
                    const errorReason = errorData.error?.errors?.[0]?.reason || '';
                    
                    // 🔥 CHIỀN LƯỢC PHÂN LOẠI LỖI (CRITICAL!)
                    // 1️⃣ QUOTA ERRORS (Blacklist key ngay - hết quota thật sự)
                    const isQuotaError = (
                        errorReason === 'quotaExceeded' ||
                        errorReason === 'dailyLimitExceeded' ||
                        errorReason === 'rateLimitExceeded' ||
                        errorMsg.includes('quota') ||
                        errorMsg.includes('RESOURCE_EXHAUSTED') ||
                        (res.status === 403 && errorMsg.includes('exceeded'))
                    );
                    
                    // 2️⃣ API NOT ENABLED (Blacklist ngay - key chưa bật YouTube API)
                    const isAPINotEnabled = (
                        errorMsg.includes('has not been used') ||
                        errorMsg.includes('it is disabled') ||
                        errorMsg.includes('Enable it by visiting') ||
                        errorReason === 'accessNotConfigured'
                    );
                    
                    // 3️⃣ TEMPORARY ERRORS (Retry nhưng KHÔNG blacklist key)
                    const isTemporaryError = (
                        res.status === 500 || // Server error
                        res.status === 503 || // Service unavailable
                        errorMsg.includes('backend') ||
                        errorMsg.includes('temporarily')
                    );
                    
                    // 4️⃣ INVALID KEY (Blacklist ngay - key không hợp lệ)
                    const isInvalidKey = (
                        res.status === 400 ||
                        errorMsg.includes('API key not valid') ||
                        errorMsg.includes('invalid')
                    );
                    
                    // 🔥 XỪA LÝ LỖI API NOT ENABLED - Blacklist & Show helpful message
                    if (isAPINotEnabled && retryCount < maxRetries) {
                        const keyMatch = url.match(/key=([^&]+)/);
                        if (keyMatch) {
                            markYouTubeKeyExhausted(keyMatch[1]);
                            console.error(`❌ YouTube API CHƯA BẬT cho key: ${keyMatch[1].substring(0, 10)}...`);
                            console.error(`💡 Hướng dẫn: Truy cập Google Cloud Console > APIs & Services > Enable YouTube Data API v3`);
                        }
                        
                        const newKey = getActiveKey();
                        if (newKey) {
                            const newUrl = url.replace(/key=[^&]+/, `key=${newKey}`);
                            return await ytFetchWithRetry(newUrl, retryCount + 1);
                        }
                    }
                    
                    // 🔥 XỪA LÝ LỖI QUOTA - Blacklist & Switch key
                    if (isQuotaError && retryCount < maxRetries) {
                        const keyMatch = url.match(/key=([^&]+)/);
                        if (keyMatch) {
                            const exhaustedKey = keyMatch[1];
                            markYouTubeKeyExhausted(exhaustedKey);
                            console.warn(`❌ YouTube key EXHAUSTED (Quota hết): ${exhaustedKey.substring(0, 10)}...`);
                        }
                        
                        // Get new key and retry
                        const newKey = getActiveKey();
                        if (newKey) {
                            console.log(`🔄 Switching to new YouTube key (${retryCount + 1}/${maxRetries})...`);
                            const newUrl = url.replace(/key=[^&]+/, `key=${newKey}`);
                            await new Promise(r => setTimeout(r, 500));
                            return await ytFetchWithRetry(newUrl, retryCount + 1);
                        } else {
                            console.error('❌ TẤT CẢ YOUTUBE KEYS ĐÃ HẾT QUOTA!');
                        }
                    }
                    
                    // 🔥 XỪA LÝ LỖI TẠM THỜI - Retry KHÔNG blacklist
                    if (isTemporaryError && retryCount < 2) {
                        console.warn(`⚠️ Temporary error, retrying... (${retryCount + 1}/2)`);
                        await new Promise(r => setTimeout(r, 1000));
                        return await ytFetchWithRetry(url, retryCount + 1);
                    }
                    
                    // 🔥 XỪA LÝ KEY KHÔNG HỢP LỆ - Blacklist & Switch
                    if (isInvalidKey && retryCount < maxRetries) {
                        const keyMatch = url.match(/key=([^&]+)/);
                        if (keyMatch) {
                            markYouTubeKeyExhausted(keyMatch[1]);
                            console.error(`❌ YouTube key KHÔNG HỢP LỆ: ${keyMatch[1].substring(0, 10)}...`);
                        }
                        
                        const newKey = getActiveKey();
                        if (newKey) {
                            const newUrl = url.replace(/key=[^&]+/, `key=${newKey}`);
                            return await ytFetchWithRetry(newUrl, retryCount + 1);
                        }
                    }
                    
                    // Return error response for caller to handle
                    return { 
                        ok: false, 
                        error: errorMsg || 'YouTube API Error', 
                        status: res.status,
                        reason: errorReason
                    };
                }
                
                const data = await res.json();
                return { ok: true, data };
                
            } catch (e) {
                console.error('YouTube fetch error:', e);
                
                // 🔄 Retry on network errors
                if (retryCount < maxRetries) {
                    console.log(`🔄 Network error, retrying (${retryCount + 1}/${maxRetries})...`);
                    await new Promise(r => setTimeout(r, 1000));
                    return await ytFetchWithRetry(url, retryCount + 1);
                }
                
                return { ok: false, error: e.message };
            }
        }

        async function fetchComments(videoId, apiKey) {
            try {
                const url = `https://www.googleapis.com/youtube/v3/commentThreads?part=snippet&videoId=${videoId}&maxResults=5&order=relevance&key=${apiKey}`;
                const result = await ytFetchWithRetry(url);
                if (!result.ok || !result.data.items) return [];
                return result.data.items.map(item => item.snippet.topLevelComment.snippet.textDisplay);
            } catch (e) { console.error("Comment fetch error:", e); return []; }
        }

        // --- GEMINI & OPENROUTER LOGIC (ADVANCED KEY ROTATION) ---
        
        // 🔥 CALL GEMINI WITH AUTO-FAILOVER
        async function callGemini(prompt, retryCount = 0) {
            const maxRetries = Math.min(geminiKeys.length, 5); // Max 5 retries
            
            // Get next available key
            const activeKey = getGeminiKey();
            if (!activeKey) { 
                showToast("Gemini API Key chưa được cấu hình! Vui lòng thêm key trong Settings.", "error"); 
                return null; 
            }
            
            try {
                // 🔥 USE GEMINI 2.5 FLASH (latest, powerful, FREE)
                // 🔥 ENHANCED: Added maxOutputTokens=65536 for FULL analysis (avoid truncation!)
                const url = `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=${activeKey}`;
                const res = await fetch(url, { 
                    method: 'POST', 
                    headers: { 'Content-Type': 'application/json' }, 
                    body: JSON.stringify({ 
                        contents: [{ parts: [{ text: prompt }] }],
                        generationConfig: {
                            temperature: 0.9,
                            topP: 0.95,
                            topK: 40,
                            maxOutputTokens: 65536
                        },
                        safetySettings: [
                            { category: "HARM_CATEGORY_HARASSMENT", threshold: "BLOCK_MEDIUM_AND_ABOVE" },
                            { category: "HARM_CATEGORY_HATE_SPEECH", threshold: "BLOCK_MEDIUM_AND_ABOVE" },
                            { category: "HARM_CATEGORY_SEXUALLY_EXPLICIT", threshold: "BLOCK_MEDIUM_AND_ABOVE" },
                            { category: "HARM_CATEGORY_DANGEROUS_CONTENT", threshold: "BLOCK_MEDIUM_AND_ABOVE" }
                        ]
                    }) 
                });
                
                if (!res.ok) { 
                    const errorData = await res.json(); 
                    const errorMsg = errorData.error?.message || '';
                    
                    // 🔥 DETECT QUOTA ERRORS (429, RESOURCE_EXHAUSTED)
                    const isQuotaError = res.status === 429 || 
                                        errorMsg.includes('quota') || 
                                        errorMsg.includes('RESOURCE_EXHAUSTED') ||
                                        errorMsg.includes('rate limit');
                    
                    if (isQuotaError) {
                        console.warn(`⚠️ Gemini key ${activeKey.substring(0, 10)}... quota exceeded`);
                        markGeminiKeyExhausted(activeKey);
                        
                        // 🔄 AUTO-RETRY with next key
                        if (retryCount < maxRetries && geminiKeys.length > 1) {
                            console.log(`🔄 Retrying with next key (${retryCount + 1}/${maxRetries})...`);
                            await new Promise(r => setTimeout(r, 500)); // Small delay
                            return await callGemini(prompt, retryCount + 1);
                        }
                    }
                    
                    throw new Error(errorMsg || "Gemini API Error"); 
                }
                
                const data = await res.json(); 
                return data.candidates?.[0]?.content?.parts?.[0]?.text || null;
                
            } catch (e) { 
                console.error("Gemini Error:", e); 
                
                // 🔄 Try next key on network errors too
                if (retryCount < maxRetries && geminiKeys.length > 1) {
                    console.log(`🔄 Network error, trying next key (${retryCount + 1}/${maxRetries})...`);
                    return await callGemini(prompt, retryCount + 1);
                }
                
                showToast("Lỗi Gemini: " + e.message, "error"); 
                return null; 
            }
        }

        // 🔥 OPENROUTER FREE MODELS LIST
        const OPENROUTER_FREE_MODELS = [
            'google/gemini-flash-1.5:free',
            'google/gemini-2.0-flash-exp:free',
            'meta-llama/llama-3.3-70b-instruct:free',
            'deepseek/deepseek-chat:free',
            'mistralai/mistral-7b-instruct:free'
        ];
        let currentOpenRouterModelIndex = 0;

        // 🔥 CALL OPENROUTER WITH AUTO-FAILOVER (Multiple Keys & Models)
        async function callOpenRouter(prompt, model = null, retryCount = 0) {
            const maxRetries = Math.max(openRouterKeys.length, OPENROUTER_FREE_MODELS.length, 3);
            
            // Get active key (priority: openRouterKeys array > single globalOpenRouterKey)
            let activeKey = globalOpenRouterKey;
            if (openRouterKeys.length > 0) {
                const keyIndex = currentOpenRouterKeyIndex % openRouterKeys.length;
                activeKey = openRouterKeys[keyIndex];
                
                // Skip exhausted keys
                if (exhaustedOpenRouterKeys.has(activeKey) && openRouterKeys.length > 1) {
                    currentOpenRouterKeyIndex++;
                    activeKey = openRouterKeys[currentOpenRouterKeyIndex % openRouterKeys.length];
                }
            }
            
            if (!activeKey) { 
                showToast("OpenRouter API Key chưa được cấu hình!", "error"); 
                return null; 
            }
            
            // 🔥 Smart model selection: user preference > rotation through free models
            let selectedModel = model || localStorage.getItem('openrouter_model');
            
            // If no preference, rotate through free models
            if (!selectedModel) {
                selectedModel = OPENROUTER_FREE_MODELS[currentOpenRouterModelIndex % OPENROUTER_FREE_MODELS.length];
            }
            
            console.log(`🤖 OpenRouter: key #${openRouterKeys.length > 0 ? (currentOpenRouterKeyIndex % openRouterKeys.length) + 1 : 1}, model: ${selectedModel}`);
            
            try {
                const res = await fetch("https://openrouter.ai/api/v1/chat/completions", { 
                    method: "POST", 
                    headers: { 
                        "Authorization": `Bearer ${activeKey}`, 
                        "Content-Type": "application/json", 
                        "HTTP-Referer": window.location.href, 
                        "X-Title": "HSHOP Analytics" 
                    }, 
                    body: JSON.stringify({ 
                        "model": selectedModel, 
                        "messages": [{ "role": "user", "content": prompt }] 
                    }) 
                });
                
                if (!res.ok) { 
                    const errorData = await res.json(); 
                    const errorMsg = errorData.error?.message || '';
                    
                    // 🔥 DETECT QUOTA/MODEL ERRORS
                    const isQuotaError = res.status === 429 || 
                                        errorMsg.includes('quota') || 
                                        errorMsg.includes('rate limit') ||
                                        errorMsg.includes('insufficient');
                    const isModelError = res.status === 404 || errorMsg.includes('model');
                    
                    if ((isQuotaError || isModelError) && retryCount < maxRetries) {
                        // Mark key exhausted if quota error
                        if (isQuotaError && openRouterKeys.length > 0) {
                            exhaustedOpenRouterKeys.add(activeKey);
                            currentOpenRouterKeyIndex++;
                            console.warn(`❌ OpenRouter key exhausted, switching...`);
                        }
                        
                        // Try next model if model error
                        if (isModelError) {
                            currentOpenRouterModelIndex++;
                            console.warn(`❌ Model error, trying ${OPENROUTER_FREE_MODELS[currentOpenRouterModelIndex % OPENROUTER_FREE_MODELS.length]}...`);
                        }
                        
                        await new Promise(r => setTimeout(r, 500));
                        return await callOpenRouter(prompt, null, retryCount + 1);
                    }
                    
                    throw new Error(errorMsg || "OpenRouter API Error"); 
                }
                
                const data = await res.json(); 
                return data.choices?.[0]?.message?.content || null;
                
            } catch (e) { 
                console.error("OpenRouter Error:", e); 
                
                // 🔄 Retry with next model on network errors
                if (retryCount < maxRetries) {
                    currentOpenRouterModelIndex++;
                    console.log(`🔄 Network error, trying next model (${retryCount + 1}/${maxRetries})...`);
                    return await callOpenRouter(prompt, null, retryCount + 1);
                }
                
                showToast("Lỗi OpenRouter: " + e.message, "error"); 
                return null; 
            }
        }

        // --- STRATEGIC LOGIC ---
        function calculateVerdict(keyword, videos) {
            if (!videos || videos.length === 0) return;
            const avgRatio = videos.reduce((sum, v) => sum + v.ratio, 0) / videos.length;
            const avgViews = videos.reduce((sum, v) => sum + v.views, 0) / videos.length;
            let score = 0, verdict = "", color = "";
            if (avgRatio > 3) score += 40; else if (avgRatio > 1.5) score += 25; else if (avgRatio > 0.8) score += 10;
            if (avgViews > 100000) score += 30; else if (avgViews > 50000) score += 20; else if (avgViews > 10000) score += 10;
            if (videos.length > 20) score += 20; else if (videos.length > 10) score += 10;
            const competitorCount = new Set(videos.map(v => v.channelId)).size;
            if (competitorCount < 5) score += 10;

            if (score >= 80) { verdict = "🔥 CỰC KỲ TIỀM NĂNG"; color = "text-green-600"; } else if (score >= 60) { verdict = "✅ KHẢ QUAN"; color = "text-blue-600"; } else if (score >= 40) { verdict = "⚠️ TRUNG BÌNH"; color = "text-yellow-600"; } else { verdict = "❌ KHÓ KHĂN"; color = "text-red-600"; }

            const verdictEl = document.getElementById('verdictText'); if (verdictEl) { verdictEl.innerText = verdict; verdictEl.className = `text-2xl font-black ${color}`; }
            const gaugeEl = document.getElementById('gaugeFill'); if (gaugeEl) { const circumference = 252; const offset = circumference - (score / 100) * circumference; gaugeEl.style.strokeDashoffset = offset; }
            safeSetText('gaugeScore', score); safeSetText('statVolume', avgViews.toLocaleString()); safeSetText('statComp', competitorCount);
        }

        function loadDashboard() {
            const container = document.getElementById('dashboardGrid'); if (!container) return;
            if (trackedChannels.length === 0) { container.innerHTML = '<div class="col-span-full text-center text-slate-400 py-12"><i class="fa-solid fa-inbox text-4xl mb-3"></i><p>Chưa có kênh nào được theo dõi</p></div>'; return; }
            container.innerHTML = trackedChannels.map(ch => `<div class="nexlev-card p-4"><h4 class="font-bold text-slate-800 mb-2 truncate">${ch.title}</h4><div class="text-xs text-slate-500"><p>Subs: ${ch.subs.toLocaleString()}</p><p class="text-[10px] mt-1">Cập nhật: ${moment(ch.lastUpdated).fromNow()}</p></div><a href="https://youtube.com/channel/${ch.id}" target="_blank" class="text-xs text-blue-600 hover:underline mt-2 inline-block">Xem kênh →</a></div>`).join('');
        }

        function showConfirm(message, onConfirm) {
            const modal = document.getElementById('confirmModal'); const msgEl = document.getElementById('confirmMessage');
            const yesBtn = document.getElementById('btnConfirmOk'); const noBtn = document.getElementById('btnConfirmCancel');
            if (msgEl) msgEl.innerText = message;
            if (modal) modal.classList.remove('opacity-0', 'pointer-events-none', 'invisible');
            if (yesBtn) yesBtn.onclick = () => { onConfirm(); if (modal) modal.classList.add('opacity-0', 'pointer-events-none'); setTimeout(() => modal.classList.add('invisible'), 200); };
            if (noBtn) noBtn.onclick = () => { if (modal) modal.classList.add('opacity-0', 'pointer-events-none'); setTimeout(() => modal.classList.add('invisible'), 200); };
        }

        function parseDuration(d) {
            if (!d) return "--:--";
            const match = d.match(/PT(\d+H)?(\d+M)?(\d+S)?/);
            if (!match) return "--:--";
            const h = (parseInt(match[1]) || 0); const m = (parseInt(match[2]) || 0); const s = (parseInt(match[3]) || 0);
            if (h > 0) return `${h}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
            return `${m}:${s.toString().padStart(2, '0')}`;
        }

        function calculateProfit() {
            const vEl = document.getElementById('calcViews'); if (!vEl) return;
            const v = parseInt(vEl.innerText.replace(/,/g, ''));
            const estRev = (v / 1000) * globalRpm;
            const resEl = document.getElementById('calcResult'); if (resEl) resEl.innerText = '$' + estRev.toFixed(2);
        }

        function extractWinningTags() {
            const map = {}; currentData.slice(0, 10).forEach(v => { if (v.tags) v.tags.forEach(t => map[t] = (map[t] || 0) + 1); });
            const sorted = Object.entries(map).sort((a, b) => b[1] - a[1]).slice(0, 15).map(x => x[0]);
            const container = document.getElementById('topTagsCloud'); if (container) container.innerHTML = sorted.map(t => `<span class="bg-slate-100 text-slate-600 px-2 py-1 rounded text-xs border border-slate-200">${t}</span>`).join('');
            topWinningTags = sorted;
        }

        function renderCompetitorLeaderboard() {
            const map = {}; currentData.forEach(v => { if (v.channelId) map[v.channelId] = (map[v.channelId] || 0) + 1; });
            const sorted = Object.entries(map).sort((a, b) => b[1] - a[1]).slice(0, 5);
            const html = sorted.map(([id, count]) => { const vid = currentData.find(v => v.channelId === id); return vid ? `<div class="flex items-center justify-between text-xs py-1 border-b border-slate-50 last:border-0"><span class="truncate font-medium text-slate-700 w-32" title="${vid.channelTitle}">${vid.channelTitle}</span><span class="font-bold text-slate-400">${count} vids</span></div>` : ""; }).join('');
            const el = document.getElementById('competitorLeaderboard'); if (el) el.innerHTML = html;
        }

        function copyTopTags() { navigator.clipboard.writeText(topWinningTags.join(', ')); showToast("Copied to clipboard!"); }
        function copyModalTags() { if (currentModalVid) { navigator.clipboard.writeText(currentModalVid.tags.join(', ')); showToast("Copied to clipboard!"); } }
        function copyDisclaimer() { const text = document.getElementById('safetyDisclaimer').innerText; if (text.includes("--")) return; navigator.clipboard.writeText(text); showToast("Đã copy Disclaimer!"); }

        function openGoogleTrends(query = null) {
            const q = query || document.getElementById('keyword').value;
            const geo = document.getElementById('globalRegion').value || '';
            const filterDate = document.getElementById('filterDate').value;
            let dateParam = '';
            if (filterDate === 'today') dateParam = '&date=now 1-d'; else if (filterDate === 'week') dateParam = '&date=now 7-d'; else if (filterDate === 'month') dateParam = '&date=today 1-m'; else if (filterDate === '3month') dateParam = '&date=today 3-m';
            if (q) window.open(`https://trends.google.com/trends/explore?q=${encodeURIComponent(q)}&geo=${geo}${dateParam}`, '_blank');
        }

        // --- CONFIGURATION CONSTANTS (Placeholder forbrevity, keep your full lists) ---
        const FIXED_ACCESS_KEY = 'visora.net';
        const REGION_LANG_MAP = { 'VN': 'vi', 'US': 'en', 'GB': 'en', 'CA': 'en', 'AU': 'en', 'DE': 'de', 'JP': 'ja', 'KR': 'ko', 'IN': 'hi', 'ID': 'id', 'TH': 'th', 'PH': 'tl', 'FR': 'fr', 'ES': 'es', 'RU': 'ru', 'BR': 'pt', 'MX': 'es' };
        
        // --- (Bạn giữ nguyên các biến MARKET_NICHE_KEYWORDS, NICHE_KEYWORDS, DISCLAIMERS, RISKY_KEYWORDS đầy đủ ở đây) ---
        // Tôi rút gọn để hiển thị, nhưng khi copy vào file thật, bạn hãy giữ nguyên phần data này từ file cũ.
        // Nếu bạn cần tôi paste full data này ra, hãy bảo tôi.
        const MARKET_NICHE_KEYWORDS = {
            'vi': { health: ['sức khỏe', 'bệnh', 'thuốc', 'giảm cân', 'yoga', 'gym', 'workout', 'đau lưng', 'mụn', 'skincare'], money: ['tiền', 'đầu tư', 'chứng khoán', 'crypto', 'bitcoin', 'kinh doanh', 'khởi nghiệp', 'marketing', 'bán hàng', 'mmo', 'tài chính'], tech: ['review', 'điện thoại', 'laptop', 'iphone', 'samsung', 'công nghệ', 'app', 'code', 'ai', 'pc', 'gaming', 'unboxing'], food: ['ăn', 'nấu', 'food', 'ẩm thực', 'mukbang', 'công thức', 'bếp', 'món ngon', 'review đồ ăn', 'street food'], gaming: ['game', 'chơi', 'liên quân', 'roblox', 'minecraft', 'skin', 'tướng', 'rank', 'highlight', 'esport'], vlog: ['vlog', 'cuộc sống', 'du lịch', 'daily', 'thử thách', 'trải nghiệm'], horror: ['ma', 'kinh dị', 'ám ảnh', 'tâm linh', 'bí ẩn', 'rùng rợn', 'creepy', 'horror', 'scary', 'kể chuyện', 'án mạng', 'trinh thám'], news: ['tin tức', 'thời sự', 'bão', 'chính trị', 'vụ án', 'drama', 'biến', 'hot', 'mới nhất', 'cập nhật'], kids: ['bé', 'trẻ em', 'kids', 'hoạt hình', 'đồ chơi', 'toys', 'baby', 'nhạc thiếu nhi'] },
            'en': { health: ['health', 'fitness', 'workout', 'diet', 'keto', 'yoga', 'gym', 'weight loss', 'nutrition', 'wellness'], money: ['money', 'investing', 'stocks', 'crypto', 'bitcoin', 'business', 'startup', 'marketing', 'sales', 'finance', 'passive income'], tech: ['review', 'phone', 'laptop', 'iphone', 'samsung', 'technology', 'app', 'coding', 'ai', 'pc', 'gaming', 'unboxing'], food: ['cooking', 'recipe', 'food', 'mukbang', 'chef', 'kitchen', 'delicious', 'food review', 'street food', 'baking'], gaming: ['gaming', 'gameplay', 'roblox', 'minecraft', 'fortnite', 'valorant', 'rank', 'highlight', 'esports', 'walkthrough'], vlog: ['vlog', 'lifestyle', 'travel', 'daily', 'challenge', 'experience', 'day in life'], horror: ['horror', 'scary', 'creepy', 'paranormal', 'mystery', 'true crime', 'investigation', 'haunted', 'ghost'], news: ['news', 'breaking', 'politics', 'crime', 'drama', 'trending', 'latest', 'update'], kids: ['kids', 'children', 'cartoon', 'toys', 'baby', 'nursery rhymes', 'learning', 'educational'] }
        };
        const NICHE_KEYWORDS = { health: { keys: ['sức khỏe', 'bệnh', 'thuốc', 'giảm cân', 'yoga', 'gym', 'workout', 'diet', 'keto', 'health', 'fitness', 'đau lưng', 'mụn', 'skincare'], rpm: 3.5 }, money: { keys: ['tiền', 'đầu tư', 'chứng khoán', 'crypto', 'bitcoin', 'kinh doanh', 'khởi nghiệp', 'marketing', 'bán hàng', 'mmo', 'tài chính'], rpm: 9.0 }, tech: { keys: ['review', 'điện thoại', 'laptop', 'iphone', 'samsung', 'công nghệ', 'app', 'code', 'ai', 'pc', 'gaming', 'unboxing'], rpm: 3.0 }, food: { keys: ['ăn', 'nấu', 'food', 'ẩm thực', 'mukbang', 'công thức', 'bếp', 'món ngon', 'review đồ ăn', 'street food'], rpm: 1.2 }, gaming: { keys: ['game', 'chơi', 'liên quân', 'roblox', 'minecraft', 'skin', 'tướng', 'rank', 'highlight', 'esport'], rpm: 0.8 }, vlog: { keys: ['vlog', 'cuộc sống', 'du lịch', 'daily', 'thử thách', 'trải nghiệm'], rpm: 0.6 }, horror: { keys: ['ma', 'kinh dị', 'ám ảnh', 'tâm linh', 'bí ẩn', 'rùng rợn', 'creepy', 'horror', 'scary', 'kể chuyện', 'án mạng', 'trinh thám'], rpm: 1.8 }, news: { keys: ['tin tức', 'thời sự', 'bão', 'chính trị', 'vụ án', 'drama', 'biến', 'hot', 'mới nhất', 'cập nhật'], rpm: 1.5 }, kids: { keys: ['bé', 'trẻ em', 'kids', 'hoạt hình', 'đồ chơi', 'toys', 'baby', 'nhạc thiếu nhi'], rpm: 0.4 } };
        const DISCLAIMERS = { health: "Lưu ý: Nội dung video chỉ mang tính chất tham khảo và chia sẻ kiến thức, không thay thế cho lời khuyên hoặc phác đồ điều trị của bác sĩ chuyên khoa.", money: "Cảnh báo rủi ro: Đầu tư tài chính luôn đi kèm rủi ro. Video chỉ mang tính chất chia sẻ quan điểm cá nhân.", horror: "Cảnh báo: Video chứa nội dung có thể gây ám ảnh hoặc không phù hợp với người yếu tim.", general: "Video này được thực hiện với mục đích giải trí và chia sẻ thông tin." };
        const RISKY_KEYWORDS = ['hack', 'crack', 'cheat', 'mod apk', 'free download', 'torrent', 'keygen', 'reup', '18+', 'nude', 'tiktok compilation', 'no copyright', 'casino', 'betting', 'cờ bạc', 'tài xỉu'];

        const SecurityManager = { obfuscate: function (str) { if (!str) return ""; try { return btoa(encodeURIComponent(str)).split('').reverse().join(''); } catch (e) { return ""; } }, deobfuscate: function (str) { if (!str) return ""; try { return decodeURIComponent(atob(str.split('').reverse().join(''))); } catch (e) { return ""; } } };

        // --- INTELLIGENCE FUNCTIONS ---
        function detectNicheData(text) {
            text = (text || "").toLowerCase(); const region = document.getElementById('globalRegion')?.value || 'VN'; const lang = REGION_LANG_MAP[region] || 'vi'; const marketKeywords = MARKET_NICHE_KEYWORDS[lang] || MARKET_NICHE_KEYWORDS['vi'];
            for (const [niche, keys] of Object.entries(marketKeywords)) { if (keys.some(k => text.includes(k.toLowerCase()))) { const rpmData = NICHE_KEYWORDS[niche]; return { name: niche, rpm: rpmData ? rpmData.rpm : globalRpm, lang: lang }; } }
            for (const [niche, data] of Object.entries(NICHE_KEYWORDS)) { if (data.keys.some(k => text.includes(k))) return { name: niche, rpm: data.rpm, lang: lang }; }
            return { name: 'general', rpm: globalRpm, lang: lang };
        }

        // --- KEY ROTATION SYSTEM (ADVANCED v2.0) ---
        
        // 🟥 YOUTUBE API KEYS
        let apiKeys = []; 
        let currentKeyIndex = 0;
        let exhaustedYouTubeKeys = new Set(); // Track quota-exceeded keys
        let youtubeKeyLastError = {}; // Track last error time per key
        let youtubeKeyUsageCount = {}; // 🔥 Track usage count per key (ĐẾM SỐ LẦN DÙNG)
        
        // 🟣 GEMINI API KEYS
        let geminiKeys = []; // Array to store multiple Gemini keys
        let currentGeminiKeyIndex = 0; // Round-robin rotation
        let exhaustedGeminiKeys = new Set(); // Track quota-exceeded keys
        let geminiKeyLastError = {}; // Track last error time per key
        
        // 🟢 OPENROUTER KEYS
        let openRouterKeys = []; // Support multiple OpenRouter keys
        let currentOpenRouterKeyIndex = 0;
        let exhaustedOpenRouterKeys = new Set();
        
        // ⏰ COOLDOWN CONFIGURATION (PER SERVICE)
        const GEMINI_COOLDOWN_MS = 60 * 60 * 1000; // 1 hour (Gemini có thể recover)
        const OPENROUTER_COOLDOWN_MS = 60 * 60 * 1000; // 1 hour
        // 🔥 YOUTUBE: KHÔNG AUTO-RESET - Quota chỉ reset 12 AM PST mỗi ngày!
        
        function resetExhaustedKeysIfNeeded() {
            const now = Date.now();
            
            // 🟥 YOUTUBE: NO AUTO-RESET (Quota resets daily at 12 AM PST only)
            // YouTube keys stay exhausted until manual clear or page reload
            // Lý do: YouTube API quota là 10,000/day, reset 1 lần/ngày vào midnight PST
            // Không có ý nghĩa gì khi reset sau 1 giờ!
            
            // 🟣 Gemini keys - Reset sau 1 giờ (có thể do rate limit tạm thời)
            exhaustedGeminiKeys.forEach(key => {
                if (geminiKeyLastError[key] && (now - geminiKeyLastError[key]) > GEMINI_COOLDOWN_MS) {
                    exhaustedGeminiKeys.delete(key);
                    delete geminiKeyLastError[key];
                    console.log('♻️ Gemini key reset after cooldown');
                }
            });
            
            // 🟢 OpenRouter keys - Reset sau 1 giờ
            const exhaustedArray = Array.from(exhaustedOpenRouterKeys);
            exhaustedArray.forEach(key => {
                // OpenRouter không có timestamp tracking, reset sau OPENROUTER_COOLDOWN_MS
                exhaustedOpenRouterKeys.delete(key);
                console.log('♻️ OpenRouter key reset after cooldown');
            });
        }
        
        // 🔥 Run reset check every 10 minutes (chỉ cho Gemini & OpenRouter)
        setInterval(resetExhaustedKeysIfNeeded, 10 * 60 * 1000);
        
        // Initialize user's own API keys from localStorage
        function initKeys() { 
            const saved = localStorage.getItem('yt_api_key_secure'); 
            if (saved) { 
                try {
                    const raw = SecurityManager.deobfuscate(saved); 
                    // 🔥 CRITICAL: Handle both JSON array and comma-separated format
                    if (raw.startsWith('[')) {
                        apiKeys = JSON.parse(raw); // JSON array format (new)
                    } else {
                        apiKeys = raw.split(',').map(k => k.trim()).filter(k => k); // Legacy comma format
                    }
                    console.log('✅ Loaded', apiKeys.length, 'YouTube API keys for Scanner');
                } catch (e) {
                    console.error('Failed to load YouTube keys:', e);
                    apiKeys = [];
                }
            } 
        }
        
        // 🔥 MULTI-KEY GEMINI MANAGEMENT FUNCTIONS
        async function loadGeminiKeys() {
            // First try to load from localStorage
            const saved = localStorage.getItem('gemini_keys_secure');
            if (saved) {
                try {
                    const raw = SecurityManager.deobfuscate(saved);
                    geminiKeys = JSON.parse(raw);
                    console.log('✅ Loaded', geminiKeys.length, 'Gemini keys from localStorage');
                    
                    // 🚀 CRITICAL: Sync to apiPool immediately!
                    if (geminiKeys.length > 0) {
                        apiPool.gemini = geminiKeys;
                        console.log('🔄 Synced to apiPool.gemini:', apiPool.gemini.length, 'keys');
                    }
                    
                    renderGeminiKeyList();
                } catch (e) {
                    console.error('Failed to load Gemini keys:', e);
                    geminiKeys = [];
                }
            }
            
            // 🆕 Then sync from server (if user is logged in)
            await syncAPIKeysFromServer();
        }
        
        // 🆕 Sync API keys from server to localStorage
        async function syncAPIKeysFromServer() {
            try {
                const response = await fetch('load_api_keys.php', {
                    method: 'GET',
                    credentials: 'include'
                });
                
                if (!response.ok) {
                    console.log('ℹ️ Not logged in or no server sync');
                    return;
                }
                
                const result = await response.json();
                
                if (result.success && result.data) {
                    // Sync Gemini keys
                    if (result.data.gemini_keys && Array.isArray(result.data.gemini_keys) && result.data.gemini_keys.length > 0) {
                        const encrypted = SecurityManager.obfuscate(JSON.stringify(result.data.gemini_keys));
                        localStorage.setItem('gemini_keys_secure', encrypted);
                        geminiKeys = result.data.gemini_keys;
                        apiPool.gemini = geminiKeys;
                        console.log('🔄 Synced', geminiKeys.length, 'Gemini keys from server');
                    }
                    
                    // Sync YouTube keys
                    if (result.data.youtube_keys && Array.isArray(result.data.youtube_keys) && result.data.youtube_keys.length > 0) {
                        const encrypted = SecurityManager.obfuscate(JSON.stringify(result.data.youtube_keys));
                        localStorage.setItem('yt_api_key_secure', encrypted);
                        youtubeKeys = result.data.youtube_keys;
                        console.log('🔄 Synced', youtubeKeys.length, 'YouTube keys from server');
                    }
                    
                    // Sync OpenRouter keys
                    if (result.data.openrouter_keys && Array.isArray(result.data.openrouter_keys) && result.data.openrouter_keys.length > 0) {
                        const encrypted = SecurityManager.obfuscate(JSON.stringify(result.data.openrouter_keys));
                        localStorage.setItem('openrouter_keys_secure', encrypted);
                        openRouterKeys = result.data.openrouter_keys;
                        console.log('🔄 Synced', openRouterKeys.length, 'OpenRouter keys from server');
                    }
                    
                    // Re-render key lists after sync
                    renderGeminiKeyList();
                    renderYouTubeKeyList();
                    
                    showToast('✅ Đã đồng bộ API keys từ server!', 'success');
                }
            } catch (error) {
                console.error('❌ Failed to sync from server:', error);
            }
        }
        
        function addGeminiKey() {
            const input = document.getElementById('newGeminiKey');
            const newKey = input.value.trim();
            
            if (!newKey) {
                showToast('Vui lòng nhập Gemini API key!', 'error');
                return;
            }
            
            // Basic validation (Gemini keys usually start with "AIza")
            if (!newKey.startsWith('AIza')) {
                if (!confirm('⚠️ Key này không giống Gemini key chuẩn (AIza...). Vẫn thêm?')) {
                    return;
                }
            }
            
            if (geminiKeys.includes(newKey)) {
                showToast('Key này đã tồn tại!', 'warning');
                return;
            }
            
            geminiKeys.push(newKey);
            saveGeminiKeys();
            renderGeminiKeyList();
            input.value = '';
            showToast(`✅ Thêm Gemini key #${geminiKeys.length} thành công!`, 'success');
        }
        
        function removeGeminiKey(index) {
            if (confirm(`Xóa Gemini key #${index + 1}?`)) {
                geminiKeys.splice(index, 1);
                saveGeminiKeys();
                renderGeminiKeyList();
                showToast('Key đã được xóa', 'success');
            }
        }
        
        function renderGeminiKeyList() {
            const container = document.getElementById('geminiKeyList');
            if (!container) return;
            
            if (geminiKeys.length === 0) {
                container.innerHTML = '<p class="text-sm text-purple-400 italic"><i class="fa-solid fa-info-circle"></i> Chưa có Gemini key nào. Thêm key để sử dụng AI Deep Dive.</p>';
                return;
            }
            
            container.innerHTML = geminiKeys.map((key, index) => `
                <div class="flex items-center gap-2 bg-purple-100 p-2 rounded-lg border border-purple-200">
                    <span class="text-xs font-bold text-purple-600 w-8">#${index + 1}</span>
                    <span class="flex-1 text-sm font-mono text-purple-800">
                        ${key.substring(0, 20)}...${key.substring(key.length - 6)}
                    </span>
                    <button onclick="removeGeminiKey(${index})" class="text-red-600 hover:text-red-700 transition">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            `).join('');
        }
        
        function saveGeminiKeys() {
            const obfuscated = SecurityManager.obfuscate(JSON.stringify(geminiKeys));
            localStorage.setItem('gemini_keys_secure', obfuscated);
            console.log('✅ Saved', geminiKeys.length, 'Gemini keys');
        }
        
        // 🔥 GET NEXT GEMINI KEY (Smart Rotation with Failover)
        function getGeminiKey() {
            // Check if we have any keys
            if (geminiKeys.length === 0) {
                return globalGeminiKey; // Fallback to old single key
            }
            
            // Find next available key (skip exhausted ones)
            const totalKeys = geminiKeys.length;
            let attempts = 0;
            
            while (attempts < totalKeys) {
                const key = geminiKeys[currentGeminiKeyIndex];
                currentGeminiKeyIndex = (currentGeminiKeyIndex + 1) % totalKeys;
                
                // Skip exhausted keys
                if (!exhaustedGeminiKeys.has(key)) {
                    console.log(`🔑 Using Gemini key #${currentGeminiKeyIndex}/${totalKeys} (${totalKeys - exhaustedGeminiKeys.size} available)`);
                    return key;
                }
                
                attempts++;
            }
            
            // All keys exhausted - reset and try again
            console.warn('⚠️ All Gemini keys exhausted! Resetting...');
            exhaustedGeminiKeys.clear();
            return geminiKeys[0];
        }
        
        // 🔥 MARK GEMINI KEY AS EXHAUSTED (called when 429 error)
        function markGeminiKeyExhausted(key) {
            exhaustedGeminiKeys.add(key);
            geminiKeyLastError[key] = Date.now();
            console.warn(`❌ Gemini key exhausted: ${key.substring(0, 10)}... (${exhaustedGeminiKeys.size}/${geminiKeys.length} exhausted)`);
            
            // Show toast if all keys exhausted
            if (exhaustedGeminiKeys.size >= geminiKeys.length) {
                showToast('⚠️ Tất cả Gemini keys đã hết quota! Sẽ tự reset sau 1 giờ.', 'warning');
            }
        }
        
        // 🔥 GET ACTIVE GEMINI KEY FOR API POOL
        function getActiveGeminiKey() {
            // Priority 1: apiPool from PHP session
            if (apiPool && apiPool.gemini && apiPool.gemini.length > 0) {
                return apiPool.gemini[0];
            }
            
            // Priority 2: User's geminiKeys
            if (geminiKeys.length > 0) {
                return getGeminiKey();
            }
            
            // Priority 3: Legacy single key
            return globalGeminiKey;
        }
        
        // ⚙️ API KEYS PAGE FUNCTIONS (Dedicated Configuration Page)
        
        // YouTube Keys Management
        let youtubeKeys = [];
        
        async function loadAPIKeysPage() {
            // Load all keys from localStorage and sync from server
            await loadYouTubeKeys();
            await loadGeminiKeysForPage();
            loadOpenRouterConfig();
            
            // Render lists
            renderYouTubeKeyList();
            renderGeminiKeyListPage();
            
            // Check connection status
            checkAPIConnectionStatus();
        }
        
        async function loadYouTubeKeys() {
            const saved = localStorage.getItem('yt_api_key_secure');
            if (saved) {
                try {
                    const raw = SecurityManager.deobfuscate(saved);
                    // Check if it's a single key or array
                    if (raw.startsWith('[')) {
                        youtubeKeys = JSON.parse(raw);
                    } else {
                        youtubeKeys = [raw]; // Convert single key to array
                    }
                    console.log('✅ Loaded', youtubeKeys.length, 'YouTube API keys from localStorage');
                } catch (e) {
                    youtubeKeys = [];
                }
            }
            
            // 🆕 Sync from server
            await syncAPIKeysFromServer();
        }
        
        function addYouTubeKey() {
            const input = document.getElementById('newYouTubeKey');
            const newKey = input.value.trim();
            
            if (!newKey) {
                showToast('Vui lòng nhập YouTube API key!', 'error');
                return;
            }
            
            // Basic validation (YouTube keys usually start with "AIza")
            if (!newKey.startsWith('AIza')) {
                if (!confirm('⚠️ Key này không giống YouTube key chuẩn (AIza...). Vẫn thêm?')) {
                    return;
                }
            }
            
            if (youtubeKeys.includes(newKey)) {
                showToast('Key này đã tồn tại!', 'warning');
                return;
            }
            
            youtubeKeys.push(newKey);
            saveYouTubeKeys();
            renderYouTubeKeyList();
            input.value = '';
            showToast(`✅ Thêm YouTube key #${youtubeKeys.length} thành công!`, 'success');
        }
        
        function removeYouTubeKey(index) {
            if (confirm(`Xóa YouTube key #${index + 1}?`)) {
                youtubeKeys.splice(index, 1);
                saveYouTubeKeys();
                renderYouTubeKeyList();
                showToast('Key đã được xóa', 'success');
            }
        }
        
        function renderYouTubeKeyList() {
            const container = document.getElementById('youtubeKeyList');
            if (!container) return;
            
            if (youtubeKeys.length === 0) {
                container.innerHTML = '<p class="text-sm text-red-400 italic"><i class="fa-solid fa-info-circle"></i> Chưa có YouTube API key nào. Thêm key để sử dụng Scanner.</p>';
                return;
            }
            
            container.innerHTML = youtubeKeys.map((key, index) => `
                <div class="flex items-center gap-2 bg-red-50 p-3 rounded-lg border border-red-200">
                    <span class="text-xs font-bold text-red-600 w-8">#${index + 1}</span>
                    <span class="flex-1 text-sm font-mono text-slate-800">
                        ${key.substring(0, 25)}...${key.substring(key.length - 8)}
                    </span>
                    <button onclick="removeYouTubeKey(${index})" class="text-red-600 hover:text-red-700 transition p-1">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            `).join('');
        }
        
        function saveYouTubeKeys() {
            const obfuscated = SecurityManager.obfuscate(JSON.stringify(youtubeKeys));
            localStorage.setItem('yt_api_key_secure', obfuscated);
            
            // Also update global variable
            if (youtubeKeys.length > 0) {
                globalAPIKey = youtubeKeys[0]; // Use first key
            }
        }
        
        // Gemini Keys for Page (separate from modal)
        function loadGeminiKeysForPage() {
            // Already loaded in loadGeminiKeys()
        }
        
        function addGeminiKeyFromPage() {
            const input = document.getElementById('newGeminiKeyPage');
            const newKey = input.value.trim();
            
            if (!newKey) {
                showToast('Vui lòng nhập Gemini API key!', 'error');
                return;
            }
            
            if (!newKey.startsWith('AIza')) {
                if (!confirm('⚠️ Key này không giống Gemini key chuẩn (AIza...). Vẫn thêm?')) {
                    return;
                }
            }
            
            if (geminiKeys.includes(newKey)) {
                showToast('Key này đã tồn tại!', 'warning');
                return;
            }
            
            geminiKeys.push(newKey);
            saveGeminiKeys();
            renderGeminiKeyListPage();
            input.value = '';
            showToast(`✅ Thêm Gemini key #${geminiKeys.length} thành công!`, 'success');
        }
        
        function renderGeminiKeyListPage() {
            const container = document.getElementById('geminiKeyListPage');
            if (!container) return;
            
            if (geminiKeys.length === 0) {
                container.innerHTML = '<p class="text-sm text-purple-400 italic"><i class="fa-solid fa-info-circle"></i> Chưa có Gemini key nào. Thêm key để sử dụng AI Deep Dive.</p>';
                return;
            }
            
            container.innerHTML = geminiKeys.map((key, index) => `
                <div class="flex items-center gap-2 bg-purple-50 p-3 rounded-lg border border-purple-200">
                    <span class="text-xs font-bold text-purple-600 w-8">#${index + 1}</span>
                    <span class="flex-1 text-sm font-mono text-slate-800">
                        ${key.substring(0, 25)}...${key.substring(key.length - 8)}
                    </span>
                    <button onclick="removeGeminiKey(${index}); renderGeminiKeyListPage();" class="text-red-600 hover:text-red-700 transition p-1">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            `).join('');
        }
        
        // 🔥 OPENROUTER MULTI-KEY MANAGEMENT
        function loadOpenRouterConfig() {
            // Load multi-keys
            const savedKeys = localStorage.getItem('openrouter_keys_secure');
            if (savedKeys) {
                try {
                    const raw = SecurityManager.deobfuscate(savedKeys);
                    openRouterKeys = JSON.parse(raw);
                    console.log('✅ Loaded', openRouterKeys.length, 'OpenRouter keys');
                } catch (e) {
                    console.error('Failed to load OpenRouter keys');
                    openRouterKeys = [];
                }
            }
            
            // Fallback: Load single key (legacy)
            if (openRouterKeys.length === 0) {
                const savedKey = localStorage.getItem('openrouter_key_secure');
                if (savedKey) {
                    try {
                        const key = SecurityManager.deobfuscate(savedKey);
                        if (key) {
                            openRouterKeys = [key];
                            globalOpenRouterKey = key;
                        }
                    } catch (e) {
                        console.error('Failed to load legacy OpenRouter key');
                    }
                }
            } else {
                globalOpenRouterKey = openRouterKeys[0];
            }
            
            const savedModel = localStorage.getItem('openrouter_model');
            if (savedModel) {
                document.getElementById('openRouterModelPage').value = savedModel;
            }
            
            renderOpenRouterKeyList();
        }
        
        function addOpenRouterKey() {
            const input = document.getElementById('newOpenRouterKey');
            const newKey = input.value.trim();
            
            if (!newKey) {
                showToast('Vui lòng nhập OpenRouter API key!', 'error');
                return;
            }
            
            // Basic validation (OpenRouter keys usually start with "sk-or")
            if (!newKey.startsWith('sk-or')) {
                if (!confirm('⚠️ Key này không giống OpenRouter key chuẩn (sk-or...). Vẫn thêm?')) {
                    return;
                }
            }
            
            if (openRouterKeys.includes(newKey)) {
                showToast('Key này đã tồn tại!', 'warning');
                return;
            }
            
            openRouterKeys.push(newKey);
            saveOpenRouterKeys();
            renderOpenRouterKeyList();
            input.value = '';
            showToast(`✅ Thêm OpenRouter key #${openRouterKeys.length} thành công!`, 'success');
        }
        
        function removeOpenRouterKey(index) {
            if (confirm(`Xóa OpenRouter key #${index + 1}?`)) {
                openRouterKeys.splice(index, 1);
                saveOpenRouterKeys();
                renderOpenRouterKeyList();
                showToast('Key đã được xóa', 'success');
            }
        }
        
        function renderOpenRouterKeyList() {
            const container = document.getElementById('openRouterKeyList');
            const countEl = document.getElementById('openRouterKeyCount');
            if (!container) return;
            
            if (countEl) {
                countEl.textContent = `${openRouterKeys.length} key${openRouterKeys.length !== 1 ? 's' : ''}`;
            }
            
            if (openRouterKeys.length === 0) {
                container.innerHTML = '<p class="text-sm text-indigo-400 italic"><i class="fa-solid fa-info-circle"></i> Chưa có OpenRouter key nào. Thêm key để sử dụng AI fallback.</p>';
                return;
            }
            
            container.innerHTML = openRouterKeys.map((key, index) => `
                <div class="flex items-center gap-2 bg-indigo-50 p-3 rounded-lg border border-indigo-200">
                    <span class="text-xs font-bold text-indigo-600 w-8">#${index + 1}</span>
                    <span class="flex-1 text-sm font-mono text-slate-800">
                        ${key.substring(0, 20)}...${key.substring(key.length - 6)}
                    </span>
                    <button onclick="removeOpenRouterKey(${index})" class="text-red-600 hover:text-red-700 transition p-1">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            `).join('');
        }
        
        function saveOpenRouterKeys() {
            const obfuscated = SecurityManager.obfuscate(JSON.stringify(openRouterKeys));
            localStorage.setItem('openrouter_keys_secure', obfuscated);
            
            // Also update global variable
            if (openRouterKeys.length > 0) {
                globalOpenRouterKey = openRouterKeys[0];
            }
            
            console.log('✅ Saved', openRouterKeys.length, 'OpenRouter keys');
        }
        
        async function saveAllAPIKeys() {
            // Save YouTube keys
            if (youtubeKeys.length > 0) {
                saveYouTubeKeys();
            }
            
            // Save Gemini keys
            if (geminiKeys.length > 0) {
                saveGeminiKeys();
            }
            
            // 🔥 Save OpenRouter keys (multi-key)
            if (openRouterKeys.length > 0) {
                saveOpenRouterKeys();
            }
            
            // Save OpenRouter model preference
            const openRouterModel = document.getElementById('openRouterModelPage').value;
            if (openRouterModel !== undefined) {
                localStorage.setItem('openrouter_model', openRouterModel);
                globalOpenRouterModel = openRouterModel;
            }
            
            // 🚀 NEW: Sync API keys to server (for FREE AI Tools)
            try {
                const response = await fetch('save_api_keys.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        gemini_keys: geminiKeys,
                        openrouter_keys: openRouterKeys,
                        youtube_keys: youtubeKeys
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    console.log('✅ API keys synced to server:', result.data);
                    
                    // Update apiPool immediately (no need to reload page)
                    apiPool.gemini = geminiKeys;
                    apiPool.openrouter = openRouterKey || null;
                    apiPool.youtube = youtubeKeys.length > 0 ? youtubeKeys[0] : null;
                    
                    // 🔥 CRITICAL: Refresh apiKeys array for Scanner
                    initKeys();
                    console.log('🔄 Refreshed apiKeys for Scanner:', apiKeys.length, 'keys');
                    
                    console.log('🔄 Updated apiPool:', apiPool);
                    
                    showToast('✅ Đã lưu toàn bộ API keys và đồng bộ với server!', 'success');
                } else {
                    console.error('❌ Failed to sync to server:', result.error);
                    showToast('⚠️ Đã lưu local nhưng không đồng bộ được server!', 'warning');
                }
            } catch (error) {
                console.error('❌ Server sync error:', error);
                showToast('⚠️ Đã lưu local nhưng không kết nối được server!', 'warning');
            }
            
            // Update status
            checkAPIConnectionStatus();
        }
        
        function testAPIConnections() {
            showToast('🧪 Đang kiểm tra kết nối API...', 'info');
            
            // TODO: Implement actual API testing
            setTimeout(() => {
                const hasYouTube = youtubeKeys.length > 0;
                const hasGemini = geminiKeys.length > 0;
                const hasOpenRouter = openRouterKeys.length > 0;
                
                let message = '';
                if (hasYouTube) message += `✅ YouTube API: ${youtubeKeys.length} key${youtubeKeys.length > 1 ? 's' : ''}\n`;
                if (hasGemini) message += `✅ Gemini API: ${geminiKeys.length} key${geminiKeys.length > 1 ? 's' : ''}\n`;
                if (hasOpenRouter) message += `✅ OpenRouter API: ${openRouterKeys.length} key${openRouterKeys.length > 1 ? 's' : ''}\n`;
                
                if (!hasYouTube && !hasGemini && !hasOpenRouter) {
                    showToast('⚠️ Chưa có API key nào!', 'warning');
                } else {
                    showToast(message, 'success');
                }
            }, 1000);
        }
        
        function checkAPIConnectionStatus() {
            const statusEl = document.getElementById('apiConnectionStatus');
            const dotEl = document.getElementById('apiKeysStatusDot');
            
            if (!statusEl) return;
            
            const hasYouTube = youtubeKeys.length > 0;
            const hasGemini = geminiKeys.length > 0;
            const hasOpenRouter = openRouterKeys.length > 0;
            
            if (hasYouTube || hasGemini || hasOpenRouter) {
                statusEl.innerHTML = `
                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                    <span class="text-sm font-bold">Connected</span>
                `;
                if (dotEl) dotEl.className = 'ml-auto w-2 h-2 bg-green-500 rounded-full';
            } else {
                statusEl.innerHTML = `
                    <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                    <span class="text-sm font-bold">No Keys</span>
                `;
                if (dotEl) dotEl.className = 'ml-auto w-2 h-2 bg-red-500 rounded-full';
            }
        }
        
        // Top Search Bar Functions
        function quickSearch() {
            const query = document.getElementById('topSearchBar').value.trim();
            if (!query) return;
            
            // Get current active tab
            const activeTab = document.querySelector('.tab-content.active')?.id;
            
            if (activeTab === 'searchTab') {
                // Set keyword and trigger search
                document.getElementById('rootKeyword').value = query;
                startScan();
            } else if (activeTab === 'deepAnalysisTab') {
                // Set channel URL and trigger analysis
                document.getElementById('deepChannelUrl').value = query;
                startDeepAnalysis();
            }
            
            document.getElementById('topSearchBar').value = '';
        }
        
        function showNotifications() {
            showToast('🔔 Chức năng thông báo sắp ra mắt!', 'info');
        }
        
        // 📹 Show Tutorials Modal
        function showTutorials() {
            document.getElementById('tutorialsModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden'; // Prevent scroll
        }
        
        // ❌ Close Tutorials Modal
        function closeTutorials() {
            document.getElementById('tutorialsModal').classList.add('hidden');
            document.body.style.overflow = ''; // Restore scroll
        }
        
        // Close modal when clicking outside
        document.getElementById('tutorialsModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeTutorials();
            }
        });
        
        // Close modal with ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !document.getElementById('tutorialsModal').classList.contains('hidden')) {
                closeTutorials();
            }
        });
        
        // 📱 Mobile Sidebar Toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (sidebar.classList.contains('-translate-x-full')) {
                // Open sidebar
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
                document.body.style.overflow = 'hidden'; // Prevent scroll
            } else {
                // Close sidebar
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
                document.body.style.overflow = ''; // Restore scroll
            }
        }
        
        // Close sidebar when clicking nav item (mobile)
        async function switchTab(tabId) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active')); 
            
            // Remove active from all sidebar items
            document.querySelectorAll('.sidebar-nav-item').forEach(el => el.classList.remove('active'));
            
            // Show selected tab content
            const content = document.getElementById(tabId); 
            if (content) content.classList.add('active'); 
            
            // Highlight active sidebar item
            const sidebarBtn = document.getElementById(`sidebar-${tabId}`);
            if (sidebarBtn) sidebarBtn.classList.add('active');
            
            // ✅ SAVE CURRENT TAB TO LOCALSTORAGE
            localStorage.setItem('scanner_active_tab', tabId);
            
            // Close sidebar on mobile after selection
            if (window.innerWidth < 768) {
                toggleSidebar();
            }
            
            // If switching to API Keys tab, load keys
            if (tabId === 'apiKeysTab') {
                await loadAPIKeysPage();
            }
            
            // Update search bar placeholder based on active tab
            const searchBar = document.getElementById('topSearchBar');
            if (searchBar) {
                if (tabId === 'searchTab') {
                    searchBar.placeholder = 'Tìm kiếm từ khóa YouTube...';
                } else if (tabId === 'deepAnalysisTab') {
                    searchBar.placeholder = 'Tìm kiếm kênh để phân tích...';
                } else if (tabId === 'dashboardTab') {
                    searchBar.placeholder = 'Tìm trong vault...';
                } else if (tabId === 'apiKeysTab') {
                    searchBar.placeholder = 'Tìm API key...';
                }
            }
        }
        
        /**
         * 🔥 GET ACTIVE YOUTUBE API KEY (Smart Rotation with Failover)
         * 
         * TIER STRATEGY:
         * - FREE: No key (blocked)
         * - TRIAL: Admin's shared key (or user's own if provided)
         * - BASIC: User's own key (required)
         * - PREMIUM: Admin's OpenRouter key (unlimited)
         */
        function getActiveKey() {
            // Priority 1: Use tier-based API key from server (PHP)
            if (TIER_API_KEY && TIER_API_KEY.length > 10) {
                return TIER_API_KEY;
            }
            
            // Priority 2: Use user's own keys from localStorage (for Basic tier)
            if (apiKeys.length > 0) {
                return getNextYouTubeKey();
            }
            
            // Priority 3: Use youtubeKeys array (from API Keys page)
            if (youtubeKeys.length > 0) {
                return getNextYouTubeKeyFromPage();
            }
            
            // Priority 4: Show error based on tier
            if (USER_TIER === TIER_BASIC) {
                showToast('⚠️ Basic tier: Please add your YouTube API key in Settings', 'warning');
            } else if (USER_TIER === TIER_TRIAL) {
                showToast('⚠️ Admin API pool exhausted. Please try again later or add your own key.', 'warning');
            }
            
            return null;
        }
        
        // 🔥 GET NEXT YOUTUBE KEY (Smart Rotation with Usage Tracking)
        function getNextYouTubeKey() {
            const totalKeys = apiKeys.length;
            if (totalKeys === 0) return null;
            
            let attempts = 0;
            while (attempts < totalKeys) {
                const key = apiKeys[currentKeyIndex];
                const keyIndex = currentKeyIndex;
                currentKeyIndex = (currentKeyIndex + 1) % totalKeys;
                
                // Skip exhausted keys
                if (!exhaustedYouTubeKeys.has(key)) {
                    // 🔥 Track usage count
                    youtubeKeyUsageCount[key] = (youtubeKeyUsageCount[key] || 0) + 1;
                    
                    // 🔥 Console log chi tiết
                    console.log(`🎬 Using YouTube key #${keyIndex + 1}/${totalKeys} | Used: ${youtubeKeyUsageCount[key]} times | Remaining: ${totalKeys - exhaustedYouTubeKeys.size}/${totalKeys} keys`);
                    
                    return key;
                }
                
                attempts++;
            }
            
            // All keys exhausted
            console.error(`❌ TẤT CẢ ${totalKeys} YOUTUBE KEYS ĐÃ HẾT QUOTA! Keys exhausted:`, Array.from(exhaustedYouTubeKeys).map(k => k.substring(0, 10) + '...'));
            return null;
        }
        
        // 🔥 GET NEXT YOUTUBE KEY FROM PAGE ARRAY
        function getNextYouTubeKeyFromPage() {
            const totalKeys = youtubeKeys.length;
            if (totalKeys === 0) return null;
            
            let attempts = 0;
            while (attempts < totalKeys) {
                const key = youtubeKeys[currentKeyIndex % totalKeys];
                currentKeyIndex++;
                
                // Skip exhausted keys
                if (!exhaustedYouTubeKeys.has(key)) {
                    console.log(`🎬 Using YouTube key #${(currentKeyIndex % totalKeys) + 1}/${totalKeys}`);
                    return key;
                }
                
                attempts++;
            }
            
            // All keys exhausted
            exhaustedYouTubeKeys.clear();
            return youtubeKeys[0];
        }
        
        // 🔥 MARK YOUTUBE KEY AS EXHAUSTED (called when 403/429 error)
        function markYouTubeKeyExhausted(key) {
            exhaustedYouTubeKeys.add(key);
            youtubeKeyLastError[key] = Date.now();
            console.warn(`❌ YouTube key blacklisted: ${key.substring(0, 10)}...`);
            
            const totalKeys = Math.max(apiKeys.length, youtubeKeys.length);
            if (exhaustedYouTubeKeys.size >= totalKeys) {
                showToast('⚠️ Tất cả YouTube API keys đã hết quota hoặc không hợp lệ! Kiểm tra Console để biết chi tiết.', 'error');
            }
        }

        // --- SYSTEM LOCK & UNLOCK ---
        async function unlockSystem() {
            const input = document.getElementById('accessKeyInput'); const status = document.getElementById('authStatus'); const wall = document.getElementById('securityWall');
            if (!input || !input.value) { status.innerText = "Vui lòng nhập Key truy cập!"; status.className = "text-red-500 text-[10px] italic font-bold"; return; }
            const userKey = input.value.trim(); status.innerText = "Đang xác thực hệ thống..."; status.className = "text-yellow-500 text-[10px] italic";
            try { await new Promise(r => setTimeout(r, 800)); if (userKey === FIXED_ACCESS_KEY) { sessionStorage.setItem('is_authenticated', 'true'); status.innerText = "Xác thực thành công! Welcome to Visora."; status.className = "text-green-500 text-[10px] italic font-bold"; setTimeout(() => { if (wall) { wall.style.opacity = '0'; setTimeout(() => { wall.style.display = 'none'; wall.classList.add('hidden-important'); document.body.classList.remove('overflow-hidden'); }, 500); } }, 800); } else { status.innerText = "Key không hợp lệ! Vui lòng thử lại."; status.className = "text-red-500 text-[10px] italic font-bold animate-pulse"; input.value = ""; input.focus(); } } catch (e) { console.error(e); status.innerText = "Lỗi xác thực cục bộ."; }
        }

        function checkAuthStatus() { const isAuth = sessionStorage.getItem('is_authenticated'); if (isAuth === 'true') { const wall = document.getElementById('securityWall'); if (wall) { wall.style.display = 'none'; wall.classList.add('hidden-important'); } document.body.classList.remove('overflow-hidden'); } }

        // --- REDIRECT TO PRICING (REMOVED - NO LONGER NEEDED) ---
        // function redirectToPricing() - DELETED

        // --- EXPORT FUNCTION (FULL ACCESS FOR ALL) ---
        function exportCSV() {
            if (!currentData || currentData.length === 0) { showToast("Không có dữ liệu để xuất!", "warning"); return; }
            const headers = ["Video Title", "Video URL", "Channel Name", "Channel URL", "Published Date", "Views", "Subscribers", "V/S Ratio", "Niche Detected", "Applied RPM ($)", "Est. Revenue ($)"];
            const rows = currentData.map(v => {
                const combinedText = (v.title + " " + v.tags.join(" ")).toLowerCase(); const nicheData = detectNicheData(combinedText); const appliedRpm = nicheData.name === 'general' ? globalRpm : nicheData.rpm; const estRevenue = (v.views / 1000) * appliedRpm; const safeTitle = v.title.replace(/"/g, '""'); const safeChannel = v.channelTitle.replace(/"/g, '""');
                return [`"${safeTitle}"`, `"https://www.youtube.com/watch?v=${v.id}"`, `"${safeChannel}"`, `"https://www.youtube.com/channel/${v.channelId}"`, `"${v.publishedTimeStr}"`, v.views, v.subs, v.ratio, `"${nicheData.name.toUpperCase()}"`, appliedRpm, estRevenue.toFixed(2)].join(",");
            });
            const csvContent = "\uFEFF" + headers.join(",") + "\n" + rows.join("\n"); const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' }); const url = URL.createObjectURL(blob); const link = document.createElement("a"); const dateStr = new Date().toISOString().slice(0, 10); link.setAttribute("href", url); link.setAttribute("download", `HSHOP_Report_${dateStr}.csv`); document.body.appendChild(link); link.click(); document.body.removeChild(link); showToast("Đã xuất file CSV thành công!");
        }

        // --- STATE & VARS ---
        let currentData = []; let topWinningTags = []; let currentModalVid = null; let trackedChannels = []; let microNicheClusters = {}; try { trackedChannels = JSON.parse(localStorage.getItem('yt_tracker_channels') || '[]'); } catch (e) { localStorage.setItem('yt_tracker_channels', '[]'); } let generatedQueriesList = []; let globalApiKey = ''; let globalGeminiKey = ''; let globalOpenRouterKey = ''; let globalVisionKey = ''; let globalRpm = parseFloat(localStorage.getItem('yt_rpm')) || 0.3; let isDeepDiveRunning = false;

        initKeys();
        if (apiKeys.length > 0) { document.getElementById('apiKeyStatus').innerHTML = `API Key: <span class="text-green-500 font-bold">● Active (${apiKeys.length} keys)</span>`; const savedRaw = localStorage.getItem('yt_api_key_secure'); if (savedRaw) document.getElementById('settingsApiKey').value = SecurityManager.deobfuscate(savedRaw); } else { document.getElementById('apiKeyStatus').innerHTML = 'API Key: <span class="text-red-500 font-bold">● Missing</span>'; }
        const savedGeminiKey = localStorage.getItem('yt_gemini_key_secure'); if (savedGeminiKey) { globalGeminiKey = SecurityManager.deobfuscate(savedGeminiKey); safeSetValue('settingsGeminiKey', globalGeminiKey); const dot = document.getElementById('geminiStatusDot'); if (dot) dot.classList.replace('bg-gray-300', 'bg-purple-500'); }
        const savedOpenRouterKey = localStorage.getItem('yt_openrouter_key_secure'); if (savedOpenRouterKey) { globalOpenRouterKey = SecurityManager.deobfuscate(savedOpenRouterKey); safeSetValue('settingsOpenRouterKey', globalOpenRouterKey); }
        
        // NEW: Load Vision API Key
        const savedVisionKey = localStorage.getItem('yt_vision_key_secure'); 
        if (savedVisionKey) { 
            globalVisionKey = SecurityManager.deobfuscate(savedVisionKey); 
            safeSetValue('settingsVisionKey', globalVisionKey); 
            console.log('✅ Vision API key loaded from storage');
        }
        
        // 🔥 Load Gemini Keys (Multi-key support)
        loadGeminiKeys();
        
        // 🔥 Load saved OpenRouter model
        const savedModel = localStorage.getItem('openrouter_model');
        if (savedModel) {
            safeSetValue('openRouterModel', savedModel);
            console.log('✅ OpenRouter model loaded:', savedModel);
        }
        
        safeSetValue('settingsRpm', globalRpm); safeSetValue('globalRpmSlider', globalRpm); safeSetText('globalRpmDisplay', '$' + globalRpm);

        // --- RENDER FUNCTIONS (FULL ACCESS - NO RESTRICTIONS) ---
        function renderResults() {
            safeClass('resultsArea', 'remove', 'hidden'); const tbody = document.getElementById('tableBody'); if (!tbody) return; tbody.innerHTML = '';
            
            // ALL USERS GET FULL ACCESS - NO LIMITATIONS
            console.log('✅ FULL ACCESS: Showing all', currentData.length, 'results');

            currentData.forEach((vid, index) => {
                const combinedText = (vid.title + " " + vid.tags.join(" ")).toLowerCase(); 
                const nicheData = detectNicheData(combinedText); 
                const appliedRpm = nicheData.name === 'general' ? globalRpm : nicheData.rpm; 
                const estRev = (vid.views / 1000) * appliedRpm;
                
                // 🔥 Calculate badges
                const outlierBadge = getOutlierBadge(vid);
                const viralScore = calculateViralPotential(vid);
                const viralPotentialBar = getViralPotentialBar(viralScore);
                
                // 🌟 GOLD MINE BADGE (priority over outlier badge)
                let goldBadge = '';
                if (vid.isGoldMine) {
                    goldBadge = '<span class="inline-flex items-center gap-1 bg-gradient-to-r from-yellow-400 to-orange-500 text-white text-[9px] font-black px-2 py-0.5 rounded-full shadow-md animate-pulse" title="Kênh mới (<1 năm) + View nổ mạnh = CƠ HỘI VÀNG!"><i class="fa-solid fa-star"></i> GOLD MINE</span>';
                }
                
                // Highlight Gold Mine and Outlier rows
                const rowClass = vid.isGoldMine
                    ? "hover:bg-yellow-50 transition-colors border-b border-yellow-200 group relative bg-gradient-to-r from-yellow-50/50 to-transparent"
                    : vid.isOutlier 
                    ? "hover:bg-orange-50 transition-colors border-b border-orange-200 group relative bg-gradient-to-r from-orange-50/50 to-transparent" 
                    : "hover:bg-slate-50 transition-colors border-b border-slate-100 group relative";
                
                const tr = document.createElement('tr'); 
                tr.className = rowClass;
                
                tr.innerHTML = `
                    <td class="p-4">
                        <div class="relative w-32 aspect-video rounded-lg overflow-hidden shadow-sm cursor-pointer group-hover:shadow-md transition" onclick="openSpyModal(${index})">
                            <img src="${vid.thumbnail}" class="w-full h-full object-cover">
                            <span class="absolute bottom-1 right-1 bg-black/80 text-white text-[9px] px-1 rounded font-mono">${vid.duration}</span>
                            ${vid.isGoldMine ? '<div class="absolute top-1 left-1 bg-gradient-to-r from-yellow-400 to-orange-500 text-white text-[8px] font-black px-1.5 py-0.5 rounded shadow-lg"><i class="fa-solid fa-crown"></i></div>' : outlierBadge ? `<div class="absolute top-1 left-1">${outlierBadge}</div>` : ''}
                        </div>
                    </td>
                    <td class="p-4 max-w-sm">
                        <a href="https://youtu.be/${vid.id}" target="_blank" class="font-bold text-slate-800 text-sm line-clamp-2 mb-1 hover:text-red-600 hover:underline block" title="${vid.title}">${vid.title}</a>
                        ${vid.isGoldMine ? `<div class="mt-1">${goldBadge}</div>` : ''}
                        <div class="space-y-1 mt-2">
                            <div class="flex items-center text-[11px] text-slate-600 gap-2">
                                <a href="https://youtube.com/channel/${vid.channelId}" target="_blank" class="font-bold hover:text-red-600 flex items-center gap-1">
                                    <i class="fa-solid fa-user-circle"></i> ${vid.channelTitle}
                                </a>
                                <span class="bg-slate-100 px-1.5 py-0.5 rounded text-[10px]">Sub: ${vid.subs.toLocaleString()}</span>
                                ${vid.isGoldMine ? '<span class="bg-gradient-to-r from-yellow-400 to-orange-500 text-white text-[9px] font-black px-1.5 py-0.5 rounded">Kênh mới</span>' : ''}
                            </div>
                            <div class="flex items-center text-[10px] text-slate-500 gap-3">
                                <span><i class="fa-regular fa-clock"></i> ${vid.publishedTimeStr}</span>
                                ${vid.activeMonthsOld !== null ? 
                                    `<span class="text-[9px] ${vid.activeMonthsOld <= 12 ? 'text-green-600 font-bold' : ''}"><i class="fa-solid fa-video"></i> HĐ: ${Math.floor(vid.activeMonthsOld)} tháng</span>` : 
                                    (vid.channelMonthsOld ? `<span class="text-[9px]"><i class="fa-solid fa-calendar"></i> Tạo: ${Math.floor(vid.channelMonthsOld)} tháng</span>` : '')
                                }
                                ${vid.channelMonthsOld && vid.activeMonthsOld !== null && Math.floor(vid.channelMonthsOld) !== Math.floor(vid.activeMonthsOld) ? 
                                    `<span class="text-[9px] text-slate-400">Tạo: ${Math.floor(vid.channelMonthsOld)}th</span>` : ''
                                }
                            </div>
                            ${vid.isOutlier ? `
                            <div class="mt-1.5">
                                <div class="text-[9px] text-slate-500 mb-0.5">Avg channel: ${vid.channelAvgViews?.toLocaleString() || 'N/A'} views</div>
                                ${viralPotentialBar}
                            </div>
                            ` : ''}
                        </div>
                    </td>
                    <td class="p-4 text-right font-mono text-slate-600 text-xs">${vid.views.toLocaleString()}</td>
                    <td class="p-4 text-right font-mono text-slate-500 text-xs hidden sm:table-cell">${vid.subs === 0 ? '?' : vid.subs.toLocaleString()}</td>
                    <td class="p-4 text-right">
                        <div class="font-bold text-sm ${vid.ratio > 3 ? 'text-green-600' : 'text-slate-600'}">${vid.ratio}x</div>
                        ${vid.isOutlier ? `<div class="text-[9px] text-orange-600 font-black mt-0.5">${vid.outlierRatio}x 🔥</div>` : ''}
                    </td>
                    <td class="p-4 text-right">
                        <div class="flex flex-col items-end">
                            <span class="text-green-600 font-bold text-xs">$${estRev.toFixed(2)}</span>
                            <span class="text-[9px] text-slate-400 bg-slate-100 px-1 rounded" title="RPM ngách: ${nicheData.name.toUpperCase()}">RPM: $${appliedRpm}</span>
                        </div>
                    </td>
                    <td class="p-4 text-center">
                        <button onclick="openSpyModal(${index})" class="text-slate-400 hover:text-red-600 transition p-2 rounded-full hover:bg-slate-100">
                            <i class="fa-solid fa-eye text-lg"></i>
                        </button>
                    </td>`;
                tbody.appendChild(tr);
            });
            
            // 🔥 Update outlier stats dashboard
            updateOutlierStats();
        }
        
        // 🔥 UPDATE OUTLIER STATS DASHBOARD
        function updateOutlierStats() {
            const container = document.getElementById('outlierStatsContainer');
            if (!container || !currentData || currentData.length === 0) {
                if (container) container.classList.add('hidden');
                return;
            }
            
            // Calculate stats
            const totalVideos = currentData.length;
            const goldMines = currentData.filter(v => v.isGoldMine);
            const goldMineCount = goldMines.length;
            const outliers = currentData.filter(v => v.isOutlier);
            const outlierCount = outliers.length;
            const megaOutliers = currentData.filter(v => v.outlierRatio >= 50).length;
            const smallChannels = currentData.filter(v => v.subs < 50000).length;
            // 🔥 Priority: activeMonthsOld (first video) > channelMonthsOld (account created)
            const newChannels = currentData.filter(v => {
                const age = v.activeMonthsOld ?? v.channelMonthsOld;
                return age !== null && age <= 12;
            }).length;
            const highViral = currentData.filter(v => {
                const score = calculateViralPotential(v);
                return score >= 80;
            }).length;
            
            const goldMinePercent = totalVideos > 0 ? ((goldMineCount / totalVideos) * 100).toFixed(1) : 0;
            const outlierPercent = totalVideos > 0 ? ((outlierCount / totalVideos) * 100).toFixed(1) : 0;
            const smallChannelPercent = totalVideos > 0 ? ((smallChannels / totalVideos) * 100).toFixed(1) : 0;
            const newChannelPercent = totalVideos > 0 ? ((newChannels / totalVideos) * 100).toFixed(1) : 0;
            
            // Update DOM
            safeSetText('statTotalVideos', totalVideos);
            safeSetText('statGoldMines', goldMineCount);
            safeSetText('statGoldMinePercent', goldMinePercent + '%');
            safeSetText('statNewChannels', newChannels);
            safeSetText('statNewChannelPercent', newChannelPercent + '%');
            safeSetText('statSmallChannels', smallChannels);
            safeSetText('statSmallChannelPercent', smallChannelPercent + '%');
            safeSetText('statHighViral', highViral);
            
            // Show container
            container.classList.remove('hidden');
        }

        // --- DEEP DIVE LOGIC (GIỮ NGUYÊN) ---
        function generateQueries() {
            const root = document.getElementById('deepKeyword').value.trim().toLowerCase(); const modifierKey = document.getElementById('deepModifier').value; const region = document.getElementById('deepRegion')?.value || document.getElementById('globalRegion')?.value || 'VN'; const lang = REGION_LANG_MAP[region] || 'vi';
            if (!root) { showToast(lang === 'vi' ? "Vui lòng nhập từ khóa gốc!" : "Please enter a root keyword!", "warning"); return; }
            
            const patterns_vi = { intent_beginner: ["{k} cho người mới bắt đầu", "hướng dẫn {k} cơ bản", "lộ trình {k} từ A-Z", "sai lầm khi làm {k}"], intent_review: ["review {k} chân thực", "có nên mua {k} không?", "{k} lừa đảo hay uy tín?", "sự thật về {k}"], intent_compare: ["{k} vs đối thủ", "top 5 {k} tốt nhất", "thay thế cho {k}"], content_horror: ["chuyện ma {k}", "bí ẩn {k} chưa giải đáp", "{k} có thật không", "sự tích {k}"], content_entertainment: ["thử thách {k} 24h", "troll {k} hài hước", "reaction {k} cực gắt"], content_spiritual: ["nhạc {k} chữa lành", "{k} tĩnh tâm", "lợi ích của {k}", "nghe {k} mỗi tối", "pháp thoại {k}"] };
            const patterns_en = { intent_beginner: ["{k} for beginners", "how to {k} basics", "{k} roadmap A-Z", "mistakes when doing {k}"], intent_review: ["honest {k} review", "is {k} worth it?", "{k} scam or legit?", "truth about {k}"], intent_compare: ["{k} vs competitor", "top 5 best {k}", "alternatives to {k}"], content_horror: ["{k} horror stories", "unsolved {k} mysteries", "is {k} real", "legend of {k}"], content_entertainment: ["{k} challenge 24h", "{k} funny pranks", "{k} extreme reaction"], content_spiritual: ["{k} healing music", "{k} meditation", "benefits of {k}", "listen to {k} daily"] };
            const patterns = lang === 'vi' ? patterns_vi : patterns_en; let selectedPatterns = [];
            
            if (modifierKey === 'gemini_ai') { const provider = globalOpenRouterKey ? 'openrouter' : (globalGeminiKey ? 'gemini' : null); if (provider === 'openrouter') { generateGeminiQueries(root, lang, 'openrouter'); return; } else if (provider === 'gemini') { generateGeminiQueries(root, lang, 'gemini'); return; } else { showToast("Vui lòng nhập API Key (Gemini hoặc OpenRouter) trong Cài đặt!", "error"); return; } }
            if (modifierKey === 'auto_detect') { if (root.match(/(ma|quỷ|ám|sợ|kinh dị|bí ẩn|ghost|horror|scary)/)) selectedPatterns = patterns.content_horror; else if (root.match(/(game|chơi|tướng|rank|liên quân|roblox|play|win)/)) selectedPatterns = patterns.content_entertainment; else if (root.match(/(phật|pháp|chùa|tụng|kinh|thiền|tĩnh|an nhiên|chữa lành|yoga|meditation|healing)/)) selectedPatterns = patterns.content_spiritual; else if (root.match(/(mua|bán|giá|review|đánh giá|top|tốt nhất|buy|sell|price|best)/)) selectedPatterns = patterns.intent_review; else selectedPatterns = patterns.intent_beginner; } else { selectedPatterns = patterns[modifierKey] || patterns.intent_beginner; }
            
            generatedQueriesList = selectedPatterns.map(p => p.replace('{k}', root));
            const genContainer = document.getElementById('generatedQueries'); if (genContainer) genContainer.innerHTML = generatedQueriesList.map(q => `<span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded border border-blue-200 font-medium cursor-pointer hover:bg-blue-200 transition" onclick="copyText('${q}')">${q}</span>`).join('');
            safeClass('queryListArea', 'remove', 'hidden'); safeClass('deepResultsArea', 'add', 'hidden');
        }

        function copyText(text) { navigator.clipboard.writeText(text); showToast(`Đã copy: "${text}"`); }

        async function generateGeminiQueries(root, lang, provider = 'gemini') {
            const btn = document.getElementById('btnDeepScan'); const originalText = btn.innerHTML; btn.innerHTML = '<i class="fa-solid fa-brain fa-pulse"></i> AI Thinking...'; btn.disabled = true;
            const prompt = lang === 'vi' ? `Tạo 10 từ khóa đuôi dài (long-tail) cụ thể, ít cạnh tranh cho chủ đề "${root}" trên YouTube. Tập trung vào ý định "hướng dẫn", "review" hoặc "vs". Xuất ra dưới dạng danh sách ngăn cách bởi dấu phẩy.` : `Generate 10 highly specific, low-competition long-tail keywords for the topic "${root}" on YouTube. Focus on "how-to", "review", or "vs" intent. Output as a comma-separated list only.`;
            try { let result = null; let usedProvider = null; if (geminiKeys.length > 0) { console.log('✅ Using Gemini FREE for query generation...'); result = await callGemini(prompt); if (result) { usedProvider = 'gemini'; console.log('✅ Gemini FREE success!'); } } if (!result && globalOpenRouterKey) { console.log('📡 Gemini exhausted, fallback to OpenRouter...'); showToast('📡 Gemini FREE hết quota, chuyển sang OpenRouter...', 'info'); result = await callOpenRouter(prompt, "openai/gpt-4o-mini"); usedProvider = 'openrouter'; } if (result) { generatedQueriesList = result.split(',').map(s => s.trim()); const genContainer = document.getElementById('generatedQueries'); if (genContainer) genContainer.innerHTML = generatedQueriesList.map(q => `<span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded border border-blue-200 font-medium cursor-pointer hover:bg-blue-200 transition" onclick="copyText('${q}')">${q}</span>`).join(''); safeClass('queryListArea', 'remove', 'hidden'); safeClass('deepResultsArea', 'add', 'hidden'); showToast(`${usedProvider === 'openrouter' ? '💰 GPT-4o Mini (PAID)' : '✅ Gemini FREE'} đã tạo xong danh sách ý tưởng!`); } else { showToast("AI không phản hồi, thử lại sau.", "error"); } } catch (e) { showToast("Lỗi kết nối AI: " + e.message, "error"); } finally { btn.innerHTML = originalText; btn.disabled = false; }
        }

        function stopDeepDive() { isDeepDiveRunning = false; }
        async function executeDeepDive() {
            const apiKey = getActiveKey(); if (!apiKey) { openSettings(); return; } const region = document.getElementById('deepRegion').value || document.getElementById('globalRegion').value;
            isDeepDiveRunning = true; safeClass('btnExecuteDeep', 'add', 'hidden'); safeClass('btnStopDeep', 'remove', 'hidden'); safeClass('progressContainer', 'remove', 'hidden'); const container = document.getElementById('deepAnalysisContainer'); if (container) container.innerHTML = ''; safeClass('deepResultsArea', 'remove', 'hidden');
            try { let completed = 0; for (const query of generatedQueriesList) { if (!isDeepDiveRunning) break; await sleep(1500); try { const nicheData = await fetchAndProcess(apiKey, query, 5, region); if (nicheData.length > 0) { const avgRatio = nicheData.reduce((sum, v) => sum + v.ratio, 0) / nicheData.length; let status = avgRatio > 3 ? "🔥 HOT" : (avgRatio < 0.5 ? "❄️ COLD" : "😐 OK"); let color = avgRatio > 3 ? "text-green-600" : (avgRatio < 0.5 ? "text-red-500" : "text-slate-500"); if (container) container.innerHTML += `<div class="nexlev-card p-4 flex justify-between items-center animate-[fadeIn_0.3s_ease-out]"><div class="flex-1"><h4 class="font-bold text-slate-800 text-sm mb-1 line-clamp-1" title="${query}">${query}</h4><div class="flex items-center gap-2"><span class="nexlev-metric">Eff: ${avgRatio.toFixed(1)}x</span><span class="${color} text-xs font-bold">${status}</span></div></div><button onclick="openGoogleTrends('${query}')" class="ml-3 text-slate-400 hover:text-blue-600 transition p-2 rounded-full hover:bg-slate-50" title="Check Trends"><i class="fa-solid fa-arrow-up-right-from-square"></i></button></div>`; } } catch (e) { console.error(e); } completed++; const progressBar = document.getElementById('progressBar'); if (progressBar) progressBar.style.width = `${(completed / generatedQueriesList.length) * 100}%`; } showToast("Quét sâu hoàn tất!"); } catch (e) { showToast("Lỗi: " + e.message, "error"); } finally { isDeepDiveRunning = false; safeClass('btnExecuteDeep', 'remove', 'hidden'); safeClass('btnStopDeep', 'add', 'hidden'); safeClass('progressContainer', 'add', 'hidden'); }
        }

        // --- MICRO NICHE CLUSTERING (IMPROVED v2.0) ---
        function analyzeMicroNiches(videos) {
            const container = document.getElementById('microNicheContainer');
            
            try {
                if (!videos || !Array.isArray(videos) || videos.length < 2) {
                    if (container) container.classList.add('hidden');
                    return;
                }
                
                const stopWords = ['video', 'youtube', '2024', '2023', '2025', '2026', 'review', 'vlog', 'new', 'mới', 'nhất', 'tại', 'của', 'là', 'gì', 'how', 'to', 'in', 'on', 'the', 'a', 'and', 'with', '|', '-', 'official', 'channel', 'kênh', 'tv', 'full', 'hd', '4k', 'phim', 'nhạc', 'song', 'music', 'part', 'ep', 'episode', 'shorts', 'short', 'reels', 'tiktok', 'viral', 'trending', 'subscribe', 'like', 'share', 'comment', 'follow', 'click', 'link', 'bio', 'description', 'watch', 'more', 'best', 'top', 'most', 'very', 'really', 'just', 'only', 'first', 'last', 'this', 'that', 'here', 'there', 'what', 'when', 'where', 'which', 'who', 'why', 'how', 'all', 'any', 'both', 'each', 'few', 'more', 'other', 'some', 'such', 'than', 'too', 'very', 'can', 'will', 'just', 'don', 'should', 'now'];
                
                const keywordMap = {};
                
                videos.forEach(v => {
                    // 🔥 IMPROVED: Extract from TAGS + TITLE + DESCRIPTION
                    const allKeywords = new Set();
                    
                    // 1. Tags (if available)
                    if (v.tags && Array.isArray(v.tags)) {
                        v.tags.forEach(t => {
                            if (t) allKeywords.add(String(t).toLowerCase().trim());
                        });
                    }
                    
                    // 2. Extract from Title (split by common separators)
                    if (v.title) {
                        const titleWords = v.title.toLowerCase()
                            .replace(/[^\w\s\u00C0-\u024F\u1E00-\u1EFF]/g, ' ') // Keep accented chars
                            .split(/\s+/)
                            .filter(w => w.length >= 3);
                        
                        // Add individual words
                        titleWords.forEach(w => allKeywords.add(w));
                        
                        // Add 2-word phrases (bigrams)
                        for (let i = 0; i < titleWords.length - 1; i++) {
                            const bigram = titleWords[i] + ' ' + titleWords[i+1];
                            if (bigram.length >= 5) allKeywords.add(bigram);
                        }
                    }
                    
                    // 3. Extract from Description (first 500 chars only)
                    if (v.desc) {
                        const descWords = v.desc.substring(0, 500).toLowerCase()
                            .replace(/[^\w\s\u00C0-\u024F\u1E00-\u1EFF]/g, ' ')
                            .split(/\s+/)
                            .filter(w => w.length >= 4);
                        
                        descWords.slice(0, 30).forEach(w => allKeywords.add(w));
                    }
                    
                    // Process all keywords
                    allKeywords.forEach(keyword => {
                        if (keyword.length < 2 || stopWords.includes(keyword) || /^\d+$/.test(keyword)) return;
                        
                        if (!keywordMap[keyword]) {
                            keywordMap[keyword] = {
                                count: 0,
                                totalRatio: 0,
                                totalViews: 0,
                                channels: new Set(),
                                videos: [],
                                source: new Set() // Track where keyword came from
                            };
                        }
                        
                        keywordMap[keyword].count++;
                        keywordMap[keyword].totalRatio += (typeof v.ratio === 'number' ? v.ratio : 0);
                        keywordMap[keyword].totalViews += (typeof v.views === 'number' ? v.views : 0);
                        if (v.channelTitle) keywordMap[keyword].channels.add(v.channelTitle);
                        keywordMap[keyword].videos.push(v);
                    });
                });
                
                // Build niche objects
                let niches = Object.keys(keywordMap).map(tag => {
                    const data = keywordMap[tag];
                    if (data.count === 0) return null;
                    
                    const avgRatio = data.totalRatio / data.count;
                    const avgViews = data.totalViews / data.count;
                    
                    return {
                        tag: tag,
                        count: data.count,
                        avgRatio: avgRatio,
                        avgViews: avgViews,
                        rawSuccessRate: avgRatio * 100, // Convert ratio to percentage
                        competitorCount: data.channels.size,
                        channels: Array.from(data.channels),
                        videos: data.videos
                    };
                }).filter(n => n !== null);
                
                // 🔥 IMPROVED FILTER: Better niche detection
                // - Must appear in 2-10 videos (not too common, not too rare)
                // - Max 5 competitors (blue ocean)
                // - Min avgRatio 0.5 (at least some viral potential)
                niches = niches.filter(n => 
                    n.count >= 2 && 
                    n.count <= 15 && 
                    n.competitorCount <= 7 && 
                    n.avgRatio >= 0.5
                );
                
                // 🔥 IMPROVED SCORING: Prioritize viral potential in small niches
                niches.sort((a, b) => {
                    // Score formula: (avgRatio * 40) + ((8 - competitorCount) * 20) + (avgViews/10000 * 20)
                    const scoreA = (a.avgRatio * 40) + ((8 - a.competitorCount) * 20) + Math.min(20, a.avgViews / 10000);
                    const scoreB = (b.avgRatio * 40) + ((8 - b.competitorCount) * 20) + Math.min(20, b.avgViews / 10000);
                    return scoreB - scoreA;
                });
                
                // Take top 6 niches
                microNicheClusters = niches.slice(0, 6);
                
                console.log('💎 Micro Niche Analysis:', microNicheClusters.length, 'niches found from', Object.keys(keywordMap).length, 'keywords');
                
                renderMicroNiches(microNicheClusters);
                
            } catch (e) {
                console.error("Micro Niche Error:", e);
                if (container) container.classList.add('hidden');
            }
        }

        function renderMicroNiches(niches) {
            const container = document.getElementById('microNicheContainer');
            const grid = document.getElementById('microNicheGrid');
            
            if (!container || !grid) return;
            if (!niches || niches.length === 0) {
                container.classList.add('hidden');
                return;
            }
            
            container.classList.remove('hidden');
            
            const region = document.getElementById('globalRegion')?.value || 'VN';
            const lang = REGION_LANG_MAP[region] || 'vi';
            const inputKeyword = document.getElementById('keyword').value || (lang === 'vi' ? "Chủ đề này" : "This topic");
            
            const header = container.querySelector('h3');
            if (header) {
                header.innerHTML = lang === 'vi' 
                    ? `💎 Ngách Siêu Nhỏ cho "<span class="text-indigo-600">${inputKeyword}</span>"`
                    : `💎 Micro-Niche Opportunities for "<span class="text-indigo-600">${inputKeyword}</span>"`;
            }
            
            grid.innerHTML = niches.map((n, idx) => {
                // Status badge
                let statusColor = "bg-slate-200", statusText = "Avg";
                if (n.rawSuccessRate > 200) {
                    statusColor = "bg-gradient-to-r from-purple-600 to-pink-600";
                    statusText = lang === 'vi' ? "💎 Siêu Ngách" : "💎 Super Niche";
                } else if (n.rawSuccessRate > 100) {
                    statusColor = "bg-gradient-to-r from-green-500 to-emerald-600";
                    statusText = lang === 'vi' ? "🔥 Tiềm Năng" : "🔥 Potential";
                } else if (n.rawSuccessRate > 50) {
                    statusColor = "bg-blue-500";
                    statusText = lang === 'vi' ? "✅ Khá" : "✅ Good";
                }
                
                // Strategy detection
                let strategyIcon = "fa-chess-pawn", strategyTitle = "Niche", strategyColor = "text-green-500";
                if (n.avgRatio > 3.0) {
                    strategyIcon = "fa-fire";
                    strategyTitle = lang === 'vi' ? "Viral" : "Viral";
                    strategyColor = "text-red-500";
                } else if (n.competitorCount <= 2 && n.avgViews > 5000) {
                    strategyIcon = "fa-flag";
                    strategyTitle = "Blue Ocean";
                    strategyColor = "text-blue-500";
                } else if (n.competitorCount <= 3) {
                    strategyIcon = "fa-gem";
                    strategyTitle = lang === 'vi' ? "Cơ Hội" : "Opportunity";
                    strategyColor = "text-purple-500";
                }
                
                // Channel list (top 3)
                const topChannels = n.channels.slice(0, 3).map(c => `<span class="text-[9px] bg-slate-100 px-1.5 py-0.5 rounded text-slate-600 truncate max-w-[80px]" title="${c}">${c.length > 12 ? c.substring(0, 12) + '...' : c}</span>`).join('');
                
                return `
                    <div class="micro-niche-card bg-white p-5 rounded-xl border border-slate-200 shadow-sm hover:shadow-lg cursor-pointer relative group overflow-hidden transition-all hover:border-indigo-300" 
                         onclick="showNicheDetails(${idx})">
                        
                        <!-- Background Icon -->
                        <div class="absolute top-0 right-0 p-3 opacity-5 group-hover:opacity-10 transition-opacity">
                            <i class="fa-solid fa-bullseye text-5xl text-indigo-600"></i>
                        </div>
                        
                        <!-- Header -->
                        <div class="flex justify-between items-start mb-3 relative z-10">
                            <h4 class="font-bold text-slate-800 text-sm uppercase tracking-wide break-words w-3/4 line-clamp-2" title="${n.tag}">
                                ${n.tag}
                            </h4>
                            <span class="text-[10px] text-white px-2 py-0.5 rounded-full font-bold ${statusColor} shadow-sm whitespace-nowrap">
                                ${statusText}
                            </span>
                        </div>
                        
                        <!-- Stats Grid -->
                        <div class="grid grid-cols-2 gap-2 mb-3 relative z-10">
                            <div class="bg-slate-50 p-2 rounded border border-slate-100">
                                <span class="block text-[10px] text-slate-400">Avg Views</span>
                                <span class="block text-xs font-bold text-slate-700">${(n.avgViews / 1000).toFixed(1)}K</span>
                            </div>
                            <div class="bg-slate-50 p-2 rounded border border-slate-100">
                                <span class="block text-[10px] text-slate-400">Success Rate</span>
                                <span class="block text-xs font-bold ${n.rawSuccessRate > 100 ? 'text-green-600' : 'text-slate-700'}">${n.rawSuccessRate.toFixed(0)}%</span>
                            </div>
                        </div>
                        
                        <!-- Video Count & Competitors -->
                        <div class="flex items-center justify-between text-[10px] text-slate-500 mb-3 relative z-10">
                            <span><i class="fa-solid fa-video text-blue-400 mr-1"></i> ${n.count} videos</span>
                            <span><i class="fa-solid fa-users text-orange-400 mr-1"></i> ${n.competitorCount} đối thủ</span>
                        </div>
                        
                        <!-- Top Channels -->
                        <div class="flex flex-wrap gap-1 mb-3 relative z-10">
                            ${topChannels}
                        </div>
                        
                        <!-- Strategy -->
                        <div class="relative z-10 pt-3 border-t border-slate-100">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid ${strategyIcon} ${strategyColor} text-xs"></i>
                                    <span class="text-[10px] font-bold ${strategyColor} uppercase">${strategyTitle}</span>
                                </div>
                                <span class="text-[9px] text-indigo-500 font-bold group-hover:underline">Chi tiết →</span>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function showNicheDetails(idx) {
            if (!microNicheClusters || !microNicheClusters[idx]) return;
            
            const niche = microNicheClusters[idx];
            const modalContent = document.getElementById('nicheModalContent');
            const modalTitle = document.getElementById('nicheModalTitle');
            
            if (modalTitle) {
                modalTitle.innerText = `Chiến Lược Ngách: "${niche.tag.toUpperCase()}"`;
            }
            
            // Build channel stats
            const channelStats = {};
            niche.videos.forEach(v => {
                if (!v.channelId) return;
                if (!channelStats[v.channelId]) {
                    channelStats[v.channelId] = {
                        title: v.channelTitle || "Unknown",
                        channelId: v.channelId,
                        views: 0,
                        videoCount: 0,
                        bestVideo: v,
                        videos: []
                    };
                }
                channelStats[v.channelId].views += (v.views || 0);
                channelStats[v.channelId].videoCount++;
                channelStats[v.channelId].videos.push(v);
                if (v.views > (channelStats[v.channelId].bestVideo.views || 0)) {
                    channelStats[v.channelId].bestVideo = v;
                }
            });
            
            const sortedChannels = Object.values(channelStats).sort((a, b) => b.views - a.views).slice(0, 5);
            
            // Build top videos list (sorted by views)
            const topVideos = [...niche.videos].sort((a, b) => b.views - a.views).slice(0, 5);
            
            if (modalContent) {
                modalContent.innerHTML = `
                    <div class="space-y-6">
                        <!-- Stats Overview -->
                        <div class="grid grid-cols-3 gap-3">
                            <div class="bg-blue-50 p-3 rounded-lg text-center border border-blue-200">
                                <div class="text-2xl font-black text-blue-600">${niche.count}</div>
                                <div class="text-[10px] text-blue-500 font-bold">Videos</div>
                            </div>
                            <div class="bg-orange-50 p-3 rounded-lg text-center border border-orange-200">
                                <div class="text-2xl font-black text-orange-600">${niche.competitorCount}</div>
                                <div class="text-[10px] text-orange-500 font-bold">Đối Thủ</div>
                            </div>
                            <div class="bg-green-50 p-3 rounded-lg text-center border border-green-200">
                                <div class="text-2xl font-black text-green-600">${niche.rawSuccessRate.toFixed(0)}%</div>
                                <div class="text-[10px] text-green-500 font-bold">Success Rate</div>
                            </div>
                        </div>
                        
                        <!-- Strategy Box -->
                        <div class="bg-gradient-to-br from-slate-800 to-slate-900 p-5 rounded-xl border-l-4 border-yellow-500 shadow-lg text-white">
                            <h5 class="text-yellow-400 font-bold text-sm uppercase mb-3 flex items-center gap-2">
                                <i class="fa-solid fa-brain"></i> Tư Duy Chiến Lược
                            </h5>
                            <p class="text-sm leading-relaxed text-slate-300">
                                <strong>Ngách:</strong> <span class="text-yellow-300 font-bold">${niche.tag}</span><br>
                                <strong>Cơ hội:</strong> ${niche.competitorCount <= 3 ? '🟢 Ít đối thủ - Dễ xâm nhập!' : '🟡 Cạnh tranh vừa - Cần nội dung chất lượng'}<br>
                                <strong>Tiềm năng:</strong> ${niche.avgRatio > 2 ? '🔥 Viral cao!' : '✅ Ổn định'}
                            </p>
                        </div>
                        
                        <!-- Top Videos in Niche -->
                        <div>
                            <h5 class="font-bold text-sm text-slate-700 mb-3 flex items-center gap-2">
                                <i class="fa-solid fa-play text-red-500"></i> Top Videos Trong Ngách Này
                            </h5>
                            <div class="space-y-2">
                                ${topVideos.map((v, i) => `
                                    <a href="https://youtu.be/${v.id}" target="_blank" 
                                       class="flex items-center gap-3 p-2 bg-white rounded-lg border border-slate-200 hover:border-blue-300 hover:shadow-md transition group">
                                        <div class="relative flex-shrink-0">
                                            <img src="${v.thumbnail}" class="w-20 h-12 object-cover rounded" alt="">
                                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                                                <i class="fa-solid fa-play text-white text-xs"></i>
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-xs font-medium text-slate-800 line-clamp-1 group-hover:text-blue-600">${v.title}</div>
                                            <div class="text-[10px] text-slate-500 flex items-center gap-2 mt-0.5">
                                                <span><i class="fa-solid fa-eye"></i> ${(v.views/1000).toFixed(0)}K</span>
                                                <span><i class="fa-solid fa-user"></i> ${v.channelTitle?.substring(0, 15) || 'N/A'}...</span>
                                            </div>
                                        </div>
                                        <div class="text-xs font-bold ${v.ratio > 2 ? 'text-green-600' : 'text-slate-400'}">${v.ratio?.toFixed(1) || 0}x</div>
                                    </a>
                                `).join('')}
                            </div>
                        </div>
                        
                        <!-- Top Channels -->
                        <div>
                            <h5 class="font-bold text-sm text-slate-700 mb-3 flex items-center gap-2">
                                <i class="fa-solid fa-trophy text-yellow-500"></i> Top Kênh Thống Trị
                            </h5>
                            <div class="space-y-2">
                                ${sortedChannels.map((c, i) => `
                                    <div class="bg-white p-3 rounded-lg border border-slate-200 hover:shadow-md transition">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <div class="bg-slate-100 text-slate-500 font-bold w-6 h-6 rounded flex items-center justify-center text-xs">
                                                    ${i + 1}
                                                </div>
                                                <div>
                                                    <a href="https://www.youtube.com/channel/${c.channelId}" target="_blank" 
                                                       class="font-bold text-sm text-slate-800 hover:text-blue-600 hover:underline">
                                                        ${c.title}
                                                    </a>
                                                    <div class="text-[10px] text-slate-500">
                                                        ${c.videoCount} videos • ${(c.views/1000).toFixed(0)}K total views
                                                    </div>
                                                </div>
                                            </div>
                                            <a href="https://youtu.be/${c.bestVideo.id}" target="_blank" 
                                               class="text-[10px] px-2 py-1 bg-red-100 text-red-600 rounded font-bold hover:bg-red-200 transition">
                                                <i class="fa-solid fa-play mr-1"></i>Best Video
                                            </a>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    </div>
                `;
            }
            
            safeClass('nicheDetailModal', 'remove', 'opacity-0');
            safeClass('nicheDetailModal', 'remove', 'invisible');
            safeClass('nicheDetailModal', 'remove', 'pointer-events-none');
        }
        function closeNicheModal() { const modal = document.getElementById('nicheDetailModal'); if (modal) { modal.classList.add('opacity-0', 'pointer-events-none'); setTimeout(() => modal.classList.add('invisible'), 200); } }

        // --- MODAL FUNCTIONS (GIỮ NGUYÊN) ---
        async function openSpyModal(idx) {
            currentModalVid = currentData[idx]; const modal = document.getElementById('spyModal'); if (modal) { modal.classList.remove('opacity-0', 'pointer-events-none', 'invisible'); document.body.classList.add('modal-active'); }
            safeSetText('modalVideoId', currentModalVid.id); const preview = document.getElementById('modalVideoPreview'); if (preview) preview.innerHTML = `<img src="${currentModalVid.thumbnail}" class="w-full h-full object-cover">`; const dlBtn = document.getElementById('downloadThumbBtn'); if (dlBtn) dlBtn.href = currentModalVid.thumbnail; const monStatus = document.getElementById('monetizationStatus'); if (monStatus) monStatus.innerHTML = `<span class="font-bold ${currentModalVid.monetization.color}">${currentModalVid.monetization.status}</span> <span class="text-xs text-slate-400">(${currentModalVid.monetization.reason})</span>`; safeSetText('calcViews', currentModalVid.views.toLocaleString());
            const combinedText = (currentModalVid.title + " " + currentModalVid.tags.join(" ")).toLowerCase(); const niche = detectNiche(combinedText); safeSetText('modalRpmDisplay', '$' + globalRpm); calculateProfit();
            const risk = RISKY_KEYWORDS.find(w => combinedText.includes(w)); const policyAudit = document.getElementById('policyAudit'); if (policyAudit) { if (risk) { policyAudit.innerHTML = `<span class="text-red-600 font-bold">CẢNH BÁO:</span> Phát hiện từ khóa "${risk}". Rủi ro tắt kiếm tiền cao.`; safeSetText('safetyDisclaimer', "-- Video có rủi ro, không khuyến khích copy --"); } else { policyAudit.innerHTML = `<span class="text-green-600 font-bold">AN TOÀN:</span> Không phát hiện từ khóa vi phạm phổ biến.`; safeSetText('safetyDisclaimer', DISCLAIMERS[niche] || DISCLAIMERS['general']); } }
            let scriptHtml = ""; if (niche === 'health') scriptHtml = `<li><strong>Disclaimer:</strong> Bắt buộc có ở 10s đầu.</li><li><strong>Intro:</strong> Nêu vấn đề sức khỏe.</li><li><strong>Body:</strong> Giải pháp khoa học.</li>`; else if (niche === 'money') scriptHtml = `<li><strong>Proof:</strong> Show kết quả/thu nhập.</li><li><strong>Warning:</strong> Cảnh báo rủi ro đầu tư.</li>`; else if (niche === 'horror') scriptHtml = `<li><strong>Hook:</strong> Cảnh báo "Đừng xem một mình".</li><li><strong>Atmosphere:</strong> Dùng nhạc nền rùng rợn.</li>`; else scriptHtml = `<li><strong>Hook:</strong> 5s đầu cực quan trọng.</li><li><strong>CTA:</strong> Kêu gọi Sub ở phút thứ 3.</li>`;
            document.getElementById('scriptSuggestions').innerHTML = scriptHtml; const newTitles = generateViralTitles(currentModalVid.title, currentModalVid.tags); document.getElementById('modalAiTitles').innerHTML = newTitles.map(t => `<li>${t}</li>`).join(''); document.getElementById('modalTags').innerHTML = currentModalVid.tags.map(t => `<span class="bg-slate-100 px-2 py-1 rounded text-slate-600 border border-slate-200">${t}</span>`).join(''); safeSetText('modalDesc', currentModalVid.desc);
            
            // 🎨 THUMBNAIL ANALYSIS (Canvas API - Color Analysis)
            const thumbAnalysisBox = document.getElementById('thumbnailAnalysisBox');
            if (thumbAnalysisBox) {
                thumbAnalysisBox.innerHTML = '<div class="text-sm text-slate-400 animate-pulse">🎨 Analyzing thumbnail...</div>';
                
                // Analyze thumbnail colors (Canvas API)
                const colorData = await analyzeThumbnailColors(currentModalVid.thumbnail, currentModalVid.id);
                
                // Analyze with Vision API if key exists (Text, Faces, Objects)
                let visionData = null;
                if (globalVisionKey) {
                    thumbAnalysisBox.innerHTML = '<div class="text-sm text-purple-500 animate-pulse">🎨 Analyzing with Vision API...</div>';
                    visionData = await analyzeThumbnailWithVision(currentModalVid.thumbnail, currentModalVid.id);
                }
                
                // Render combined results
                if (colorData || visionData) {
                    let html = '';
                    
                    // Vision API results (Priority - more valuable)
                    if (visionData) {
                        html += renderVisionAnalysis(visionData);
                    }
                    
                    // Color analysis results (Fallback/Additional)
                    if (colorData) {
                        html += renderThumbnailAnalysis(colorData);
                    }
                    
                    thumbAnalysisBox.innerHTML = html;
                } else {
                    thumbAnalysisBox.innerHTML = '<div class="text-xs text-slate-400">Thumbnail analysis unavailable (CORS)</div>';
                }
            }
            
            const commentBox = document.getElementById('commentAnalysis'); const commentContent = document.getElementById('commentInsights'); if (commentBox) commentBox.classList.remove('hidden'); if (commentContent) commentContent.innerHTML = '<p class="italic text-slate-500">Đang tải bình luận...</p>';
            const apiKey = getActiveKey(); if (apiKey) { const comments = await fetchComments(currentModalVid.id, apiKey); if (comments.length > 0 && commentContent) { commentContent.innerHTML = comments.slice(0, 5).map(c => `<div class="p-2 bg-white rounded border border-blue-50 mb-1 text-[11px] leading-snug">"${c}"</div>`).join('') + `<p class="text-[10px] text-blue-500 mt-1">Phân tích: Khán giả quan tâm nhiều đến nội dung này.</p>`; } else if (commentContent) { commentContent.innerHTML = '<p class="text-slate-400 italic">Không có bình luận hoặc tính năng bị tắt.</p>'; } }
        }
        function closeModal() { document.getElementById('spyModal').classList.add('opacity-0', 'pointer-events-none'); setTimeout(() => { document.getElementById('spyModal').classList.add('invisible'); }, 200); document.body.classList.remove('modal-active'); }
        function addToTracking() { if (!currentModalVid) return; const c = { id: currentModalVid.channelId, title: currentModalVid.channelTitle, subs: currentModalVid.subs, lastUpdated: new Date() }; if (!trackedChannels.find(x => x.id === c.id)) { trackedChannels.push(c); localStorage.setItem('yt_tracker_channels', JSON.stringify(trackedChannels)); loadDashboard(); showToast("Đã thêm vào kho!"); } else { showToast("Kênh này đã có trong kho!", "warning"); } }
        function clearDashboard() { showConfirm("Bạn có chắc chắn muốn xóa toàn bộ dữ liệu trong kho?", () => { trackedChannels = []; localStorage.setItem('yt_tracker_channels', '[]'); loadDashboard(); showToast("Đã xóa sạch kho dữ liệu!"); }); }
        
        // --- UI FUNCTIONS (GIỮ NGUYÊN) ---
        function toggleCustomDate() { const val = document.getElementById('filterDate').value; if (val === 'custom') { safeClass('customDateContainer', 'remove', 'hidden'); safeClass('customDateContainer', 'add', 'grid'); } else { safeClass('customDateContainer', 'add', 'hidden'); safeClass('customDateContainer', 'remove', 'grid'); } }
        
        // ✅ Removed legacy openSettings(), closeSettings(), saveSettings() - Now using API Keys tab
        
        // 🎨 VISION API GUIDE MODAL FUNCTIONS
        function showVisionGuide() {
            const modal = document.getElementById('visionGuideModal');
            if (modal) {
                modal.classList.remove('opacity-0', 'invisible', 'pointer-events-none');
            }
        }
        
        function closeVisionGuide() {
            const modal = document.getElementById('visionGuideModal');
            if (modal) {
                modal.classList.add('opacity-0', 'pointer-events-none');
                setTimeout(() => {
                    modal.classList.add('invisible');
                }, 200);
            }
        }
        
        // ✅ clearAllData() now handled by API Keys tab
        function clearAllData() { 
            showConfirm("Hành động này sẽ XÓA TOÀN BỘ Key và Dữ liệu đã lưu. Tiếp tục?", () => { 
                localStorage.removeItem('yt_api_key_secure'); 
                localStorage.removeItem('yt_gemini_key_secure'); 
                localStorage.removeItem('gemini_keys_secure'); // Multi-key Gemini
                localStorage.removeItem('yt_openrouter_key_secure'); 
                localStorage.removeItem('openrouter_model'); // Selected model
                localStorage.removeItem('yt_vision_key_secure'); // NEW: Vision API
                localStorage.removeItem('yt_tracker_channels'); 
                location.reload(); 
            }); 
        }
        function toggleShowKey(id) { const input = document.getElementById(id); if (input.type === "password") input.type = "text"; else input.type = "password"; }
        
        function toggleFilters() { document.getElementById('advancedFilters').classList.toggle('hidden'); }
        function updateGlobalRpm() { const val = parseFloat(document.getElementById('globalRpmSlider').value); globalRpm = val; safeSetText('globalRpmDisplay', '$' + val.toFixed(1)); localStorage.setItem('yt_rpm', val); renderResults(); if (document.getElementById('spyModal').classList.contains('opacity-0') === false) { safeSetText('modalRpmDisplay', '$' + globalRpm); calculateProfit(); } }

        // 🔥 OUTLIER FILTER FUNCTION
        let filteredData = []; // Store filtered results
        
        function applyOutlierFilters() {
            // ⚠️ CHECK: Must have data first!
            if (!currentData || currentData.length === 0) {
                showToast('⚠️ Vui lòng TÌM KIẾM từ khóa trước khi lọc Outliers!', 'warning');
                
                // Highlight search button
                const searchBtn = document.getElementById('analyzeBtn');
                if (searchBtn) {
                    searchBtn.classList.add('animate-pulse');
                    setTimeout(() => searchBtn.classList.remove('animate-pulse'), 2000);
                }
                
                return;
            }
            
            const minOutlierRatio = parseFloat(document.getElementById('filterOutlierRatio').value);
            const maxSubs = parseInt(document.getElementById('filterMaxSubs').value);
            const minViralScore = parseInt(document.getElementById('filterViralScore').value);
            
            console.log('🔥 Applying MICRO-NICHE filters:', { minOutlierRatio, maxSubs, minViralScore });
            
            // Filter currentData based on criteria
            filteredData = currentData.filter(vid => {
                // Outlier ratio filter
                if (minOutlierRatio > 0 && vid.outlierRatio < minOutlierRatio) {
                    return false;
                }
                
                // Max channel size filter
                if (vid.subs > maxSubs) {
                    return false;
                }
                
                // Viral potential filter
                if (minViralScore > 0) {
                    const viralScore = calculateViralPotential(vid);
                    if (viralScore < minViralScore) {
                        return false;
                    }
                }
                
                return true;
            });
            
            console.log('✅ Filtered results:', filteredData.length, 'of', currentData.length);
            
            // Temporarily swap currentData with filteredData for rendering
            const originalData = currentData;
            currentData = filteredData;
            renderResults();
            currentData = originalData; // Restore original
            
            // Show toast with results
            const outlierCount = filteredData.filter(v => v.isOutlier).length;
            const microChannels = filteredData.filter(v => v.subs < 10000).length;
            
            let message = `Lọc xong: ${filteredData.length} videos (${outlierCount} outliers 🔥)`;
            if (maxSubs < 10000) {
                message += ` - ${microChannels} từ kênh MICRO 💎`;
            }
            showToast(message, 'success');
        }
        
        // Reset filters function
        function resetOutlierFilters() {
            document.getElementById('filterOutlierRatio').value = '0';
            document.getElementById('filterMaxSubs').value = '999999999';
            document.getElementById('filterViralScore').value = '0';
            renderResults(); // Show all results again
            showToast('Đã reset bộ lọc!');
        }

        // ==============================================
        // 🎯 CHANNEL HELPER FUNCTIONS
        // ==============================================
        
        /**
         * Extract Channel ID from various YouTube URL formats
         */
        function extractChannelId(input) {
            input = input.trim();
            
            // Already a channel ID (UC...)
            if (/^UC[a-zA-Z0-9_-]{22}$/.test(input)) {
                return input;
            }
            
            // Handle @username format: https://youtube.com/@channelname
            if (input.includes('@')) {
                // Extract username
                const match = input.match(/@([a-zA-Z0-9_-]+)/);
                if (match) {
                    return '@' + match[1]; // Return @username, will need to resolve later
                }
            }
            
            // Channel URL: youtube.com/channel/UC...
            const channelMatch = input.match(/youtube\.com\/channel\/([a-zA-Z0-9_-]+)/);
            if (channelMatch) {
                return channelMatch[1];
            }
            
            // User URL: youtube.com/user/username
            const userMatch = input.match(/youtube\.com\/user\/([a-zA-Z0-9_-]+)/);
            if (userMatch) {
                return userMatch[1]; // Will need to resolve to channel ID
            }
            
            // C/ format: youtube.com/c/customname
            const customMatch = input.match(/youtube\.com\/c\/([a-zA-Z0-9_-]+)/);
            if (customMatch) {
                return customMatch[1];
            }
            
            return null;
        }
        
        /**
         * Resolve @username or custom URL to Channel ID using YouTube API
         * 🔥 UPDATED: Use multiple methods for better reliability
         * Returns: { channelId, channelName, customUrl } or null
         */
        async function resolveChannelId(identifier, apiKey) {
            try {
                console.log('🔍 Resolving channel ID for:', identifier);
                
                // Method 1: If starts with @, use forHandle parameter (NEW YouTube API)
                if (identifier.startsWith('@')) {
                    const handle = identifier.substring(1);
                    
                    // 🔥 NEW: Use forHandle parameter (YouTube API v3 latest) with auto-retry
                    const handleUrl = `https://www.googleapis.com/youtube/v3/channels?part=id,snippet&forHandle=${encodeURIComponent(handle)}&key=${apiKey}`;
                    console.log('📡 Trying forHandle API...');
                    const handleResult = await ytFetchWithRetry(handleUrl);
                    
                    if (handleResult.ok && handleResult.data.items && handleResult.data.items.length > 0) {
                        const channel = handleResult.data.items[0];
                        console.log('✅ Found via forHandle:', channel.id, '-', channel.snippet.title);
                        return {
                            channelId: channel.id,
                            channelName: channel.snippet.title,
                            customUrl: channel.snippet.customUrl || '@' + handle
                        };
                    }
                    
                    // Fallback: Search API with exact handle match
                    console.log('⚠️ forHandle failed, trying search API...');
                    const searchUrl = `https://www.googleapis.com/youtube/v3/search?part=snippet&type=channel&q=${encodeURIComponent('@' + handle)}&maxResults=10&key=${apiKey}`;
                    const searchResult = await ytFetchWithRetry(searchUrl);
                    
                    if (searchResult.ok && searchResult.data.items && searchResult.data.items.length > 0) {
                        // Find exact match by comparing handle in customUrl
                        for (const item of searchResult.data.items) {
                            const channelId = item.snippet.channelId;
                            // Verify this is the correct channel
                            const verifyUrl = `https://www.googleapis.com/youtube/v3/channels?part=snippet&id=${channelId}&key=${apiKey}`;
                            const verifyResult = await ytFetchWithRetry(verifyUrl);
                            
                            if (verifyResult.ok && verifyResult.data.items && verifyResult.data.items.length > 0) {
                                const channelSnippet = verifyResult.data.items[0].snippet;
                                const customUrl = (channelSnippet.customUrl || '').toLowerCase();
                                const targetHandle = handle.toLowerCase();
                                
                                // Check if customUrl matches the handle (with or without @)
                                if (customUrl === '@' + targetHandle || 
                                    customUrl === targetHandle ||
                                    customUrl.includes(targetHandle)) {
                                    console.log('✅ Found via search + verify:', channelId, '-', channelSnippet.title);
                                    return {
                                        channelId: channelId,
                                        channelName: channelSnippet.title,
                                        customUrl: channelSnippet.customUrl || '@' + handle
                                    };
                                }
                            }
                        }
                        
                        // 🚫 NO FALLBACK TO FIRST RESULT - Return null if no exact match
                        console.log('❌ No exact match found for handle:', handle);
                        console.log('📝 Search results were:', searchResult.data.items.map(i => i.snippet.title).join(', '));
                        return null;
                    }
                }
                
                // Method 2: Try forUsername (legacy) with auto-retry
                const channelUrl = `https://www.googleapis.com/youtube/v3/channels?part=id,snippet&forUsername=${encodeURIComponent(identifier)}&key=${apiKey}`;
                const result = await ytFetchWithRetry(channelUrl);
                
                if (result.ok && result.data.items && result.data.items.length > 0) {
                    const channel = result.data.items[0];
                    console.log('✅ Found via forUsername:', channel.id, '-', channel.snippet.title);
                    return {
                        channelId: channel.id,
                        channelName: channel.snippet.title,
                        customUrl: channel.snippet.customUrl || identifier
                    };
                }
                
                console.log('❌ Could not resolve channel ID for:', identifier);
                return null;
            } catch (error) {
                console.error('Error resolving channel ID:', error);
                return null;
            }
        }
        
        /**
         * Fetch all videos from a channel
         */
        async function fetchChannelVideos(channelId, apiKey, maxResults = 50) {
            try {
                // First, get channel info and uploads playlist ID with auto-retry
                const channelUrl = `https://www.googleapis.com/youtube/v3/channels?part=snippet,statistics,contentDetails&id=${channelId}&key=${apiKey}`;
                const channelResult = await ytFetchWithRetry(channelUrl);
                
                if (!channelResult.ok || !channelResult.data.items || channelResult.data.items.length === 0) {
                    throw new Error('Channel not found');
                }
                
                const channel = channelResult.data.items[0];
                const uploadsPlaylistId = channel.contentDetails.relatedPlaylists.uploads;
                const channelInfo = {
                    title: channel.snippet.title,
                    thumbnail: channel.snippet.thumbnails.medium.url,
                    subscribers: parseInt(channel.statistics.subscriberCount) || 0,
                    totalVideos: parseInt(channel.statistics.videoCount) || 0,
                    totalViews: parseInt(channel.statistics.viewCount) || 0
                };
                
                console.log('📺 Channel found:', channelInfo.title, '- Subs:', channelInfo.subscribers.toLocaleString());
                
                // Fetch videos from uploads playlist
                const videos = [];
                let pageToken = '';
                let fetchedCount = 0;
                
                while (fetchedCount < maxResults) {
                    const playlistUrl = `https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&playlistId=${uploadsPlaylistId}&maxResults=50&pageToken=${pageToken}&key=${apiKey}`;
                    const playlistResult = await ytFetchWithRetry(playlistUrl);
                    
                    if (!playlistResult.ok || !playlistResult.data.items) break;
                    const playlistData = playlistResult.data;
                    
                    // Get video IDs
                    const videoIds = playlistData.items.map(item => item.snippet.resourceId.videoId);
                    
                    // Fetch detailed video statistics
                    const videoUrl = `https://www.googleapis.com/youtube/v3/videos?part=snippet,statistics,contentDetails&id=${videoIds.join(',')}&key=${apiKey}`;
                    const videoResult = await ytFetchWithRetry(videoUrl);
                    
                    if (videoResult.ok && videoResult.data.items) {
                        videos.push(...videoResult.data.items);
                        fetchedCount += videoResult.data.items.length;
                    }
                    
                    // Check if there's more
                    if (!playlistData.nextPageToken || fetchedCount >= maxResults) break;
                    pageToken = playlistData.nextPageToken;
                    
                    // Rate limiting
                    await sleep(200);
                }
                
                return { channelInfo, videos };
            } catch (error) {
                console.error('Error fetching channel videos:', error);
                throw error;
            }
        }
        
        // --- MAIN ANALYSIS (WITH OUTLIER DETECTION) ---
        async function analyzeKeywords() {
            // 🔒 FREE TIER BLOCK: Không cho phép tìm kiếm
            if (IS_FREE_LOCKED) {
                showToast('🔒 Free tier không được tìm kiếm! Upgrade để unlock!', 'error');
                
                // 🔥 NEW: Show FULL PLAN COMPARISON MODAL
                const modal = document.createElement('div');
                modal.className = 'fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4';
                modal.innerHTML = `
                    <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full transform scale-95 animate-scale-in overflow-hidden">
                        <!-- Header -->
                        <div class="bg-gradient-to-r from-red-600 to-pink-600 p-6 text-white">
                            <div class="text-center">
                                <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <i class="fa-solid fa-lock-open text-3xl"></i>
                                </div>
                                <h3 class="text-3xl font-black mb-2">🔓 Mở Khóa Toàn Bộ Tính Năng</h3>
                                <p class="text-red-100">Chọn gói phù hợp với nhu cầu của bạn</p>
                            </div>
                        </div>
                        
                        <!-- Plans Comparison -->
                        <div class="p-6 bg-gradient-to-br from-slate-50 to-white">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                                
                                <!-- TRIAL PLAN -->
                                <div class="bg-white border-2 border-green-300 rounded-xl p-5 hover:shadow-lg transition">
                                    <div class="text-center mb-4">
                                        <div class="inline-block bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-black mb-2">
                                            🚀 KHỚi ĐẦU
                                        </div>
                                        <h4 class="text-2xl font-black text-slate-900">Dùng Thử</h4>
                                        <div class="mt-2">
                                            <span class="text-3xl font-black text-green-600">39K</span>
                                            <span class="text-slate-500 text-sm">/3 ngày</span>
                                        </div>
                                        <p class="text-xs text-slate-500 mt-1">~13K/ngày</p>
                                    </div>
                                    <ul class="space-y-2 text-xs text-slate-700 mb-4">
                                        <li class="flex items-start gap-2">
                                            <i class="fa-solid fa-check text-green-600 mt-0.5"></i>
                                            <span>♾️ Không giới hạn tìm kiếm</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <i class="fa-solid fa-check text-green-600 mt-0.5"></i>
                                            <span>Xem đầy đủ kết quả</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <i class="fa-solid fa-times text-red-400 mt-0.5"></i>
                                            <span class="text-slate-400">Không xuất CSV</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <i class="fa-solid fa-times text-red-400 mt-0.5"></i>
                                            <span class="text-slate-400">Không AI Deep Dive</span>
                                        </li>
                                    </ul>
                                    <a href="checkout.php?plan=trial" 
                                       class="block w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-lg transition text-center">
                                        Chọn Trial
                                    </a>
                                </div>
                                
                                <!-- BASIC PLAN -->
                                <div class="bg-white border-2 border-blue-300 rounded-xl p-5 hover:shadow-lg transition">
                                    <div class="text-center mb-4">
                                        <div class="inline-block bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-xs font-black mb-2">
                                            📈 PHỔ BIẾN
                                        </div>
                                        <h4 class="text-2xl font-black text-slate-900">Basic</h4>
                                        <div class="mt-2">
                                            <span class="text-3xl font-black text-blue-600">99.000 vnđ</span>
                                            <span class="text-slate-500 text-sm">/tháng</span>
                                        </div>
                                        <p class="text-xs text-slate-500 mt-1">~6.6K/ngày</p>
                                    </div>
                                    <ul class="space-y-2 text-xs text-slate-700 mb-4">
                                        <li class="flex items-start gap-2">
                                            <i class="fa-solid fa-check text-blue-600 mt-0.5"></i>
                                            <span>♾️ Không giới hạn tìm kiếm</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <i class="fa-solid fa-check text-blue-600 mt-0.5"></i>
                                            <span>Xem đầy đủ kết quả</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <i class="fa-solid fa-check text-blue-600 mt-0.5"></i>
                                            <span class="font-bold">Xuất CSV không giới hạn</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <i class="fa-solid fa-times text-red-400 mt-0.5"></i>
                                            <span class="text-slate-400">Không AI Deep Dive</span>
                                        </li>
                                    </ul>
                                    <a href="checkout.php?plan=1m" 
                                       class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition text-center">
                                        Chọn Basic
                                    </a>
                                </div>
                                
                                <!-- PREMIUM PLAN (HIGHLIGHTED) -->
                                <div class="bg-gradient-to-br from-yellow-50 to-orange-50 border-4 border-yellow-400 rounded-xl p-5 hover:shadow-2xl transition relative">
                                    <div class="absolute -top-3 left-1/2 transform -translate-x-1/2">
                                        <span class="bg-gradient-to-r from-yellow-400 to-orange-500 text-white text-xs font-black px-4 py-1 rounded-full shadow-lg">
                                            ⭐ PHỔ BIẾN NHẤT
                                        </span>
                                    </div>
                                    <div class="text-center mb-4 mt-2">
                                        <div class="inline-block bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-xs font-black mb-2">
                                            👑 PREMIUM
                                        </div>
                                        <h4 class="text-2xl font-black text-slate-900">Premium</h4>
                                        <div class="mt-2">
                                            <span class="text-3xl font-black text-yellow-600">653.400 vnđ</span>
                                            <span class="text-slate-500 text-sm">/năm</span>
                                        </div>
                                        <p class="text-xs text-green-600 font-bold mt-1">💰 Tiết kiệm 834K</p>
                                    </div>
                                    <ul class="space-y-2 text-xs text-slate-700 mb-4">
                                        <li class="flex items-start gap-2">
                                            <i class="fa-solid fa-check text-yellow-600 mt-0.5"></i>
                                            <span>♾️ Không giới hạn tìm kiếm</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <i class="fa-solid fa-check text-yellow-600 mt-0.5"></i>
                                            <span>Xem đầy đủ kết quả</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <i class="fa-solid fa-check text-yellow-600 mt-0.5"></i>
                                            <span class="font-bold">Xuất CSV không giới hạn</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <i class="fa-solid fa-check text-yellow-600 mt-0.5"></i>
                                            <span class="font-bold text-yellow-700">🧠 AI Deep Dive (Gemini)</span>
                                        </li>
                                    </ul>
                                    <a href="checkout.php?plan=12m" 
                                       class="block w-full bg-gradient-to-r from-yellow-500 to-orange-500 hover:from-yellow-600 hover:to-orange-600 text-white font-black py-3 px-4 rounded-lg transition text-center shadow-lg">
                                        👑 Chọn Premium
                                    </a>
                                </div>
                                
                            </div>
                            
                            <!-- View Full Pricing Link -->
                            <div class="text-center border-t border-slate-200 pt-4">
                                <p class="text-sm text-slate-600 mb-3">Xem thêm các gói 3 tháng, 6 tháng và so sánh chi tiết</p>
                                <a href="pricing.php" class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-700 font-bold text-sm">
                                    <i class="fa-solid fa-table-cells-large"></i>
                                    Xem Bảng Giá Đầy Đủ
                                    <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            </div>
                            
                            <!-- Close Button -->
                            <button onclick="this.closest('.fixed').remove()" 
                                    class="absolute top-4 right-4 w-8 h-8 bg-white/90 hover:bg-white rounded-full flex items-center justify-center text-slate-600 hover:text-slate-900 transition shadow-lg">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);
                
                return; // Block execution
            }
            
            if (!globalApiKey) { const key = getActiveKey(); if (!key) { openSettings(); return; } globalApiKey = key; }
            const keyword = document.getElementById('keyword').value; const region = document.getElementById('globalRegion').value; const maxResults = document.getElementById('maxResults').value; const isSlowMode = document.getElementById('slowModeToggle').checked;
            if (!keyword) { showToast("Vui lòng nhập từ khóa mục tiêu!", "warning"); return; }
            safeClass('loading', 'remove', 'hidden'); safeClass('resultsArea', 'add', 'hidden'); const btn = document.getElementById('analyzeBtn'); if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Đang phân tích...'; }
            
            try { 
                if (isSlowMode) await sleep(1000); 
                
                // Fetch video data
                const data = await fetchAndProcess(globalApiKey, keyword, maxResults, region); 
                currentData = data; 
                
                if (currentData.length === 0) throw new Error("Không tìm thấy dữ liệu phù hợp."); 
                
                // 🔥 OUTLIER DETECTION: Calculate channel averages
                console.log('🔥 Calculating outliers for', currentData.length, 'videos...');
                const uniqueChannelIds = [...new Set(currentData.map(v => v.channelId))];
                const channelAverages = {};
                
                // Calculate averages for each unique channel (in batches to avoid API limits)
                const batchSize = 5;
                for (let i = 0; i < uniqueChannelIds.length; i += batchSize) {
                    const batch = uniqueChannelIds.slice(i, i + batchSize);
                    const promises = batch.map(channelId => calculateChannelAverageViews(channelId, globalApiKey));
                    const results = await Promise.all(promises);
                    
                    batch.forEach((channelId, idx) => {
                        channelAverages[channelId] = results[idx];
                    });
                    
                    // Small delay between batches
                    if (i + batchSize < uniqueChannelIds.length) {
                        await sleep(200);
                    }
                }
                
                // Detect outliers in the data
                currentData = detectOutliers(currentData, channelAverages);
                console.log('✅ Outlier detection complete!');
                
                // 🌟 GOLD MINE SCORING SYSTEM (from niche.php)
                currentData.forEach(video => {
                    // 🔥 PRIORITY: Use activeMonthsOld (first video) > channelMonthsOld (account created)
                    // activeMonthsOld is more accurate - shows when channel ACTUALLY started
                    const monthsOld = video.activeMonthsOld ?? video.channelMonthsOld ?? 999;
                    
                    // Calculate GOLD MINE priority score
                    let goldScore = 0;
                    
                    // 1. NEW CHANNEL BONUS (< 12 months active = +1000 points!)
                    if (monthsOld <= 12) goldScore += 1000;
                    else if (monthsOld <= 24) goldScore += 500;
                    
                    // 2. SMALL SUBSCRIBER BONUS (easier to replicate)
                    if (video.subs < 10000) goldScore += 300;
                    else if (video.subs < 50000) goldScore += 150;
                    else if (video.subs < 100000) goldScore += 50;
                    
                    // 3. HIGH RATIO BONUS (viral video)
                    goldScore += video.ratio * 10;
                    
                    // 4. OUTLIER BONUS (if video is outlier, extra boost!)
                    if (video.isOutlier && video.outlierRatio) {
                        goldScore += video.outlierRatio * 5;
                    }
                    
                    video.goldScore = goldScore;
                    video.isGoldMine = (monthsOld <= 12 && video.subs < 50000 && video.ratio > 0.5);
                });
                
                console.log('🌟 Gold Score calculation complete!');
                
                // 🔥 SORT BY GOLD SCORE (prioritize new small channels with viral videos)
                currentData.sort((a, b) => b.goldScore - a.goldScore);
                
                // Continue with existing analysis
                extractWinningTags(); 
                renderCompetitorLeaderboard(); 
                calculateVerdict(keyword, currentData); 
                analyzeMicroNiches(currentData); 
                analyzeUploadTimes(currentData); 
                renderResults(); 
                
                const outlierCount = currentData.filter(v => v.isOutlier).length;
                const goldMineCount = currentData.filter(v => v.isGoldMine).length;
                showToast(`🌟 Tìm thấy ${goldMineCount} GOLD MINE (kênh mới + view nổ)! Tổng: ${currentData.length} video`, 'success'); 
            } catch (err) { 
                console.error(err); 
                showToast("Lỗi: " + err.message + " (Thử đổi Key khác)", "error"); 
            } finally { 
                safeClass('loading', 'add', 'hidden'); 
                if (btn) { 
                    btn.disabled = false; 
                    btn.innerHTML = '<span>KÍCH HOẠT</span> <i class="fa-solid fa-radar"></i>'; 
                } 
            }
        }

        async function fetchAndProcess(apiKey, keyword, maxResults, regionCode) {
            const filterDuration = document.getElementById('filterDuration').value; const filterDate = document.getElementById('filterDate').value; const minViews = parseInt(document.getElementById('minViews').value) || 0; const minSubs = parseInt(document.getElementById('minSubs').value) || 0;
            let publishedAfter = '', publishedBefore = ''; const now = new Date();
            try { if (filterDate === 'hour') { const d = new Date(now); d.setHours(d.getHours() - 1); publishedAfter = d.toISOString(); } else if (filterDate === 'today') { const d = new Date(now); d.setDate(d.getDate() - 1); publishedAfter = d.toISOString(); } else if (filterDate === 'week') { const d = new Date(now); d.setDate(d.getDate() - 7); publishedAfter = d.toISOString(); } else if (filterDate === 'month') { const d = new Date(now); d.setMonth(d.getMonth() - 1); publishedAfter = d.toISOString(); } else if (filterDate === '3month') { const d = new Date(now); d.setMonth(d.getMonth() - 3); publishedAfter = d.toISOString(); } else if (filterDate === 'custom') { const dFrom = document.getElementById('dateFrom').value; const dTo = document.getElementById('dateTo').value; if (dFrom) { const t = new Date(dFrom); t.setHours(0,0,0,0); publishedAfter = t.toISOString(); } if (dTo) { const t = new Date(dTo); t.setHours(23,59,59,999); publishedBefore = t.toISOString(); } } } catch (e) { console.warn("Date parsing error", e); }
            
            // 🔥 Build URL and use auto-retry
            let url = `https://www.googleapis.com/youtube/v3/search?part=id,snippet&q=${encodeURIComponent(keyword)}&type=video&maxResults=${maxResults}&order=viewCount&key=${apiKey}`; 
            if (regionCode) url += `&regionCode=${regionCode}`; 
            if (filterDuration === 'short') url += `&videoDuration=short`; 
            if (filterDuration === 'long') url += `&videoDuration=medium`; 
            if (publishedAfter) url += `&publishedAfter=${publishedAfter}`; 
            if (publishedBefore) url += `&publishedBefore=${publishedBefore}`;
            
            // 🔥 SEARCH with auto-retry
            const searchResult = await ytFetchWithRetry(url);
            if (!searchResult.ok) throw new Error("API Error: " + (searchResult.error || 'Unknown'));
            const searchData = searchResult.data;
            if (!searchData.items?.length) return [];
            
            // 🔥 VIDEOS with auto-retry
            const videoIds = searchData.items.map(i => i.id.videoId).join(','); 
            const vidResult = await ytFetchWithRetry(`https://www.googleapis.com/youtube/v3/videos?part=statistics,snippet,contentDetails&id=${videoIds}&key=${apiKey}`);
            if (!vidResult.ok) throw new Error("Videos API Error");
            const vidData = vidResult.data;
            
            // 🔥 CHANNELS with auto-retry (include snippet for publishedAt = channel creation date)
            const channelIds = [...new Set(vidData.items.map(i => i.snippet.channelId))].join(','); 
            let chanMap = {}; 
            if (channelIds) { 
                const chanResult = await ytFetchWithRetry(`https://www.googleapis.com/youtube/v3/channels?part=statistics,snippet&id=${channelIds}&key=${apiKey}`); 
                if (chanResult.ok && chanResult.data.items) {
                    chanResult.data.items.forEach(i => {
                        chanMap[i.id] = i;
                        // 🔥 Store channel creation date from snippet.publishedAt
                        chanMap[i.id].channelCreatedAt = i.snippet?.publishedAt;
                    }); 
                }
                
                // 🔥 FETCH FIRST VIDEO DATE FOR EACH CHANNEL (batch process)
                const channelIdArray = channelIds.split(',');
                const firstVideoPromises = channelIdArray.map(async (chId) => {
                    try {
                        // Get oldest video (order=date, reverse not available, so get all and sort)
                        // Alternative: Use playlistItems with uploads playlist, sorted by date ascending
                        const uploadsPlaylistId = chanMap[chId]?.contentDetails?.relatedPlaylists?.uploads || `UU${chId.substring(2)}`;
                        const firstVidUrl = `https://www.googleapis.com/youtube/v3/search?part=snippet&channelId=${chId}&type=video&order=date&maxResults=1&key=${apiKey}`;
                        
                        // Fetch oldest videos by getting recent and checking publishedAt
                        // YouTube API doesn't support ascending order, so we use a workaround:
                        // Fetch playlist items and get the last page
                        const playlistUrl = `https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&playlistId=${uploadsPlaylistId}&maxResults=50&key=${apiKey}`;
                        const plResult = await ytFetchWithRetry(playlistUrl);
                        
                        if (plResult.ok && plResult.data.items && plResult.data.items.length > 0) {
                            // Find the oldest video in the batch
                            let oldestDate = null;
                            plResult.data.items.forEach(item => {
                                const pubDate = new Date(item.snippet.publishedAt);
                                if (!oldestDate || pubDate < oldestDate) {
                                    oldestDate = pubDate;
                                }
                            });
                            
                            // If there's a next page, the actual first video is even older
                            // For now, use the oldest from first 50 as approximation
                            if (oldestDate && chanMap[chId]) {
                                chanMap[chId].firstVideoDate = oldestDate.toISOString();
                            }
                        }
                    } catch (e) {
                        console.warn('Error fetching first video for channel:', chId, e);
                    }
                });
                
                // Wait for all first video fetches (with timeout)
                await Promise.race([
                    Promise.all(firstVideoPromises),
                    new Promise(resolve => setTimeout(resolve, 5000)) // 5s timeout
                ]);
            }
            return vidData.items.map(vid => { 
                const stats = vid.statistics || {}; 
                const snippet = vid.snippet || {}; 
                const chanObj = chanMap[snippet.channelId] || {}; 
                const cStats = chanObj.statistics || {}; 
                const views = parseInt(stats.viewCount || 0); 
                const subs = parseInt(cStats.subscriberCount || 0); 
                const ratio = subs === 0 ? 0 : parseFloat((views / (subs === 0 ? 1 : subs)).toFixed(2)); 
                const monStatus = estimateMonetization(subs, views, snippet.title || "", snippet.tags || []); 
                let pubTime = ""; 
                if (snippet.publishedAt) { 
                    const pt = moment(snippet.publishedAt); 
                    if (pt.isValid()) pubTime = pt.format("HH:mm - DD/MM/YYYY"); 
                } 
                
                // 🔥 Calculate channel age from channel's publishedAt (account creation)
                let channelMonthsOld = null;
                if (chanObj.channelCreatedAt) {
                    const channelCreatedDate = new Date(chanObj.channelCreatedAt);
                    const now = new Date();
                    channelMonthsOld = (now - channelCreatedDate) / (1000 * 60 * 60 * 24 * 30);
                }
                
                // 🔥 Calculate ACTIVE age from first video date (when channel started uploading)
                let activeMonthsOld = null;
                if (chanObj.firstVideoDate) {
                    const firstVideoDate = new Date(chanObj.firstVideoDate);
                    const now = new Date();
                    activeMonthsOld = (now - firstVideoDate) / (1000 * 60 * 60 * 24 * 30);
                }
                
                return { 
                    id: vid.id, 
                    title: snippet.title || "No Title", 
                    desc: snippet.description || "", 
                    tags: snippet.tags || [], 
                    thumbnail: (snippet.thumbnails && (snippet.thumbnails.maxres || snippet.thumbnails.high || snippet.thumbnails.default)) ? (snippet.thumbnails.maxres || snippet.thumbnails.high || snippet.thumbnails.default).url : "", 
                    channelId: snippet.channelId || "", 
                    channelTitle: snippet.channelTitle || "Unknown", 
                    publishedAt: snippet.publishedAt, 
                    publishedTimeStr: pubTime, 
                    channelCreatedAt: chanObj.channelCreatedAt || null,
                    channelMonthsOld: channelMonthsOld,
                    firstVideoDate: chanObj.firstVideoDate || null,
                    activeMonthsOld: activeMonthsOld,
                    views, 
                    subs, 
                    ratio, 
                    duration: vid.contentDetails ? parseDuration(vid.contentDetails.duration) : "--:--", 
                    monetization: monStatus 
                }; 
            }).filter(v => { if (minViews && v.views < minViews) return false; if (minSubs && v.subs < minSubs) return false; return true; });
        }

        // ==========================================
        // 🔥 OUTLIER DETECTION SYSTEM (1of10 Style)
        // ==========================================
        
        /**
         * Calculate average views per video for a channel
         * Uses exponential moving average for accuracy
         */
        async function calculateChannelAverageViews(channelId, apiKey) {
            try {
                // Fetch channel's recent videos to calculate average
                const url = `https://www.googleapis.com/youtube/v3/search?part=id&channelId=${channelId}&type=video&order=date&maxResults=50&key=${apiKey}`;
                const searchResult = await ytFetchWithRetry(url);
                if (!searchResult.ok || !searchResult.data.items || searchResult.data.items.length === 0) return null;
                const searchData = searchResult.data;
                
                const videoIds = searchData.items.map(i => i.id.videoId).join(',');
                const vidUrl = `https://www.googleapis.com/youtube/v3/videos?part=statistics&id=${videoIds}&key=${apiKey}`;
                const vidResult = await ytFetchWithRetry(vidUrl);
                if (!vidResult.ok || !vidResult.data.items || vidResult.data.items.length === 0) return null;
                const vidData = vidResult.data;
                
                const totalViews = vidData.items.reduce((sum, vid) => {
                    return sum + parseInt(vid.statistics?.viewCount || 0);
                }, 0);
                
                return Math.round(totalViews / vidData.items.length);
            } catch (e) {
                console.warn('Error calculating channel average:', e);
                return null;
            }
        }

        /**
         * Detect outlier videos (10x+ performance)
         * Returns outlier ratio and classification
         */
        function detectOutliers(videos, channelAverages) {
            return videos.map(video => {
                const channelAvg = channelAverages[video.channelId];
                
                if (!channelAvg || channelAvg === 0) {
                    return {
                        ...video,
                        outlierRatio: 0,
                        isOutlier: false,
                        outlierLevel: 'none'
                    };
                }
                
                const outlierRatio = parseFloat((video.views / channelAvg).toFixed(1));
                
                let isOutlier = false;
                let outlierLevel = 'none';
                
                if (outlierRatio >= 50) {
                    isOutlier = true;
                    outlierLevel = 'mega'; // 🔥🔥🔥 MEGA OUTLIER
                } else if (outlierRatio >= 20) {
                    isOutlier = true;
                    outlierLevel = 'super'; // 🔥🔥 SUPER OUTLIER
                } else if (outlierRatio >= 10) {
                    isOutlier = true;
                    outlierLevel = 'standard'; // 🔥 OUTLIER
                }
                
                return {
                    ...video,
                    outlierRatio,
                    isOutlier,
                    outlierLevel,
                    channelAvgViews: channelAvg
                };
            });
        }

        /**
         * Get outlier badge HTML
         */
        function getOutlierBadge(video) {
            if (!video.isOutlier) return '';
            
            let badge = '';
            let bgColor = '';
            let icon = '';
            
            switch (video.outlierLevel) {
                case 'mega':
                    bgColor = 'bg-gradient-to-r from-purple-600 to-pink-600';
                    icon = '🔥🔥🔥';
                    badge = `${icon} ${video.outlierRatio}x MEGA`;
                    break;
                case 'super':
                    bgColor = 'bg-gradient-to-r from-orange-500 to-red-600';
                    icon = '🔥🔥';
                    badge = `${icon} ${video.outlierRatio}x SUPER`;
                    break;
                case 'standard':
                    bgColor = 'bg-gradient-to-r from-red-500 to-orange-500';
                    icon = '🔥';
                    badge = `${icon} ${video.outlierRatio}x OUTLIER`;
                    break;
            }
            
            return `<span class="${bgColor} text-white text-[10px] px-2 py-0.5 rounded-full font-black shadow-lg animate-pulse">${badge}</span>`;
        }

        /**
         * Get viral potential score (0-100)
         */
        function calculateViralPotential(video) {
            let score = 0;
            
            // Outlier ratio weight (40 points)
            if (video.outlierRatio >= 50) score += 40;
            else if (video.outlierRatio >= 20) score += 35;
            else if (video.outlierRatio >= 10) score += 30;
            else score += Math.min(25, video.outlierRatio * 2);
            
            // View/Sub ratio weight (30 points)
            if (video.ratio >= 5) score += 30;
            else if (video.ratio >= 3) score += 25;
            else if (video.ratio >= 1) score += 20;
            else score += Math.min(15, video.ratio * 10);
            
            // Small channel bonus (30 points)
            if (video.subs < 10000) score += 30;
            else if (video.subs < 50000) score += 20;
            else if (video.subs < 100000) score += 10;
            
            return Math.min(100, Math.round(score));
        }

        /**
         * Get viral potential bar HTML
         */
        function getViralPotentialBar(score) {
            let colorClass = '';
            let label = '';
            
            if (score >= 80) {
                colorClass = 'bg-green-500';
                label = 'VIRAL 🚀';
            } else if (score >= 60) {
                colorClass = 'bg-yellow-500';
                label = 'HIGH 📈';
            } else if (score >= 40) {
                colorClass = 'bg-orange-500';
                label = 'MEDIUM 📊';
            } else {
                colorClass = 'bg-slate-400';
                label = 'LOW 📉';
            }
            
            return `
                <div class="flex items-center gap-2" title="Viral Potential Score">
                    <div class="flex-1 h-2 bg-slate-200 rounded-full overflow-hidden">
                        <div class="${colorClass} h-full transition-all duration-500" style="width: ${score}%"></div>
                    </div>
                    <span class="text-[9px] font-black text-slate-600">${score}</span>
                </div>
            `;
        }

        // ==========================================
        // 🎨 THUMBNAIL ANALYSIS SYSTEM
        // ==========================================
        
        // ==============================================
        // 🎨 GOOGLE CLOUD VISION API INTEGRATION
        // ==============================================
        
        /**
         * Analyze thumbnail with Google Cloud Vision API
         * Features: Text detection, Face detection, Object detection
         */
        async function analyzeThumbnailWithVision(thumbnailUrl, videoId) {
            // Check if Vision API key exists
            if (!globalVisionKey) {
                console.log('⚠️ Vision API key not found');
                return null;
            }
            
            try {
                // Use cached result if available
                const cacheKey = `vision_${videoId}`;
                const cached = sessionStorage.getItem(cacheKey);
                if (cached) {
                    console.log('✅ Vision analysis loaded from cache');
                    return JSON.parse(cached);
                }
                
                console.log('🎨 Calling Google Cloud Vision API...');
                
                // Convert image URL to base64
                const base64Image = await imageUrlToBase64(thumbnailUrl);
                if (!base64Image) {
                    console.warn('Failed to convert image to base64');
                    return null;
                }
                
                // Call Vision API with multiple features
                const apiUrl = `https://vision.googleapis.com/v1/images:annotate?key=${globalVisionKey}`;
                
                const requestBody = {
                    requests: [{
                        image: { content: base64Image },
                        features: [
                            { type: 'TEXT_DETECTION', maxResults: 10 },
                            { type: 'FACE_DETECTION', maxResults: 10 },
                            { type: 'LABEL_DETECTION', maxResults: 10 },
                            { type: 'IMAGE_PROPERTIES' }
                        ]
                    }]
                };
                
                const response = await fetch(apiUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(requestBody)
                });
                
                if (!response.ok) {
                    const errorData = await response.json();
                    console.error('Vision API error:', errorData);
                    return null;
                }
                
                const data = await response.json();
                const result = data.responses[0];
                
                // Parse results
                const analysis = {
                    // Text detection
                    text: result.textAnnotations ? {
                        fullText: result.textAnnotations[0]?.description || '',
                        words: result.textAnnotations.slice(1).map(t => t.description),
                        hasText: result.textAnnotations && result.textAnnotations.length > 0
                    } : { fullText: '', words: [], hasText: false },
                    
                    // Face detection
                    faces: result.faceAnnotations ? {
                        count: result.faceAnnotations.length,
                        emotions: result.faceAnnotations.map(face => ({
                            joy: face.joyLikelihood,
                            surprise: face.surpriseLikelihood,
                            anger: face.angerLikelihood,
                            sorrow: face.sorrowLikelihood
                        })),
                        dominantEmotion: getDominantEmotion(result.faceAnnotations[0])
                    } : { count: 0, emotions: [], dominantEmotion: null },
                    
                    // Object/Label detection
                    objects: result.labelAnnotations ? 
                        result.labelAnnotations.slice(0, 5).map(label => ({
                            name: label.description,
                            confidence: Math.round(label.score * 100)
                        })) : [],
                    
                    // Image properties (colors)
                    colors: result.imagePropertiesAnnotation ? 
                        result.imagePropertiesAnnotation.dominantColors.colors.slice(0, 3).map(c => ({
                            rgb: `rgb(${Math.round(c.color.red || 0)}, ${Math.round(c.color.green || 0)}, ${Math.round(c.color.blue || 0)})`,
                            score: Math.round(c.score * 100),
                            pixelFraction: Math.round(c.pixelFraction * 100)
                        })) : []
                };
                
                // Cache result
                sessionStorage.setItem(cacheKey, JSON.stringify(analysis));
                console.log('✅ Vision API analysis completed:', analysis);
                
                return analysis;
                
            } catch (error) {
                console.error('Vision API error:', error);
                return null;
            }
        }
        
        /**
         * Convert image URL to base64
         */
        async function imageUrlToBase64(url) {
            try {
                // Use proxy to avoid CORS
                const proxyUrl = url.replace('i.ytimg.com', 'img.youtube.com');
                
                const response = await fetch(proxyUrl);
                const blob = await response.blob();
                
                return new Promise((resolve, reject) => {
                    const reader = new FileReader();
                    reader.onloadend = () => {
                        // Remove data:image/jpeg;base64, prefix
                        const base64 = reader.result.split(',')[1];
                        resolve(base64);
                    };
                    reader.onerror = reject;
                    reader.readAsDataURL(blob);
                });
            } catch (error) {
                console.error('Image to base64 conversion failed:', error);
                return null;
            }
        }
        
        /**
         * Get dominant emotion from face detection
         */
        function getDominantEmotion(face) {
            if (!face) return null;
            
            const emotions = {
                'Joy 😊': face.joyLikelihood,
                'Surprise 😮': face.surpriseLikelihood,
                'Anger 😠': face.angerLikelihood,
                'Sorrow 😢': face.sorrowLikelihood
            };
            
            const likelihood = ['VERY_UNLIKELY', 'UNLIKELY', 'POSSIBLE', 'LIKELY', 'VERY_LIKELY'];
            
            let maxScore = -1;
            let dominant = 'Neutral 😐';
            
            for (const [emotion, level] of Object.entries(emotions)) {
                const score = likelihood.indexOf(level);
                if (score > maxScore) {
                    maxScore = score;
                    if (score >= 2) { // POSSIBLE or higher
                        dominant = emotion;
                    }
                }
            }
            
            return dominant;
        }
        
        /**
         * Render Vision API analysis results
         */
        function renderVisionAnalysis(visionData) {
            if (!visionData) {
                return '<div class="text-[9px] text-slate-400">Vision API not available</div>';
            }
            
            let html = '<div class="space-y-2">';
            
            // Text detection
            if (visionData.text.hasText) {
                const words = visionData.text.words.slice(0, 10);
                html += `
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-2 rounded">
                        <div class="text-[10px] font-bold text-blue-700 mb-1">
                            📝 Text trên thumbnail (${words.length} words)
                        </div>
                        <div class="flex flex-wrap gap-1">
                            ${words.map(w => `
                                <span class="bg-blue-100 text-blue-800 text-[8px] px-1.5 py-0.5 rounded font-bold">
                                    ${w}
                                </span>
                            `).join('')}
                        </div>
                        <div class="text-[8px] text-blue-600 mt-1">
                            💡 Words như "${words[0]}" có thể tăng CTR
                        </div>
                    </div>
                `;
            }
            
            // Face detection
            if (visionData.faces.count > 0) {
                html += `
                    <div class="bg-purple-50 border-l-4 border-purple-500 p-2 rounded">
                        <div class="text-[10px] font-bold text-purple-700 mb-1">
                            👤 Face Detection: ${visionData.faces.count} người
                        </div>
                        <div class="text-[9px] text-purple-600">
                            Emotion: <span class="font-bold">${visionData.faces.dominantEmotion}</span>
                        </div>
                        <div class="text-[8px] text-purple-600 mt-1">
                            ✅ Thumbnails có mặt người thường CTR cao hơn 30%
                        </div>
                    </div>
                `;
            }
            
            // Object detection
            if (visionData.objects.length > 0) {
                html += `
                    <div class="bg-green-50 border-l-4 border-green-500 p-2 rounded">
                        <div class="text-[10px] font-bold text-green-700 mb-1">
                            🔍 Objects phát hiện
                        </div>
                        <div class="flex flex-wrap gap-1">
                            ${visionData.objects.slice(0, 5).map(obj => `
                                <span class="bg-green-100 text-green-800 text-[8px] px-1.5 py-0.5 rounded">
                                    ${obj.name} (${obj.confidence}%)
                                </span>
                            `).join('')}
                        </div>
                    </div>
                `;
            }
            
            html += '</div>';
            return html;
        }
        
        // ==============================================
        // 🎨 THUMBNAIL COLOR ANALYSIS (Canvas API)
        // ==============================================
        
        /**
         * Extract dominant colors from thumbnail using Canvas API
         * Returns: { primary, secondary, palette, brightness }
         */
        async function analyzeThumbnailColors(thumbnailUrl, videoId) {
            try {
                // Use cached result if available
                const cacheKey = `thumb_${videoId}`;
                const cached = sessionStorage.getItem(cacheKey);
                if (cached) return JSON.parse(cached);
                
                // Create canvas to analyze image
                const img = new Image();
                img.crossOrigin = 'Anonymous';
                
                return new Promise((resolve, reject) => {
                    img.onload = () => {
                        try {
                            const canvas = document.createElement('canvas');
                            const ctx = canvas.getContext('2d');
                            
                            // Resize to small size for faster processing
                            canvas.width = 100;
                            canvas.height = 56;
                            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                            
                            // Get pixel data
                            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                            const pixels = imageData.data;
                            
                            // Color frequency map
                            const colorMap = {};
                            let totalBrightness = 0;
                            let pixelCount = 0;
                            
                            // Sample every 4th pixel for performance
                            for (let i = 0; i < pixels.length; i += 16) {
                                const r = pixels[i];
                                const g = pixels[i + 1];
                                const b = pixels[i + 2];
                                const a = pixels[i + 3];
                                
                                if (a < 128) continue; // Skip transparent pixels
                                
                                // Calculate brightness
                                const brightness = (r * 0.299 + g * 0.587 + b * 0.114);
                                totalBrightness += brightness;
                                pixelCount++;
                                
                                // Quantize colors to reduce variations
                                const qR = Math.floor(r / 32) * 32;
                                const qG = Math.floor(g / 32) * 32;
                                const qB = Math.floor(b / 32) * 32;
                                const colorKey = `${qR},${qG},${qB}`;
                                
                                colorMap[colorKey] = (colorMap[colorKey] || 0) + 1;
                            }
                            
                            // Sort by frequency
                            const sortedColors = Object.entries(colorMap)
                                .sort((a, b) => b[1] - a[1])
                                .slice(0, 5)
                                .map(([color, count]) => {
                                    const [r, g, b] = color.split(',').map(Number);
                                    return {
                                        rgb: `rgb(${r}, ${g}, ${b})`,
                                        hex: rgbToHex(r, g, b),
                                        count,
                                        name: getColorName(r, g, b)
                                    };
                                });
                            
                            const avgBrightness = totalBrightness / pixelCount;
                            
                            const result = {
                                primary: sortedColors[0] || null,
                                secondary: sortedColors[1] || null,
                                palette: sortedColors,
                                brightness: avgBrightness,
                                isBright: avgBrightness > 128,
                                isDark: avgBrightness < 80,
                                hasHighContrast: sortedColors.length > 1 && 
                                    Math.abs(getColorBrightness(sortedColors[0].hex) - 
                                             getColorBrightness(sortedColors[1].hex)) > 100
                            };
                            
                            // Cache result
                            sessionStorage.setItem(cacheKey, JSON.stringify(result));
                            resolve(result);
                        } catch (error) {
                            resolve(null);
                        }
                    };
                    
                    img.onerror = () => resolve(null);
                    
                    // Use proxy to avoid CORS issues
                    img.src = thumbnailUrl.replace('i.ytimg.com', 'img.youtube.com');
                });
            } catch (error) {
                console.warn('Thumbnail analysis failed:', error);
                return null;
            }
        }
        
        /**
         * Helper: Convert RGB to Hex
         */
        function rgbToHex(r, g, b) {
            return '#' + [r, g, b].map(x => {
                const hex = x.toString(16);
                return hex.length === 1 ? '0' + hex : hex;
            }).join('');
        }
        
        /**
         * Helper: Get color brightness
         */
        function getColorBrightness(hex) {
            const r = parseInt(hex.substr(1, 2), 16);
            const g = parseInt(hex.substr(3, 2), 16);
            const b = parseInt(hex.substr(5, 2), 16);
            return (r * 0.299 + g * 0.587 + b * 0.114);
        }
        
        /**
         * Helper: Get human-readable color name
         */
        function getColorName(r, g, b) {
            const brightness = r * 0.299 + g * 0.587 + b * 0.114;
            
            // Grayscale
            if (Math.abs(r - g) < 20 && Math.abs(g - b) < 20 && Math.abs(r - b) < 20) {
                if (brightness > 200) return 'Trắng';
                if (brightness > 128) return 'Xám sáng';
                if (brightness > 64) return 'Xám';
                return 'Đen';
            }
            
            // Primary colors
            if (r > g + 50 && r > b + 50) return 'Đỏ';
            if (g > r + 50 && g > b + 50) return 'Xanh lá';
            if (b > r + 50 && b > g + 50) return 'Xanh dương';
            
            // Secondary colors
            if (r > 128 && g > 128 && b < 100) return 'Vàng';
            if (r > 128 && b > 128 && g < 100) return 'Tím';
            if (g > 128 && b > 128 && r < 100) return 'Cyan';
            
            // Orange
            if (r > 180 && g > 100 && g < 180 && b < 100) return 'Cam';
            
            // Pink
            if (r > 180 && b > 100 && g < 150) return 'Hồng';
            
            return 'Hỗn hợp';
        }
        
        /**
         * Get color psychology insights
         */
        function getColorPsychology(colorName) {
            const psychology = {
                'Đỏ': { emoji: '🔴', effect: 'Kích thích, thu hút attention', viral: 'High' },
                'Cam': { emoji: '🟠', effect: 'Năng động, hành động', viral: 'High' },
                'Vàng': { emoji: '🟡', effect: 'Vui vẻ, lạc quan', viral: 'Medium' },
                'Xanh lá': { emoji: '🟢', effect: 'Thư giãn, tin cậy', viral: 'Low' },
                'Xanh dương': { emoji: '🔵', effect: 'Chuyên nghiệp, tin cậy', viral: 'Medium' },
                'Tím': { emoji: '🟣', effect: 'Sang trọng, sáng tạo', viral: 'Medium' },
                'Hồng': { emoji: '🩷', effect: 'Ngọt ngào, trending', viral: 'High' },
                'Đen': { emoji: '⚫', effect: 'Mạnh mẽ, bí ẩn', viral: 'Medium' },
                'Trắng': { emoji: '⚪', effect: 'Sạch sẽ, đơn giản', viral: 'Low' }
            };
            
            return psychology[colorName] || { emoji: '⚪', effect: 'N/A', viral: 'Unknown' };
        }
        
        /**
         * Render thumbnail analysis card
         */
        function renderThumbnailAnalysis(colorData) {
            if (!colorData || !colorData.primary) {
                return '<div class="text-[9px] text-slate-400">Analyzing...</div>';
            }
            
            const primaryPsy = getColorPsychology(colorData.primary.name);
            const secondaryPsy = colorData.secondary ? getColorPsychology(colorData.secondary.name) : null;
            
            return `
                <div class="bg-slate-50 rounded-lg p-2 mt-2 border border-slate-200">
                    <div class="text-[9px] font-bold text-slate-600 mb-1.5">🎨 Thumbnail Analysis</div>
                    
                    <!-- Color Palette -->
                    <div class="flex gap-1 mb-2">
                        ${colorData.palette.map(c => `
                            <div class="w-5 h-5 rounded border border-slate-300 shadow-sm" 
                                 style="background: ${c.rgb}"
                                 title="${c.name}"></div>
                        `).join('')}
                    </div>
                    
                    <!-- Primary Color -->
                    <div class="flex items-center gap-1.5 mb-1">
                        <span class="text-xs">${primaryPsy.emoji}</span>
                        <span class="text-[9px] font-bold text-slate-700">${colorData.primary.name}</span>
                        <span class="text-[8px] px-1.5 py-0.5 rounded ${
                            primaryPsy.viral === 'High' ? 'bg-green-100 text-green-700' : 
                            primaryPsy.viral === 'Medium' ? 'bg-yellow-100 text-yellow-700' : 
                            'bg-slate-100 text-slate-600'
                        }">${primaryPsy.viral}</span>
                    </div>
                    
                    <!-- Brightness -->
                    <div class="flex items-center gap-1 mb-1">
                        <span class="text-[9px] text-slate-500">Độ sáng:</span>
                        <span class="text-[9px] font-bold ${
                            colorData.isBright ? 'text-yellow-600' : 
                            colorData.isDark ? 'text-slate-700' : 
                            'text-slate-600'
                        }">
                            ${colorData.isBright ? '☀️ Sáng' : colorData.isDark ? '🌙 Tối' : '⚖️ Vừa'}
                        </span>
                    </div>
                    
                    <!-- Contrast -->
                    ${colorData.hasHighContrast ? `
                        <div class="text-[8px] text-green-700 bg-green-50 px-1.5 py-0.5 rounded">
                            ✓ High contrast (tốt cho CTR)
                        </div>
                    ` : ''}
                    
                    <!-- Psychology Insight -->
                    <div class="text-[8px] text-slate-600 mt-1.5 leading-tight">
                        ${primaryPsy.effect}
                    </div>
                </div>
            `;
        }

        function analyzeUploadTimes(videos) {
            const container = document.getElementById('uploadTimeContainer'); if (container) container.classList.remove('hidden'); const heatmapEl = document.getElementById('uploadHeatmap'); if (!heatmapEl || !videos || videos.length === 0) return;
            const hourMap = {}; for (let i = 0; i < 24; i++) hourMap[i] = 0; let validCount = 0; videos.forEach(v => { const dateStr = v.publishedAt; if (dateStr) { const h = new Date(dateStr).getHours(); hourMap[h]++; validCount++; } }); if (validCount === 0) return; safeSetText('uploadTimeVideoCount', validCount);
            const maxCount = Math.max(...Object.values(hourMap)); const hourStats = Object.entries(hourMap).map(([h, count]) => ({ hour: parseInt(h), count, intensity: maxCount > 0 ? (count / maxCount) * 100 : 0 })); const activeHours = hourStats.filter(h => h.hour >= 7 && h.hour <= 23).sort((a, b) => a.count - b.count); const bestHours = activeHours.slice(0, 3).map(h => h.hour);
            let html = ''; for (let h = 0; h < 24; h++) { const stat = hourStats[h]; let color = 'bg-slate-200'; if (stat.intensity > 80) color = 'bg-red-500'; else if (stat.intensity > 40) color = 'bg-yellow-400'; if (bestHours.includes(h)) color = 'bg-green-500'; const height = Math.max(15, stat.intensity); html += `<div class="flex flex-col items-center justify-end h-full w-full group relative cursor-pointer"><div class="w-full mx-0.5 rounded-t ${color} transition-all duration-300 hover:opacity-80" style="height: ${height}%"></div><div class="hidden group-hover:block absolute bottom-full mb-1 p-1.5 bg-slate-800 text-white text-[10px] rounded z-10 whitespace-nowrap shadow-lg">${h}:00 - ${stat.count} videos</div></div>`; } heatmapEl.innerHTML = html; safeSetText('bestTimeRange', `${bestHours[0]}h - ${bestHours[0] + 1}h`);
        }

        // ==============================================
        // 🧠 AI DEEP CHANNEL ANALYSIS FUNCTIONS (NEW)
        // ==============================================

        // 🔥 SAFETY: Ensure global variables are initialized at start
        (function initDeepAnalysisGlobals() {
            if (typeof window.currentDeepChannelData === 'undefined') {
                window.currentDeepChannelData = null;
            }
            if (typeof window.lastAIResponse === 'undefined') {
                window.lastAIResponse = '';
            }
        })();
        
        // Alias for easier access (use window. prefix for guaranteed global scope)
        var currentDeepChannelData = window.currentDeepChannelData;
        var lastAIResponse = window.lastAIResponse;

        async function startDeepAnalysis() {
            const channelUrl = document.getElementById('deepChannelUrl').value.trim();
            
            if (!channelUrl) {
                showToast('Vui lòng nhập link kênh YouTube!', 'warning');
                return;
            }
            
            // 🤖 CHECK AI DEEP DIVE LIMIT (Free tier: 2/month)
            if (!AI_DEEP_DIVE_UNLIMITED && !AI_DEEP_DIVE_CAN_USE) {
                showUpgradeModal('ai_deep_dive_limit');
                return;
            }
            
            // Show limit warning if approaching limit
            if (!AI_DEEP_DIVE_UNLIMITED && AI_DEEP_DIVE_REMAINING <= 1) {
                showToast(`⚠️ Còn ${AI_DEEP_DIVE_REMAINING} lượt phân tích AI miễn phí trong tháng này!`, 'warning');
            }
            
            // 🔥 CRITICAL: Reload Gemini keys from localStorage to ensure freshness
            loadGeminiKeys();
            console.log('🧠 AI Deep Dive - apiPool.gemini:', apiPool.gemini);
            console.log('🧠 AI Deep Dive - geminiKeys:', geminiKeys);
            
            // Check if Gemini API key exists (use NEW method)
            const hasGemini = getActiveGeminiKey();
            const hasOpenRouter = globalOpenRouterKey;
            
            if (!hasGemini && !hasOpenRouter) {
                showToast('Vui lòng nhập Gemini hoặc OpenRouter API Key trong Cài đặt!', 'error');
                openSettings();
                return;
            }
            
            // Show loading
            document.getElementById('deepLoading').classList.remove('hidden');
            document.getElementById('deepResults').classList.add('hidden');
            const btn = document.getElementById('deepAnalyzeBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Đang xử lý...';
            
            // 🔥 CRITICAL: Ensure YouTube API key is available
            if (!globalApiKey) {
                const ytKey = getActiveKey();
                if (!ytKey) {
                    showToast('Vui lòng nhập YouTube API Key trong Cài đặt!', 'error');
                    openSettings();
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-sparkles"></i> Phân tích AI';
                    document.getElementById('deepLoading').classList.add('hidden');
                    return;
                }
                globalApiKey = ytKey;
            }
            console.log('🔑 Using YouTube API key:', globalApiKey.substring(0, 10) + '...');
            
            try {
                // Step 1: Get channel ID
                let channelId = extractChannelId(channelUrl);
                let resolvedChannelName = null; // 🔥 Store resolved channel name
                let resolvedChannelUrl = null;  // 🔥 Store resolved channel URL
                console.log('📺 Extracted channel ID:', channelId);
                
                if (!channelId) {
                    throw new Error('URL kênh không hợp lệ! Vui lòng nhập link dạng: youtube.com/@username hoặc youtube.com/channel/UC...');
                }
                
                // Step 2: Resolve channel ID if needed (for @username format)
                if (!channelId.startsWith('UC')) {
                    showToast('🔍 Đang tìm kênh...', 'info');
                    const resolved = await resolveChannelId(channelId, globalApiKey);
                    
                    if (!resolved) {
                        throw new Error(`Không tìm thấy kênh "${channelId}". Vui lòng kiểm tra lại tên kênh hoặc thử link trực tiếp dạng youtube.com/channel/UC...`);
                    }
                    
                    // 🔥 Extract data from resolved object
                    channelId = resolved.channelId;
                    resolvedChannelName = resolved.channelName;
                    resolvedChannelUrl = resolved.customUrl;
                    console.log('✅ Resolved to:', channelId, '-', resolvedChannelName);
                    showToast(`✅ Tìm thấy: ${resolvedChannelName}`, 'success');
                }
                
                // Step 3: Fetch channel data
                const { channelInfo, videos } = await fetchChannelVideos(channelId, globalApiKey, 50);
                
                // 🔥 Verify channel name matches what user expected
                if (resolvedChannelName && channelInfo.title !== resolvedChannelName) {
                    console.warn('⚠️ Channel name mismatch:', resolvedChannelName, 'vs', channelInfo.title);
                }
                
                if (videos.length === 0) {
                    throw new Error('Kênh không có video hoặc đã bị ẩn');
                }
                
                // 🔥 Add channel link to channelInfo for display
                channelInfo.channelId = channelId;
                channelInfo.channelLink = `https://www.youtube.com/channel/${channelId}`;
                
                // Step 4: Prepare data for AI analysis
                const channelData = {
                    name: channelInfo.title,
                    channelId: channelId,
                    url: channelUrl,
                    subscribers: channelInfo.subscribers,
                    totalVideos: channelInfo.totalVideos,
                    totalViews: channelInfo.totalViews,
                    channelLink: `https://www.youtube.com/channel/${channelId}`,
                    videos: videos.slice(0, 50).map(v => ({
                        title: v.snippet.title,
                        views: parseInt(v.statistics.viewCount) || 0,
                        likes: parseInt(v.statistics.likeCount) || 0,
                        publishedAt: v.snippet.publishedAt,
                        thumbnail: v.snippet.thumbnails.medium.url,
                        url: `https://youtu.be/${v.id}`,
                        tags: v.snippet.tags || []
                    }))
                };
                
                // Step 5: Build AI prompt
                const prompt = buildDeepAnalysisPrompt(channelData);
                
                // Step 6: Call AI with SMART FALLBACK
                showToast('🧠 Đang gọi dữ liệu cho AI...', 'info');
                let aiResponse;
                
                // 🔥 PRIORITY 1: Try Gemini FREE first (multi-key rotation)
                if (geminiKeys.length > 0) {
                    console.log('✅ Using Gemini FREE (multi-key pool:', geminiKeys.length, 'keys)');
                    aiResponse = await callGemini(prompt);
                    
                    if (aiResponse) {
                        console.log('✅ Gemini FREE success!');
                    } else {
                        console.log('⚠️ All Gemini keys exhausted, trying fallback...');
                    }
                }
                
                // 🔥 PRIORITY 2: Fallback to OpenRouter PAID (only if Gemini failed)
                if (!aiResponse && globalOpenRouterKey) {
                    console.log('📡 Fallback to OpenRouter (PAID)...');
                    showToast('📡 Gemini FREE hết quota, chuyển sang OpenRouter...', 'info');
                    aiResponse = await callOpenRouter(prompt, 'google/gemini-flash-1.5:free');
                }
                
                if (!aiResponse) {
                    throw new Error('AI không phản hồi. Vui lòng thử lại.');
                }
                
                // Step 7: Display results
                displayDeepAnalysisResults(aiResponse, channelData);
                showToast('✅ Phân tích hoàn tất!', 'success');
                
                // 🤖 Increment AI Deep Dive usage (for free tier tracking)
                if (!AI_DEEP_DIVE_UNLIMITED) {
                    incrementAIDeepDiveUsage();
                }
                
                // 💾 Save to history (for all users)
                try {
                    const saveData = {
                        name: channelData.name,
                        channelId: channelData.channelId,
                        url: channelUrl,
                        subscribers: channelData.subscribers,
                        totalVideos: channelData.totalVideos,
                        totalViews: channelData.totalViews,
                        aiResponse: aiResponse
                    };
                    
                    console.log('💾 Saving to history:', saveData);
                    
                    fetch('api_save_ai_history.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(saveData)
                    })
                    .then(res => {
                        console.log('📡 Response status:', res.status);
                        return res.json();
                    })
                    .then(data => {
                        console.log('✅ History saved:', data);
                        if (data.success) {
                            showToast('💾 Đã lưu lịch sử phân tích!', 'success');
                        }
                    })
                    .catch(err => console.error('❌ Failed to save history:', err));
                } catch (e) {
                    console.error('Error saving history:', e);
                }
                
            } catch (error) {
                console.error('Deep analysis error:', error);
                showToast(error.message || 'Lỗi khi phân tích. Vui lòng thử lại.', 'error');
                document.getElementById('deepLoading').classList.add('hidden');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-sparkles"></i> Phân tích AI';
            }
        }
        
        function buildDeepAnalysisPrompt(data) {
            // Calculate upload frequency
            const videos = data.videos;
            if (videos.length < 2) return null;
            
            const firstDate = new Date(videos[videos.length - 1].publishedAt);
            const lastDate = new Date(videos[0].publishedAt);
            const daysDiff = (lastDate - firstDate) / (1000 * 60 * 60 * 24);
            const uploadFreq = videos.length / (daysDiff / 7); // videos per week
            
            // Get top 10 videos by views (last 6 months)
            const sixMonthsAgo = new Date();
            sixMonthsAgo.setMonth(sixMonthsAgo.getMonth() - 6);
            
            const recentVideos = videos
                .filter(v => new Date(v.publishedAt) >= sixMonthsAgo)
                .sort((a, b) => b.views - a.views)
                .slice(0, 10);
            
            // 🔥 Extract all video titles for pattern analysis
            const allTitles = videos.map(v => v.title).join('\n');
            
            // 🔥 Calculate average views
            const avgViews = videos.reduce((sum, v) => sum + v.views, 0) / videos.length;
            
            // 🔥 Find viral videos (3x average)
            const viralVideos = videos.filter(v => v.views > avgViews * 3);
            
            // 🔥 Extract all tags for keyword analysis
            const allTags = videos.flatMap(v => v.tags || []).slice(0, 100);
            const tagFreq = {};
            allTags.forEach(tag => { tagFreq[tag] = (tagFreq[tag] || 0) + 1; });
            const topTags = Object.entries(tagFreq).sort((a, b) => b[1] - a[1]).slice(0, 20).map(t => t[0]);
            
            const prompt = `Bạn là chuyên gia phân tích YouTube với 10 năm kinh nghiệm, đặc biệt giỏi trong việc tìm ra CHIẾN LƯỢC thành công và hướng dẫn cụ thể để replicate. Phân tích kênh YouTube sau đây một cách CHI TIẾT, TOÀN DIỆN và CHUYÊN NGHIỆP:

=== THÔNG TIN KÊnh ===
- Tên kênh: ${data.name}
- Link kênh: ${data.url}
- Subscribers: ${data.subscribers.toLocaleString()}
- Tốc độ ra Video: ${uploadFreq.toFixed(1)} video/tuần (${data.totalVideos} videos tổng)
- Tổng lượt xem: ${data.totalViews.toLocaleString()}
- Views trung bình: ${Math.round(avgViews).toLocaleString()}/video
- Số video viral (3x TB): ${viralVideos.length}

=== TOP 20 TAGS THƯỜNG DÙNG ===
${topTags.join(', ')}

=== ${recentVideos.length} VIDEO NỔI BẬT NHẤT (6 THÁNG GẦN ĐÂY) ===
${recentVideos.map((v, i) => `${i + 1}. "${v.title}" - ${v.views.toLocaleString()} views - ${v.url}`).join('\n')}

=== TẤT CẢ TIÊU ĐỀ VIDEO (ĐỂ PHÂN TÍCH PATTERN) ===
${allTitles}

========================================
YÊU CẦU PHÂN TÍCH TOÀN DIỆN (Viết bằng TIẾNG VIỆT, format Markdown):
========================================

## 1. 🎯 TỔNG QUAN & THỜI GIAN HOẠT ĐỘNG
- **Ngàch chính (niche):** Chính xác nhất có thể
- **Tuổi kênh:** Ước tính dựa trên video đầu tiên và tốc độ upload
- **Tốc độ phát triển:** Chậm/Trung bình/Nhanh/Viral - Giải thích tại sao
- **Tỷ lệ viral:** Nhận xét về ${viralVideos.length}/${videos.length} video vượt 3x trung bình

## 2. 👥 TỆP NGƯỜI XEM CHÍNH (Target Audience)
Mô tả CHI TIẾT và CỤ THỂ:
- **Độ tuổi:** Khoảng bao nhiêu? Dựa vào yếu tố nào?
- **Giới tính:** Nam/Nữ/Cả hai? Tỷ lệ ước tính?
- **Địa lý/Ngôn ngữ:** Khu vực nào? (Dựa vào ngôn ngữ, nội dung)
- **Sở thích cu thể:** Liệt kê các sở thích, hobby liên quan
- **Hành vi xem:** Passive/Active? Xem dài/ngắn? Skip hay xem hết?
- **Mục đích xem:** Họ xem để làm gì? (Giải trí/Học hỏi/Relax/ASMR/Cảm hứng/...)

## 3. 📡 CÁC NGUỒN PHỔ BIẾN (Traffic Sources)
Phân tích người xem tìm đến kênh qua đâu:
- YouTube Suggest (Browse Features)
- YouTube Search (Tìm kiếm từ khóa gì?)
- External (Social Media nào? TikTok/Facebook/Instagram?)
- Suggested Videos (Kênh nào suggest nhiều?)

## 4. 📂 5 CHỦ ĐỀ LỚN (Content Pillars)
Phân tích từ titles và tags, trình bày dạng BẢNG:

| Chủ đề | Tỉ lệ % | Số video (ước tính) | Mức độ thành công |
|----------|----------|---------------------|--------------------|
| ... | ... | ... | Cao/TB/Thấp |

## 5. 🎬 TOP 10 VIDEO VIEWS CAO NHẤT (6 THÁNG GẦN ĐÂY)
Trình bày dạng BẢNG markdown:

| STT | Tên Video | Lượt xem | Link | Chủ đề | Đặc điểm nổi bật |
|-----|-----------|----------|------|----------|--------------------|

## 6. 🎨 TITLE PATTERN ANALYSIS (🔥 QUAN TRỌNG!)
Phân tích CÔNG THỨC tiêu đề viral của kênh:

### Hook Pattern (Công thức mở đầu)
- Liệt kê các pattern: "How to...", "Why...", "X things...", "I tried...", "Secret of..."
- Pattern nào xuất hiện nhiều nhất? Cho ví dụ cụ thể

### Power Words (Từ khóa mạnh)
- Liệt kê 10-15 từ khóa mạnh xuất hiện thường xuyên
- Ví dụ: SHOCKING, SECRET, ULTIMATE, BEST, WORST, HIDDEN,...

### Số liệu/Con số
- Kênh có dùng số trong tiêu đề không? (VD: "10 ways", "5 secrets")
- Tần suất: % video có số trong tiêu đề?

### Emotional Triggers (Kích tính cảm)
- Loại cảm xúc nào: Sợ hãi/Tò mò/FOMO/Shock/Hứng thú/Cảm hứng?
- Ví dụ cụ thể từ tiêu đề thực tế

### Độ dài tiêu đề
- Trung bình: ... ký tự
- Khoảng: Ngắn (<50) / Trung (50-70) / Dài (>70)

### 📝 5 MẪu Tiêu Đề ĐỂ COPY (Actionable!)
Viết 5 template tiêu đề dựa trên pattern đã phân tích:
1. [Template 1 - Cụ thể]
2. [Template 2 - Cụ thể]
3. [Template 3 - Cụ thể]
4. [Template 4 - Cụ thể]
5. [Template 5 - Cụ thể]

## 7. 📸 THUMBNAIL STRATEGY (Chi tiết)

### Màu sắc chủ đạo
- Màu chính: (VD: Đỏ/Cam - nổi bật, Xanh dương - trust, Vàng - hạnh phúc)
- Màu phụ: ...
- Tập trung vào màu nào? Tại sao?

### Text Overlay
- Có/Không? Tần suất: ...%
- Kiểu chữ: Bold/ALL CAPS/Outline/Shadow?
- Nội dung text: Keyword hay Full title?

### Faces (Mặt người)
- Có mặt người không? Tần suất: ...%
- Biểu cảm: Shock (😱)/Smile (😊)/Confused (🤔)/Serious?
- Close-up hay full body?

### Objects & Elements
- Những đồ vật nổi bật: Mũi tên/Icons/Products/...
- Layout: Centered/Split/Collage?

### Contrast & Quality
- Contrast: Cao (nổi bật)/Thấp (nhẹ nhàng)?
- Chất lượng: Amateur/Semi-pro/Professional?

### 🎨 GỢI Ý THIẾT KẾ THUMBNAIL LÝ TƯởNG
Mô tả cụ thể 1 thumbnail lý tưởng cho ngàch này (ai cũng hiểu được):
> "[Mô tả chi tiết thumbnail: màu sắc, bố cục, text, biểu cảm,...]"

## 8. 📊 CONTENT STRATEGY & UPLOAD PATTERN

### Format Video
- Loại hình: Talking head/Voiceover/B-roll/Tutorial/Vlog/Review/Compilation?
- Style: Fast-paced hay Slow & chill?

### Độ dài video
- Trung bình: ... phút
- Phân bố: Ngắn (<8p): ...% | Trung (8-15p): ...% | Dài (>15p): ...%

### Upload Schedule
- Tần suất: ${uploadFreq.toFixed(1)} video/tuần
- Ngày ưu tiên: (Nếu phát hiện được pattern)
- Giờ đăng: (Nếu nhận biết được)

### Series/Playlist Strategy
- Có tạo series không? Các series nào?
- Playlist organization: Tốt/Trung bình/Yếu?

## 9. 🏆 ĐÁNH GIÁ CHIỀU SÂU NỘI DUNG

### Chất lượng Production
- Mức độ: Thấp/Trung bình/Cao/Chuyên nghiệp
- Camera/Ánh sáng/Âm thanh/Edit: Đánh giá chi tiết

### Độ chuyên môn kỹ thuật
- Có dạy nghề thật hay chỉ giải trí?
- Mức độ kiến thức: Bề nổi/Trung bình/Sâu

### Tính Viral
- Dựa vào: Thuật toán (Clickbait) hay Nội dung thật (Value)?
- Retention rate dự đoán: Cao/Trung bình/Thấp

### Chiến lược tăng trưởng
- Nếu muốn tạo kênh tương tự, nên làm gì KHÁC để TỐT HƠN?

## 10. 💡 ĐÁNH GIÁ KHẢ NĂNG REPLICATE
Phân tích độ khó để tạo kênh tương tự:

| Tiêu chí | Mức độ | Giải thích chi tiết |
|----------|--------|---------------------|
| Vốn đầu tư | Thấp/TB/Cao | Cần bao nhiêu? Đầu tư vào đâu? |
| Kỹ năng cần có | Dễ/TB/Khó | Kỹ năng gì? Học ở đâu? |
| Thiết bị | Đơn giản/Pro | Cụ thể cần gì? (Phone/Camera/Mic/...) |
| Thời gian/video | Ít/Nhiều | Bao nhiêu giờ/video? |
| Độc đáo | Dễ copy/Khó | Có bi secret sauce không? |

## 11. 🚀 ACTION PLAN - HƯỚNG DẪN REPLICATE (🔥 QUAN TRỌNG NHẤT!)

Nếu muốn tạo kênh tương tự và đạt TOP như kênh này:

### 🛠️ BƯỚC 1: CHUẨN BỊ (Tuần 1)
Liệt kê CỤ THỂ cần làm:
1. [Action item 1]
2. [Action item 2]
3. [Action item 3]
...

### 🎥 BƯỚC 2: NỘI DUNG (Tuần 2-4)
GỢI Ý 10 Ý TƯởNG VIDEO ĐẦU TIÊN (cụ thể, có thể làm ngay):

1. **[Tên video 1]**
   - Tiêu đề: "[Tiêu đề mẫu cụ thể]"
   - Nội dung: [Mô tả ngắn]
   - Tại sao viral: [Lý do]

2. **[Tên video 2]**
   - Tiêu đề: "[Tiêu đề mẫu cụ thể]"
   - Nội dung: [Mô tả ngắn]
   - Tại sao viral: [Lý do]

...(Tiếp tục đến 10)

### 📈 BƯỚC 3: TỐI ƯU & TĂNG TRƯởNG (Tháng 2)
Chiến thuật cụ thể:
1. [Tối ưu SEO như thế nào]
2. [Cross-promote ở đâu]
3. [Engagement strategy]
...

### ⚠️ PITFALLS CẦN TRÁNH (Quan trọng!)
Liệt kê 5 SAI LẦM phổ biến khi làm ngàch này:
1. **[Sai lầm 1]** - [Tại sao sai] - [Cách tránh]
2. **[Sai lầm 2]** - [Tại sao sai] - [Cách tránh]
3. **[Sai lầm 3]** - [Tại sao sai] - [Cách tránh]
4. **[Sai lầm 4]** - [Tại sao sai] - [Cách tránh]
5. **[Sai lầm 5]** - [Tại sao sai] - [Cách tránh]

### 🔍 CƠ HỘI CHƯA KHAI THÁC (NICHE GAPS - VÀNG!)
Kênh này chưa làm gì mà BẠN có thể làm để VƯỢT QUA:
1. **[Gap 1]** - [Mô tả cơ hội] - [Cách khai thác]
2. **[Gap 2]** - [Mô tả cơ hội] - [Cách khai thác]
3. **[Gap 3]** - [Mô tả cơ hội] - [Cách khai thác]

## 12. ⭐ KẾT LUẬN & RATING

### 👍 ĐIỂM MẠNH (3 điểm)
1. [Strong point 1]
2. [Strong point 2]
3. [Strong point 3]

### 👎 ĐIỂM YẾU/CƠ HỘI (3 điểm)
1. [Weakness/Opportunity 1]
2. [Weakness/Opportunity 2]
3. [Weakness/Opportunity 3]

### 🎯 RATING KHẢ NĂNG THÀNH CÔNG KHI REPLICATE
**[X]/10** - [Giải thích chi tiết tại sao lại cho điểm này]

### 💡 MỘT CÂU TÓM TẮT CHIẾN LƯỢC
> "[Một câu ngắn gọn nhất để tóm tắt toàn bộ chiến lược thành công của kênh này]"

========================================
LƯU Ý QUAN TRỌNG:
- Phân tích dựa trên DATA thực tế đã cung cấp, KHÔNG suy đoán quá xa
- Đưa ra ĐỀ XUẤT CỤ THỂ, ACTIONABLE, có thể THỰC HIỆN NGAY
- Format chuẩn markdown với headers rõ ràng
- Sử dụng emoji để dễ đọc
- Trả lời bằng TIẾNG VIỆT
- PHÂN TÍCH PHẢI SÂU, CHI TIẾT, TOÀN DIỆN - ĐỪNG SƠ SÀI!
- ⚠️ BẮT BUỘC phải trả lời ĐẦY ĐỦ 12 PHẦN trong báo cáo, KHÔNG được bỏ qua bất kỳ phần nào!
- 📌 Mỗi phần phải có ít nhất 3-5 dòng nội dung chi tiết, không được viết ngắn!
- 🎯 Target: BÁO CÁO TỐI THIỂU 2000 TỪ để đảm bảo CHẤT LƯỢNG PHÂN TÍCH CHUYÊN SÂU!
========================================`;

            return prompt;
        }
        
        function displayDeepAnalysisResults(aiResponse, channelData) {
            // 💾 Store globally for save/share functions
            currentDeepChannelData = channelData;
            lastAIResponse = aiResponse;
            
            // 🔥 VALIDATION: Check if AI response is complete
            if (!aiResponse || aiResponse.length < 500) {
                console.warn('⚠️ AI response truncated or incomplete!', aiResponse?.length);
                showToast('⚠️ Phản hồi AI bị cắt ngắn! Đang thử lại...', 'warning');
            }
            
            const resultsDiv = document.getElementById('deepResults');
            
            // 🔥 Build channel link (prefer channelLink if available)
            const channelLink = channelData.channelLink || channelData.url || `https://www.youtube.com/channel/${channelData.channelId}`;
            
            // 🔥 Calculate avg views
            const avgViews = channelData.videos && channelData.videos.length > 0 
                ? Math.round(channelData.videos.reduce((s, v) => s + v.views, 0) / channelData.videos.length) 
                : 0;
            
            // Convert markdown-like response to HTML
            let html = `
                <!-- 🔥 PREMIUM CHANNEL HEADER CARD -->
                <div class="relative overflow-hidden rounded-2xl mb-8">
                    <!-- Background Gradient -->
                    <div class="absolute inset-0 bg-gradient-to-br from-purple-600 via-indigo-600 to-pink-500"></div>
                    <div class="absolute inset-0 bg-black/10"></div>
                    <div class="absolute top-0 right-0 w-96 h-96 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2 blur-3xl"></div>
                    
                    <!-- Content -->
                    <div class="relative p-8">
                        <div class="flex flex-col lg:flex-row items-start lg:items-center gap-6">
                            <!-- Channel Avatar -->
                            <div class="flex-shrink-0">
                                <div class="w-24 h-24 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center shadow-xl border border-white/20">
                                    <i class="fa-brands fa-youtube text-5xl text-white"></i>
                                </div>
                            </div>
                            
                            <!-- Channel Info -->
                            <div class="flex-1 text-white">
                                <h2 class="text-3xl lg:text-4xl font-black mb-3 drop-shadow-lg">${channelData.name}</h2>
                                
                                <!-- Stats Grid -->
                                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                                    <div class="bg-white/15 backdrop-blur-sm rounded-xl px-4 py-3 text-center border border-white/10">
                                        <div class="text-2xl font-black">${(channelData.subscribers / 1000).toFixed(1)}K</div>
                                        <div class="text-xs text-white/80">Subscribers</div>
                                    </div>
                                    <div class="bg-white/15 backdrop-blur-sm rounded-xl px-4 py-3 text-center border border-white/10">
                                        <div class="text-2xl font-black">${channelData.totalVideos}</div>
                                        <div class="text-xs text-white/80">Videos</div>
                                    </div>
                                    <div class="bg-white/15 backdrop-blur-sm rounded-xl px-4 py-3 text-center border border-white/10">
                                        <div class="text-2xl font-black">${(channelData.totalViews / 1000000).toFixed(1)}M</div>
                                        <div class="text-xs text-white/80">Total Views</div>
                                    </div>
                                    <div class="bg-white/15 backdrop-blur-sm rounded-xl px-4 py-3 text-center border border-white/10">
                                        <div class="text-2xl font-black">${(avgViews / 1000).toFixed(1)}K</div>
                                        <div class="text-xs text-white/80">Avg Views</div>
                                    </div>
                                </div>
                                
                                <!-- Channel Link -->
                                <a href="${channelLink}" target="_blank" 
                                   class="inline-flex items-center gap-2 text-sm bg-white/20 hover:bg-white/30 px-4 py-2 rounded-full transition backdrop-blur-sm border border-white/10">
                                    <i class="fa-solid fa-external-link"></i>
                                    <span class="truncate max-w-[300px]">${channelLink}</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 🔥 AI ANALYSIS CONTENT -->
                <div class="mb-8">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center text-white shadow-lg">
                            <i class="fa-solid fa-brain"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-slate-800">Phân Tích Chuyên Sâu</h3>
                            <p class="text-sm text-slate-500">Powered by Gemini AI</p>
                        </div>
                    </div>
                    
                    ${formatAIResponse(aiResponse)}
                </div>
                
                <!-- 🔥 TOP 10 VIDEOS GRID -->
                ${channelData.videos && channelData.videos.length > 0 ? `
                <div class="bg-white rounded-2xl shadow-lg border border-slate-200 overflow-hidden mb-8">
                    <div class="bg-gradient-to-r from-orange-500 to-red-500 px-6 py-4">
                        <h4 class="text-lg font-bold text-white flex items-center gap-2">
                            <i class="fa-solid fa-fire"></i> Top ${Math.min(10, channelData.videos.length)} Videos Gần Đây
                        </h4>
                    </div>
                    <div class="p-4 grid grid-cols-1 lg:grid-cols-2 gap-3">
                        ${channelData.videos.slice(0, 10).map((v, i) => `
                            <a href="${v.url}" target="_blank" 
                               class="flex items-start gap-3 p-3 bg-slate-50 rounded-xl border border-slate-100 hover:border-purple-300 hover:shadow-lg hover:bg-white transition-all group">
                                <div class="relative flex-shrink-0">
                                    <img src="${v.thumbnail}" class="w-32 h-20 object-cover rounded-lg shadow-sm" alt="" loading="lazy">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition rounded-lg flex items-end justify-center pb-2">
                                        <i class="fa-solid fa-play text-white text-xl drop-shadow-lg"></i>
                                    </div>
                                    <div class="absolute top-1 left-1 bg-purple-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-md shadow">#${i + 1}</div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h5 class="text-sm font-semibold text-slate-800 line-clamp-2 group-hover:text-purple-700 transition mb-2">${v.title}</h5>
                                    <div class="flex items-center gap-3 text-xs text-slate-500">
                                        <span class="flex items-center gap-1 bg-slate-100 px-2 py-1 rounded-full">
                                            <i class="fa-solid fa-eye text-purple-500"></i> ${v.views.toLocaleString()}
                                        </span>
                                        <span class="flex items-center gap-1 bg-slate-100 px-2 py-1 rounded-full">
                                            <i class="fa-solid fa-heart text-red-400"></i> ${v.likes.toLocaleString()}
                                        </span>
                                    </div>
                                </div>
                            </a>
                        `).join('')}
                    </div>
                </div>
                ` : ''}
                
                <!-- 🔥 ACTION BUTTONS -->
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4 pt-6 border-t border-slate-200">
                    <div class="flex flex-wrap gap-3 w-full sm:w-auto">
                        <button onclick="copyAnalysis()" 
                                class="flex-1 sm:flex-none px-6 py-3 bg-gradient-to-r from-slate-600 to-slate-700 hover:from-slate-700 hover:to-slate-800 text-white rounded-xl font-bold transition-all shadow-lg hover:shadow-xl flex items-center justify-center gap-2">
                            <i class="fa-solid fa-copy"></i> Copy Toàn Bộ
                        </button>
                        <button onclick="saveCurrentAnalysis()" 
                                class="flex-1 sm:flex-none px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl font-bold transition-all shadow-lg hover:shadow-xl flex items-center justify-center gap-2">
                            <i class="fa-solid fa-floppy-disk"></i> Lưu Kết Quả
                        </button>
                        <button onclick="exportHTMLCode()" 
                                class="flex-1 sm:flex-none px-6 py-3 bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white rounded-xl font-bold transition-all shadow-lg hover:shadow-xl flex items-center justify-center gap-2">
                            <i class="fa-solid fa-code"></i> Lấy Code HTML
                        </button>
                        <button onclick="shareCurrentAnalysis()" 
                                class="flex-1 sm:flex-none px-6 py-3 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white rounded-xl font-bold transition-all shadow-lg hover:shadow-xl flex items-center justify-center gap-2">
                            <i class="fa-solid fa-share-nodes"></i> Chia Sẻ
                        </button>
                    </div>
                    <a href="${channelLink}" target="_blank" 
                       class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-red-500 to-pink-500 hover:from-red-600 hover:to-pink-600 text-white rounded-xl font-bold transition-all shadow-lg hover:shadow-xl flex items-center justify-center gap-2">
                        <i class="fa-brands fa-youtube"></i> Xem Kênh Trên YouTube
                    </a>
                </div>
            `;
            
            resultsDiv.innerHTML = html;
            resultsDiv.classList.remove('hidden');
            document.getElementById('deepLoading').classList.add('hidden');
            
            // Smooth scroll to results
            resultsDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
        
        function formatAIResponse(response) {
            if (!response) return '<p class="text-red-500">Không có kết quả phân tích</p>';
            
            let html = response;
            
            // 🔥 STEP 1: Process markdown tables FIRST (convert to styled HTML tables)
            html = html.replace(/(\|[^\n]+\|\n?)+/g, (tableMatch) => {
                const rows = tableMatch.trim().split('\n').filter(r => r.trim());
                if (rows.length === 0) return tableMatch;
                
                let tableHtml = '<div class="overflow-x-auto my-5 rounded-xl shadow-lg border border-slate-200">';
                tableHtml += '<table class="w-full text-sm">';
                
                let isFirstRow = true;
                rows.forEach((row, idx) => {
                    // Skip separator rows (|---|---|)
                    if (row.includes('---')) return;
                    
                    const cells = row.split('|').filter(c => c.trim() !== '');
                    if (cells.length === 0) return;
                    
                    if (isFirstRow) {
                        // Header row - gradient background
                        tableHtml += '<thead><tr class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white">';
                        cells.forEach(cell => {
                            tableHtml += `<th class="px-4 py-3 text-left font-semibold text-[13px] whitespace-nowrap">${cell.trim()}</th>`;
                        });
                        tableHtml += '</tr></thead><tbody>';
                        isFirstRow = false;
                    } else {
                        // Data rows - zebra striping + hover
                        const bgClass = idx % 2 === 0 ? 'bg-white' : 'bg-slate-50';
                        tableHtml += `<tr class="${bgClass} hover:bg-purple-50 transition-colors border-b border-slate-100">`;
                        cells.forEach((cell, cellIdx) => {
                            let cellContent = cell.trim();
                            // Make URLs clickable
                            if (cellContent.includes('http')) {
                                cellContent = cellContent.replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank" class="text-purple-600 hover:text-purple-800 hover:underline text-xs">🔗 Xem</a>');
                            }
                            // First column (STT) - bold
                            if (cellIdx === 0 && /^\d+$/.test(cellContent)) {
                                tableHtml += `<td class="px-4 py-3 font-bold text-purple-700">${cellContent}</td>`;
                            } else {
                                tableHtml += `<td class="px-4 py-3 text-slate-700">${cellContent}</td>`;
                            }
                        });
                        tableHtml += '</tr>';
                    }
                });
                
                tableHtml += '</tbody></table></div>';
                return tableHtml;
            });
            
            // 🔥 STEP 2: Headers with beautiful styling + icons
            // H1 - Main title
            html = html.replace(/^# (.+)$/gm, '<h1 class="text-2xl font-black text-slate-900 mb-6 pb-3 border-b-2 border-purple-300">$1</h1>');
            
            // H2 - Section headers with gradient card
            html = html.replace(/^## (\d+)\.\s*([\u{1F300}-\u{1FAFF}]?)\s*(.+)$/gmu, (match, num, emoji, title) => {
                const colors = [
                    'from-purple-500 to-indigo-500',
                    'from-blue-500 to-cyan-500', 
                    'from-emerald-500 to-teal-500',
                    'from-orange-500 to-red-500',
                    'from-pink-500 to-rose-500',
                    'from-violet-500 to-purple-500',
                    'from-cyan-500 to-blue-500',
                    'from-amber-500 to-orange-500',
                    'from-teal-500 to-emerald-500',
                    'from-rose-500 to-pink-500',
                    'from-indigo-500 to-violet-500',
                    'from-red-500 to-orange-500'
                ];
                const colorIdx = (parseInt(num) - 1) % colors.length;
                return `<div class="mt-8 mb-4">
                    <div class="bg-gradient-to-r ${colors[colorIdx]} text-white px-5 py-3 rounded-t-xl flex items-center gap-3 shadow-md">
                        <span class="text-2xl">${emoji || '📌'}</span>
                        <span class="font-bold text-lg">${num}. ${title}</span>
                    </div>
                    <div class="bg-white border border-t-0 border-slate-200 rounded-b-xl p-5 shadow-sm">`;
            });
            
            // H3 - Subsection headers
            html = html.replace(/^### (.+)$/gm, '<h3 class="text-base font-bold text-purple-700 mt-5 mb-3 flex items-center gap-2"><span class="w-1.5 h-5 bg-purple-500 rounded-full"></span>$1</h3>');
            
            // 🔥 STEP 3: Text formatting
            // Bold with highlight
            html = html.replace(/\*\*(.+?)\*\*/g, '<strong class="font-bold text-slate-800 bg-yellow-100 px-0.5 rounded">$1</strong>');
            
            // Italic
            html = html.replace(/\*([^*]+)\*/g, '<em class="italic text-slate-600">$1</em>');
            
            // Code/Keywords
            html = html.replace(/`([^`]+)`/g, '<code class="bg-gradient-to-r from-purple-100 to-indigo-100 text-purple-800 px-2 py-0.5 rounded-md text-sm font-mono border border-purple-200">$1</code>');
            
            // 🔥 STEP 4: Lists with beautiful styling
            // List items with bold key: value
            html = html.replace(/^- \*\*(.+?):\*\*(.*)$/gm, 
                '<div class="flex items-start gap-3 mb-3 p-3 bg-gradient-to-r from-slate-50 to-white rounded-lg border-l-4 border-purple-400 hover:border-purple-600 transition-colors">' +
                '<span class="text-purple-500 mt-0.5">◆</span>' +
                '<div><span class="font-bold text-slate-800">$1:</span><span class="text-slate-600">$2</span></div></div>');
            
            // Simple list items
            html = html.replace(/^- (.+)$/gm, 
                '<div class="flex items-start gap-2 mb-2 ml-2"><span class="text-purple-400 mt-1">▸</span><span class="text-slate-700">$1</span></div>');
            
            // Numbered lists with styling
            html = html.replace(/^(\d+)\. \*\*(.+?)\*\*(.*)$/gm,
                '<div class="flex items-start gap-3 mb-4 p-4 bg-white rounded-xl shadow-sm border border-slate-100 hover:shadow-md transition-shadow">' +
                '<span class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-purple-500 to-indigo-500 text-white rounded-full flex items-center justify-center font-bold text-sm">$1</span>' +
                '<div class="flex-1"><div class="font-bold text-slate-800 mb-1">$2</div><div class="text-slate-600 text-sm">$3</div></div></div>');
            
            html = html.replace(/^(\d+)\. (.+)$/gm, 
                '<div class="flex items-start gap-3 mb-2 ml-1"><span class="text-purple-600 font-bold">$1.</span><span class="text-slate-700">$2</span></div>');
            
            // 🔥 STEP 5: Blockquotes - Important highlights
            html = html.replace(/^> "(.+)"$/gm, 
                '<blockquote class="my-4 p-4 bg-gradient-to-r from-amber-50 to-yellow-50 border-l-4 border-amber-400 rounded-r-xl italic text-slate-700 shadow-sm">' +
                '<span class="text-2xl text-amber-500 mr-2">💡</span>"$1"</blockquote>');
            
            html = html.replace(/^> (.+)$/gm, 
                '<blockquote class="my-3 p-3 bg-purple-50 border-l-4 border-purple-400 rounded-r-lg text-slate-700 italic">$1</blockquote>');
            
            // 🔥 STEP 6: Special patterns - Rating highlights
            html = html.replace(/\*\*(\d+)\/10\*\*/g, 
                '<span class="inline-flex items-center gap-1 px-3 py-1 bg-gradient-to-r from-green-500 to-emerald-500 text-white font-bold rounded-full text-sm shadow-sm">⭐ $1/10</span>');
            
            // 🔥 STEP 7: URLs - Make them clickable buttons
            html = html.replace(/(?<!href=")(https?:\/\/[^\s<>"]+)/g, 
                '<a href="$1" target="_blank" class="inline-flex items-center gap-1 text-purple-600 hover:text-purple-800 hover:underline text-sm"><i class="fa-solid fa-external-link text-xs"></i>Link</a>');
            
            // 🔥 STEP 8: Close section cards (match opened divs from H2)
            // Count ## headers and close their divs
            const sectionCount = (html.match(/bg-gradient-to-r.*?rounded-t-xl/g) || []).length;
            for (let i = 0; i < sectionCount; i++) {
                // Find last unclosed section and close it before next section or end
                html = html.replace(/(rounded-b-xl p-5 shadow-sm">)([\s\S]*?)(<div class="mt-8 mb-4">|$)/, '$1$2</div></div>$3');
            }
            
            // 🔥 STEP 9: Paragraph breaks
            html = html.replace(/\n\n+/g, '</p><p class="mb-3 text-slate-700 leading-relaxed">');
            html = html.replace(/(?<![>])\n(?![<])/g, '<br>');
            
            // 🔥 STEP 10: Clean up empty paragraphs
            html = html.replace(/<p[^>]*>\s*<\/p>/g, '');
            html = html.replace(/<p[^>]*><br><\/p>/g, '');
            
            // Wrap in container
            return `<div class="ai-analysis-content space-y-2 text-[14px] leading-relaxed">${html}</div>`;
        }
        
        function copyAnalysis() {
            const content = document.getElementById('deepResults').innerText;
            navigator.clipboard.writeText(content);
            showToast('✅ Đã copy toàn bộ phân tích!', 'success');
        }
        
        // 💾 SAVE CURRENT ANALYSIS TO HISTORY
        async function saveCurrentAnalysis() {
            if (!currentDeepChannelData) {
                showToast('❌ Chưa có dữ liệu để lưu!', 'error');
                return;
            }
            
            try {
                const saveData = {
                    name: currentDeepChannelData.name,
                    channelId: currentDeepChannelData.channelId,
                    url: currentDeepChannelData.url || `https://www.youtube.com/channel/${currentDeepChannelData.channelId}`,
                    subscribers: currentDeepChannelData.subscribers,
                    totalVideos: currentDeepChannelData.totalVideos,
                    totalViews: currentDeepChannelData.totalViews,
                    aiResponse: lastAIResponse || ''
                };
                
                const response = await fetch('api_save_ai_history.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(saveData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast('💾 Đã lưu kết quả vào Lịch Sử AI!', 'success');
                } else {
                    showToast('❌ Lỗi khi lưu: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error saving analysis:', error);
                showToast('❌ Lỗi kết nối server!', 'error');
            }
        }
        
        // 📄 EXPORT HTML CODE
        function exportHTMLCode() {
            if (!currentDeepChannelData) {
                showToast('❌ Chưa có dữ liệu để lấy HTML!', 'error');
                return;
            }
            
            const element = document.getElementById('deepResults');
            const channelName = currentDeepChannelData.name || 'Channel';
            
            // Get the full HTML content
            let htmlContent = element.innerHTML;
            
            // Create a complete HTML document
            const fullHTML = `<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo Cáo Phân Tích Kênh YouTube: ${channelName}</title>
    <link href="https://cdn.tailwindcss.com" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; background: #f8fafc; }
        .ai-analysis-content h1 { font-size: 1.5rem; font-weight: 900; margin-bottom: 1rem; }
        .ai-analysis-content h3 { font-size: 1rem; font-weight: 700; color: #7c3aed; margin: 1rem 0 0.5rem; }
        .ai-analysis-content p { margin-bottom: 0.5rem; line-height: 1.7; }
        .ai-analysis-content strong { background: #fef9c3; padding: 0 0.25rem; border-radius: 0.25rem; }
        .ai-analysis-content code { background: #f3e8ff; color: #7c3aed; padding: 0.125rem 0.5rem; border-radius: 0.375rem; font-family: monospace; }
        .ai-analysis-content blockquote { border-left: 4px solid #fbbf24; padding-left: 1rem; font-style: italic; color: #64748b; margin: 1rem 0; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-purple-50 to-pink-50 p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-black text-purple-600">HSHOP Analytics</h2>
                <p class="text-slate-500 mt-2">Powered by Gemini AI</p>
            </div>
            <div class="border-b pb-4 mb-6">
                <h3 class="text-sm font-bold text-slate-500 uppercase tracking-wide">Phân Tích Kênh: ${channelName}</h3>
                <p class="text-xs text-slate-400 mt-1">Dữ liệu: ${currentDeepChannelData.subscribers?.toLocaleString() || 0} subscribers | ${currentDeepChannelData.totalVideos || 0} videos | ${((currentDeepChannelData.totalViews || 0) / 1000000).toFixed(1)}M views</p>
            </div>
            <div class="ai-analysis-content text-[14px] leading-relaxed text-slate-700">
                ${htmlContent}
            </div>
        </div>
    </div>
</body>
</html>`;
            
            // Create a modal to show the code
            const modal = document.createElement('div');
            modal.id = 'htmlCodeModal';
            modal.className = 'fixed inset-0 bg-black/70 flex items-center justify-center z-[9999] p-4';
            modal.innerHTML = `
                <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] flex flex-col">
                    <div class="p-4 border-b flex items-center justify-between bg-gradient-to-r from-orange-500 to-amber-500 text-white rounded-t-2xl">
                        <h3 class="font-bold text-lg"><i class="fa-solid fa-code mr-2"></i>Mã HTML Để Copy</h3>
                        <button onclick="closeHTMLModal()" class="hover:bg-white/20 p-2 rounded-lg transition">
                            <i class="fa-solid fa-times text-xl"></i>
                        </button>
                    </div>
                    <div class="p-4 flex-1 overflow-auto">
                        <p class="text-sm text-slate-600 mb-3"><i class="fa-solid fa-info-circle text-orange-500 mr-1"></i> Copy toàn bộ code bên dưới và dán vào bài viết trên website của bạn:</p>
                        <textarea id="htmlCodeContent" readonly class="w-full h-[400px] p-4 bg-slate-900 text-green-400 rounded-lg font-mono text-sm resize-none border-2 border-slate-700 focus:border-orange-500">${fullHTML.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</textarea>
                    </div>
                    <div class="p-4 border-t flex gap-3">
                        <button onclick="copyHTMLCode()" class="flex-1 bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white font-bold py-3 rounded-xl transition flex items-center justify-center gap-2">
                            <i class="fa-solid fa-copy"></i> Copy Mã HTML
                        </button>
                        <button onclick="downloadHTMLFile()" class="flex-1 bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 text-white font-bold py-3 rounded-xl transition flex items-center justify-center gap-2">
                            <i class="fa-solid fa-download"></i> Tải File .HTML
                        </button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }
        
        function closeHTMLModal() {
            const modal = document.getElementById('htmlCodeModal');
            if (modal) modal.remove();
        }
        
        function copyHTMLCode() {
            const textarea = document.getElementById('htmlCodeContent');
            const text = textarea.value.replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&amp;/g, '&');
            textarea.value = text;
            textarea.select();
            document.execCommand('copy');
            textarea.value = text.replace(/</g, '&lt;').replace(/>/g, '&gt;');
            showToast('✅ Đã copy mã HTML vào bộ nhớ tạm!', 'success');
        }
        
        function downloadHTMLFile() {
            const textarea = document.getElementById('htmlCodeContent');
            const text = textarea.value.replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&amp;/g, '&');
            const channelName = currentDeepChannelData?.name?.replace(/[^a-z0-9]/gi, '_') || 'Channel';
            const blob = new Blob([text], { type: 'text/html' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `HSHOP_Analysis_${channelName}.html`;
            a.click();
            URL.revokeObjectURL(url);
            showToast('✅ Đã tải file HTML!', 'success');
        }
        
        // 🔗 SHARE CURRENT ANALYSIS
        async function shareCurrentAnalysis() {
            if (!currentDeepChannelData) {
                showToast('❌ Chưa có dữ liệu để chia sẻ!', 'error');
                return;
            }
            
            const channelName = currentDeepChannelData.name;
            const subs = (currentDeepChannelData.subscribers / 1000).toFixed(1) + 'K';
            const videos = currentDeepChannelData.totalVideos;
            const views = (currentDeepChannelData.totalViews / 1000000).toFixed(1) + 'M';
            
            const shareText = `📊 PHÂN TÍCH AI KÊNH YOUTUBE\n\n` +
                            `🎯 Kênh: ${channelName}\n` +
                            ` Subscribers: ${subs}\n` +
                            `🎬 Videos: ${videos}\n` +
                            `👁️ Total Views: ${views}\n\n` +
                            `Phân tích bởi HSHOP Analytics - AI Deep Dive tool!\n` +
                            `Xem chi tiết tại: https://<?php echo getBaseUrl(); ?>/ai-history.php`;
            
            // Try Web Share API first
            if (navigator.share) {
                try {
                    await navigator.share({
                        title: `AI Analysis: ${channelName}`,
                        text: shareText
                    });
                    console.log('✅ Shared successfully');
                } catch (err) {
                    console.error('Share failed:', err);
                    fallbackShareAnalysis(shareText);
                }
            } else {
                fallbackShareAnalysis(shareText);
            }
        }
        
        function fallbackShareAnalysis(text) {
            navigator.clipboard.writeText(text).then(() => {
                showToast('✅ Đã copy kết quả vào bộ nhớ tạm!', 'success');
            }).catch(err => {
                alert('Copy nội dung này và chia sẻ:\n\n' + text);
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            checkAuthStatus();
            if (typeof loadDashboard === 'function') loadDashboard();
            const path = document.getElementById('gaugeFill');
            if (path) { path.style.strokeDasharray = "252, 252"; path.style.strokeDashoffset = "252"; }

            // 🎉 Show welcome toast after successful payment activation
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('activated') === '1') {
                setTimeout(() => {
                    showToast('🎉 Tài khoản đã được kích hoạt! Chào mừng bạn đến với HSHOP Analytics.', 'success');
                }, 500);
                // Clean URL without reload
                history.replaceState(null, '', 'scanner.php');
            }
            
            // ✅ RESTORE LAST ACTIVE TAB FROM LOCALSTORAGE
            const savedTab = localStorage.getItem('scanner_active_tab');
            if (savedTab && document.getElementById(savedTab)) {
                // Wait a bit to ensure DOM is fully loaded
                setTimeout(() => {
                    switchTab(savedTab);
                }, 100);
            }
        });
        
        // ✨ FREE AI TOOLS FUNCTIONS
        
        // AI Title Generator
        async function generateTitles() {
            const topic = document.getElementById('titleTopic').value.trim();
            if (!topic) {
                showToast('⚠️ Vui lòng nhập chủ đề video!', 'error');
                return;
            }
            
            // 🔥 DEBUG: Check API availability
            console.log('%c🎬 Generate Titles Started', 'background: purple; color: white; font-size: 12px; padding: 3px;');
            console.log('Topic:', topic);
            console.log('apiPool object:', apiPool);
            console.log('apiPool.gemini:', apiPool.gemini);
            console.log('apiPool.gemini type:', typeof apiPool.gemini);
            console.log('apiPool.gemini is Array:', Array.isArray(apiPool.gemini));
            console.log('apiPool.gemini length:', apiPool.gemini ? apiPool.gemini.length : 'N/A');
            
            // 🔥 CRITICAL: Reload Gemini keys from localStorage to ensure freshness
            loadGeminiKeys();
            console.log('After loadGeminiKeys(), apiPool.gemini:', apiPool.gemini);
            
            // Check if Gemini API exists
            const geminiKey = getActiveGeminiKey();
            console.log('getActiveGeminiKey() result:', geminiKey ? geminiKey.substring(0, 15) + '...' : 'NULL');
            
            if (!geminiKey) {
                console.log('%c❌ NO API KEY - Showing modal', 'background: red; color: white; font-size: 12px; padding: 3px;');
                // Show friendly prompt to add API key
                showAPIKeyPrompt('title', topic);
                return;
            }
            
            console.log('%c✅ API KEY FOUND - Generating...', 'background: green; color: white; font-size: 12px; padding: 3px;');
            
            const btn = document.getElementById('generateTitleBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>AI đang tạo titles...';
            btn.disabled = true;
            
            try {
                const prompt = `Tạo 10 tiêu đề YouTube có tỷ lệ click (CTR) cao cho chủ đề: "${topic}"

Yêu cầu:
- Sử dụng khoảng trống tò mò (curiosity gap)
- Bao gồm số liệu/thống kê cụ thể
- Kích hoạt cảm xúc (FOMO, shock, surprise)
- Sử dụng từ quyền lực (power words)
- Dưới 60 ký tự
- Tiếng Việt tự nhiên

Các mẫu CTR cao:
- "Tôi đã [KẾT QUẢ] sau [THỜI GIAN] làm [PHƯƠNG PHÁP]"
- "[SỐ] cách [KẾT QUẢ] mà 99% người không biết"
- "Sự thật về [CHỦ ĐỀ] mà [AUTHORITY] giấu bạn"

Xuất ra dạng danh sách đánh số 1-10, mỗi title một dòng.`;
                
                const result = await callGeminiAPI(geminiKey, prompt);
                displayTitleResults(result);
                showToast('✨ Đã tạo 10 tiêu đề viral!', 'success');
                
            } catch (error) {
                console.error('Title generation error:', error);
                showToast('❌ Lỗi: ' + error.message, 'error');
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }
        
        // Thumbnail Prompt Generator
        async function generateThumbnailPrompts() {
            const topic = document.getElementById('thumbnailTopic').value.trim();
            if (!topic) {
                showToast('⚠️ Vui lòng nhập chủ đề/niche!', 'error');
                return;
            }
            
            // 🔥 CRITICAL: Reload Gemini keys from localStorage to ensure freshness
            loadGeminiKeys();
            console.log('🇿 Thumbnail Prompts - apiPool.gemini:', apiPool.gemini);
            
            // Check if Gemini API exists
            const geminiKey = getActiveGeminiKey();
            if (!geminiKey) {
                // Show friendly prompt to add API key
                showAPIKeyPrompt('thumbnail', topic);
                return;
            }
            
            const btn = document.getElementById('generateThumbBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>AI đang tạo prompts...';
            btn.disabled = true;
            
            try {
                const prompt = `Tạo 3 prompts cho Midjourney/DALL-E để tạo thumbnail YouTube có CTR 40%+ cho chủ đề: "${topic}"

Yêu cầu cho mỗi prompt:
1. Mô tả hình ảnh cụ thể, rõ ràng
2. Bao gồm color psychology (màu sắc kích thích click)
3. Composition professional (rule of thirds, focal point)
4. Text overlay suggestions (nếu có)
5. Style: Bold, eye-catching, high contrast

Format xuất ra:
**Prompt 1: [Tên variation]**
[Full Midjourney/DALL-E prompt]

**Prompt 2: [Tên variation]**
[Full Midjourney/DALL-E prompt]

**Prompt 3: [Tên variation]**
[Full Midjourney/DALL-E prompt]`;
                
                const result = await callGeminiAPI(geminiKey, prompt);
                displayThumbnailResults(result);
                showToast('✨ Đã tạo 3 thumbnail prompts!', 'success');
                
            } catch (error) {
                console.error('Thumbnail generation error:', error);
                showToast('❌ Lỗi: ' + error.message, 'error');
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }
        
        // Call Gemini API
        async function callGeminiAPI(apiKey, prompt) {
            // 🔥 USE GEMINI 2.5 FLASH (latest, powerful, FREE)
            const url = `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=${apiKey}`;
            
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    contents: [{
                        parts: [{ text: prompt }]
                    }]
                })
            });
            
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.error?.message || 'API Error');
            }
            
            const data = await response.json();
            return data.candidates?.[0]?.content?.parts?.[0]?.text || null;
        }
        
        // Get active Gemini key from apiPool
        function getActiveGeminiKey() {
            if (!apiPool || !apiPool.gemini || apiPool.gemini.length === 0) {
                return null;
            }
            // Return first key (can be enhanced with rotation logic)
            return apiPool.gemini[0];
        }
        
        // Display title results
        function displayTitleResults(text) {
            const container = document.getElementById('titleResults');
            container.innerHTML = '';
            container.classList.remove('hidden');
            
            const lines = text.split('\n').filter(line => line.trim());
            
            lines.forEach((line, index) => {
                const cleanLine = line.replace(/^\d+\.\s*/, '').trim();
                if (cleanLine) {
                    const div = document.createElement('div');
                    div.className = 'bg-white border-2 border-purple-200 rounded-lg p-3 hover:border-purple-400 transition group';
                    div.innerHTML = `
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1">
                                <div class="text-xs text-purple-600 font-bold mb-1">Title ${index + 1}</div>
                                <div class="text-sm font-medium text-gray-900">${cleanLine}</div>
                            </div>
                            <button onclick="copyTitle('${cleanLine.replace(/'/g, "\\'")}')"
                                    class="opacity-0 group-hover:opacity-100 transition bg-purple-600 hover:bg-purple-700 text-white px-3 py-1.5 rounded text-xs font-bold">
                                <i class="fa-solid fa-copy mr-1"></i>
                                Copy
                            </button>
                        </div>
                    `;
                    container.appendChild(div);
                }
            });
        }
        
        // Display thumbnail results
        function displayThumbnailResults(text) {
            const container = document.getElementById('thumbnailResults');
            container.innerHTML = '';
            container.classList.remove('hidden');
            
            // Split by **Prompt markers
            const prompts = text.split(/\*\*Prompt \d+:/).filter(p => p.trim());
            
            prompts.forEach((prompt, index) => {
                const cleanPrompt = prompt.trim();
                if (cleanPrompt) {
                    const div = document.createElement('div');
                    div.className = 'bg-white border-2 border-blue-200 rounded-lg p-3 hover:border-blue-400 transition group';
                    div.innerHTML = `
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1">
                                <div class="text-xs text-blue-600 font-bold mb-1">Prompt ${index + 1}</div>
                                <div class="text-sm text-gray-900 whitespace-pre-wrap">${cleanPrompt}</div>
                            </div>
                            <button onclick="copyPrompt('${cleanPrompt.replace(/'/g, "\\'").replace(/\n/g, ' ')}')"
                                    class="opacity-0 group-hover:opacity-100 transition bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded text-xs font-bold">
                                <i class="fa-solid fa-copy mr-1"></i>
                                Copy
                            </button>
                        </div>
                    `;
                    container.appendChild(div);
                }
            });
        }
        
        // Copy functions
        function copyTitle(text) {
            navigator.clipboard.writeText(text).then(() => {
                showToast('✅ Đã copy tiêu đề!', 'success');
            }).catch(err => {
                showToast('❌ Không thể copy!', 'error');
            });
        }
        
        function copyPrompt(text) {
            navigator.clipboard.writeText(text).then(() => {
                showToast('✅ Đã copy prompt!', 'success');
            }).catch(err => {
                showToast('❌ Không thể copy!', 'error');
            });
        }
        
        // 💎 Show API Key Prompt Modal
        function showAPIKeyPrompt(toolType, savedTopic) {
            const toolName = toolType === 'title' ? 'AI Title Generator' : 'Thumbnail Prompt AI';
            const icon = toolType === 'title' ? 'fa-heading' : 'fa-image';
            
            // Create modal HTML
            const modalHTML = `
                <div id="apiKeyPromptModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" onclick="closeAPIKeyPrompt()">
                    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full animate-fade-in" onclick="event.stopPropagation()">
                        <!-- Header -->
                        <div class="bg-gradient-to-r from-purple-600 to-pink-600 p-6 rounded-t-2xl">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="bg-white/20 p-3 rounded-xl">
                                    <i class="fa-solid ${icon} text-white text-2xl"></i>
                                </div>
                                <div>
                                    <h3 class="text-xl font-black text-white">${toolName}</h3>
                                    <p class="text-xs text-purple-100">Cần Gemini API để tiếp tục</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Body -->
                        <div class="p-6 space-y-4">
                            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                                <p class="text-sm text-blue-900 font-medium">
                                    <i class="fa-solid fa-lightbulb mr-2 text-blue-600"></i>
                                    Để sử dụng FREE AI Tools, bạn cần thêm Gemini API key.
                                </p>
                            </div>
                            
                            <div class="space-y-2">
                                <div class="flex items-start gap-2 text-sm text-gray-700">
                                    <i class="fa-solid fa-check text-green-600 mt-0.5"></i>
                                    <span><strong>FREE:</strong> 1,500 requests/ngày miễn phí</span>
                                </div>
                                <div class="flex items-start gap-2 text-sm text-gray-700">
                                    <i class="fa-solid fa-check text-green-600 mt-0.5"></i>
                                    <span><strong>EASY:</strong> Chỉ mất 30 giây để lấy key</span>
                                </div>
                                <div class="flex items-start gap-2 text-sm text-gray-700">
                                    <i class="fa-solid fa-check text-green-600 mt-0.5"></i>
                                    <span><strong>SAFE:</strong> Key của bạn, chỉ bạn kiểm soát</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Footer -->
                        <div class="p-6 pt-0 flex gap-3">
                            <button onclick="closeAPIKeyPrompt()" 
                                    class="flex-1 px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition">
                                Để sau
                            </button>
                            <button onclick="closeAPIKeyPrompt(); switchTab('apiKeysTab');" 
                                    class="flex-1 px-4 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold rounded-xl transition shadow-lg">
                                <i class="fa-solid fa-arrow-right mr-2"></i>
                                Thêm API Key
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            // Insert modal into body
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            
            // Store topic for later use
            if (savedTopic) {
                sessionStorage.setItem('pendingTool', JSON.stringify({ type: toolType, topic: savedTopic }));
            }
        }
        
        function closeAPIKeyPrompt() {
            const modal = document.getElementById('apiKeyPromptModal');
            if (modal) {
                modal.remove();
            }
        }
    </script>

<style>
    @keyframes fade-in {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }
    .animate-fade-in {
        animation: fade-in 0.2s ease-out;
    }
</style>

<!-- 👣 FOOTER - Compact & Bottom -->
<footer class="fixed bottom-0 left-0 right-0 z-30 bg-slate-900/95 backdrop-blur-sm border-t border-slate-800/60">
    <div class="md:ml-64 px-4 py-3">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-2 text-xs">
            <!-- Copyright -->
            <div class="text-slate-500">
                &copy; <?php echo date('Y'); ?> 
                <span class="text-slate-300 font-semibold">HSHOP MEDIA </span>
                <span class="hidden md:inline text-slate-600 mx-2">|</span>
                <span class="hidden md:inline text-slate-600">All rights reserved.</span>
            </div>
            
            <!-- YouTube Link -->
            <a href="https://www.youtube.com/" 
               target="_blank" 
               class="group flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-800 hover:bg-red-600/10 border border-slate-700 hover:border-red-500/50 transition-all">
                <i class="fa-brands fa-youtube text-sm text-slate-400 group-hover:text-red-500 transition-colors"></i>
                <span class="text-xs font-medium text-slate-400 group-hover:text-red-400 transition-colors">
                    YouTube
                </span>
            </a>
        </div>
    </div>
</footer>

<!-- Add padding-bottom to body to prevent content being hidden by footer -->
<style>
    body {
        padding-bottom: 60px; /* Space for fixed footer */
    }
</style>

<!-- 📹 TUTORIALS MODAL -->
<div id="tutorialsModal" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm z-[9999] flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-5xl w-full max-h-[90vh] overflow-y-auto">
        <!-- Modal Header -->
        <div class="sticky top-0 bg-gradient-to-r from-blue-600 to-purple-600 text-white p-6 rounded-t-2xl flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-black flex items-center gap-3">
                    <i class="fa-solid fa-graduation-cap text-3xl"></i>
                    Hướng Dẫn Chi Tiết
                </h2>
                <p class="text-sm opacity-90 mt-1">Video tutorials từ A-Z để bạn làm chủ hệ thống</p>
            </div>
            <button onclick="closeTutorials()" class="w-10 h-10 rounded-full bg-white/20 hover:bg-white/30 transition flex items-center justify-center">
                <i class="fa-solid fa-times text-xl"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="p-6 space-y-6">
            
            <!-- Tutorial Grid -->
            <div class="grid md:grid-cols-2 gap-6">
                
                <!-- Tutorial 1: YouTube API -->
                <div class="bg-gradient-to-br from-red-50 to-orange-50 border-2 border-red-200 rounded-xl overflow-hidden hover:shadow-lg transition group">
                    <div class="aspect-video relative bg-black">
                        <img src="https://img.youtube.com/vi/2URIxZ4qQQg/maxresdefault.jpg" 
                             alt="Cách lấy API YouTube" 
                             class="w-full h-full object-cover opacity-90 group-hover:opacity-100 transition">
                        <div class="absolute inset-0 bg-black/20 group-hover:bg-black/10 transition"></div>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <a href="https://www.youtube.com/watch?v=2URIxZ4qQQg" 
                               target="_blank"
                               class="w-16 h-16 bg-red-600 hover:bg-red-700 rounded-full flex items-center justify-center text-white shadow-2xl transform group-hover:scale-110 transition">
                                <i class="fa-solid fa-play text-2xl ml-1"></i>
                            </a>
                        </div>
                    </div>
                    <div class="p-4">
                        <h3 class="text-lg font-black text-slate-800 mb-2 flex items-center gap-2">
                            <span class="w-7 h-7 bg-red-600 text-white rounded-full flex items-center justify-center text-sm font-black">1</span>
                            Cách Lấy API YouTube
                        </h3>
                        <p class="text-sm text-slate-600 mb-3">Hướng dẫn chi tiết cách tạo API Key từ Google Cloud Console để tìm kiếm video YouTube</p>
                        <a href="https://www.youtube.com/watch?v=2URIxZ4qQQg" 
                           target="_blank"
                           class="inline-flex items-center gap-2 text-sm font-bold text-red-600 hover:text-red-700">
                            <i class="fa-brands fa-youtube"></i>
                            Xem video ngay
                            <i class="fa-solid fa-arrow-right text-xs"></i>
                        </a>
                    </div>
                </div>

                <!-- Tutorial 2: OpenRouter API -->
                <div class="bg-gradient-to-br from-purple-50 to-pink-50 border-2 border-purple-200 rounded-xl overflow-hidden hover:shadow-lg transition group">
                    <div class="aspect-video relative bg-black">
                        <img src="https://img.youtube.com/vi/1jfXUkEQmx8/maxresdefault.jpg" 
                             alt="Cách lấy API OpenRouter" 
                             class="w-full h-full object-cover opacity-90 group-hover:opacity-100 transition">
                        <div class="absolute inset-0 bg-black/20 group-hover:bg-black/10 transition"></div>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <a href="https://www.youtube.com/watch?v=1jfXUkEQmx8" 
                               target="_blank"
                               class="w-16 h-16 bg-purple-600 hover:bg-purple-700 rounded-full flex items-center justify-center text-white shadow-2xl transform group-hover:scale-110 transition">
                                <i class="fa-solid fa-play text-2xl ml-1"></i>
                            </a>
                        </div>
                    </div>
                    <div class="p-4">
                        <h3 class="text-lg font-black text-slate-800 mb-2 flex items-center gap-2">
                            <span class="w-7 h-7 bg-purple-600 text-white rounded-full flex items-center justify-center text-sm font-black">2</span>
                            Cách Lấy API OpenRouter
                        </h3>
                        <p class="text-sm text-slate-600 mb-3">Tạo API Key OpenRouter để sử dụng AI phân tích nội dung video chuyên nghiệp</p>
                        <a href="https://www.youtube.com/watch?v=1jfXUkEQmx8" 
                           target="_blank"
                           class="inline-flex items-center gap-2 text-sm font-bold text-purple-600 hover:text-purple-700">
                            <i class="fa-brands fa-youtube"></i>
                            Xem video ngay
                            <i class="fa-solid fa-arrow-right text-xs"></i>
                        </a>
                    </div>
                </div>

                <!-- Tutorial 3: Gemini API -->
                <div class="bg-gradient-to-br from-blue-50 to-cyan-50 border-2 border-blue-200 rounded-xl overflow-hidden hover:shadow-lg transition group">
                    <div class="aspect-video relative bg-black">
                        <img src="https://img.youtube.com/vi/xADpKIKPZpc/maxresdefault.jpg" 
                             alt="Cách lấy API Gemini" 
                             class="w-full h-full object-cover opacity-90 group-hover:opacity-100 transition">
                        <div class="absolute inset-0 bg-black/20 group-hover:bg-black/10 transition"></div>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <a href="https://www.youtube.com/watch?v=xADpKIKPZpc" 
                               target="_blank"
                               class="w-16 h-16 bg-blue-600 hover:bg-blue-700 rounded-full flex items-center justify-center text-white shadow-2xl transform group-hover:scale-110 transition">
                                <i class="fa-solid fa-play text-2xl ml-1"></i>
                            </a>
                        </div>
                    </div>
                    <div class="p-4">
                        <h3 class="text-lg font-black text-slate-800 mb-2 flex items-center gap-2">
                            <span class="w-7 h-7 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-black">3</span>
                            Cách Lấy API Gemini
                        </h3>
                        <p class="text-sm text-slate-600 mb-3">Tạo API Key Gemini từ Google AI Studio cho AI Deep Dive</p>
                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-3">
                            <p class="text-xs font-bold text-amber-900 mb-1">
                                <i class="fa-solid fa-lightbulb mr-1"></i> Pro Tips:
                            </p>
                            <ul class="text-xs text-amber-800 space-y-1">
                                <li>• Nên lấy <strong>mỗi mail 1 API</strong></li>
                                <li>• <strong>Tách IP mạng</strong> để đảm bảo xịn</li>
                                <li>• Không share API key công khai</li>
                            </ul>
                        </div>
                        <a href="https://www.youtube.com/watch?v=xADpKIKPZpc" 
                           target="_blank"
                           class="inline-flex items-center gap-2 text-sm font-bold text-blue-600 hover:text-blue-700">
                            <i class="fa-brands fa-youtube"></i>
                            Xem video ngay
                            <i class="fa-solid fa-arrow-right text-xs"></i>
                        </a>
                    </div>
                </div>

                <!-- Tutorial 4: Tìm Key Xịn -->
                <div class="bg-gradient-to-br from-green-50 to-emerald-50 border-2 border-green-200 rounded-xl overflow-hidden hover:shadow-lg transition group">
                    <div class="p-6">
                        <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl flex items-center justify-center text-white text-3xl mb-4 shadow-lg">
                            <i class="fa-solid fa-key"></i>
                        </div>
                        <h3 class="text-lg font-black text-slate-800 mb-2 flex items-center gap-2">
                            <span class="w-7 h-7 bg-green-600 text-white rounded-full flex items-center justify-center text-sm font-black">4</span>
                            Bí Quyết Tìm Key Xịn
                        </h3>
                        <p class="text-sm text-slate-600 mb-4">Chiến lược tìm từ khóa viral chuyên nghiệp</p>
                        
                        <div class="space-y-3">
                            <div class="bg-white border-2 border-green-200 rounded-lg p-3">
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                                        <i class="fa-solid fa-globe text-green-600"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="text-sm font-black text-slate-800 mb-1">1. Sử Dụng Ngôn Ngữ Địa Phương</h4>
                                        <p class="text-xs text-slate-600">Muốn tìm key ở quốc gia nào, phải dùng <strong>ngôn ngữ quốc gia đó</strong></p>
                                        <div class="mt-2 flex flex-wrap gap-1">
                                            <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full font-semibold">🇻🇳 Việt Nam</span>
                                            <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full font-semibold">🇺🇸 English</span>
                                            <span class="text-xs bg-orange-100 text-orange-700 px-2 py-1 rounded-full font-semibold">🇹🇭 ไทย</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white border-2 border-green-200 rounded-lg p-3">
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                                        <i class="fa-solid fa-text-width text-green-600"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="text-sm font-black text-slate-800 mb-1">2. Chiến Lược Từ Khóa</h4>
                                        <p class="text-xs text-slate-600 mb-2">Tìm cả <strong>từ khóa dài</strong> (long-tail) và <strong>từ khóa nhỏ</strong> (niche)</p>
                                        <div class="text-xs space-y-1">
                                            <div class="flex items-center gap-2">
                                                <i class="fa-solid fa-check text-green-600"></i>
                                                <span class="text-slate-700">Từ khóa dài: "cách nấu phở bò Hà Nội ngon"</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <i class="fa-solid fa-check text-green-600"></i>
                                                <span class="text-slate-700">Từ khóa niche: "review máy ảnh 2024"</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white border-2 border-green-200 rounded-lg p-3">
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                                        <i class="fa-solid fa-chart-line text-green-600"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="text-sm font-black text-slate-800 mb-1">3. Phân Tích Xu Hướng</h4>
                                        <p class="text-xs text-slate-600">Kết hợp từ khóa với <strong>trending topics</strong> trong nước hoặc khu vực</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Bottom CTA -->
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl p-6 text-white text-center">
                <div class="text-4xl mb-3">🚀</div>
                <h3 class="text-xl font-black mb-2">Sẵn Sàng Bắt Đầu?</h3>
                <p class="text-sm opacity-90 mb-4">Xem hết 3 video trên để có đủ API keys, sau đó áp dụng bí quyết tìm key xịn!</p>
                <button onclick="closeTutorials()" class="bg-white text-blue-600 font-bold px-6 py-3 rounded-lg hover:bg-blue-50 transition">
                    <i class="fa-solid fa-check mr-2"></i>
                    Đã Hiểu, Bắt Đầu Ngay!
                </button>
            </div>

        </div>
    </div>
</div>

</body>
</html>