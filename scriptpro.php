<?php
// 1. KẾT NỐI HỆ THỐNG BẢO MẬT
require_once 'includes/functions.php';

// 2. ✅ FREE TOOL - KHÔNG CẦN ĐĂNG NHẬP
// ScriptPro là FREE tool, ai cũng dùng được (chỉ cần có Gemini API key)
// if (!isset($_SESSION['user_login']) || $_SESSION['user_login'] !== true) {
//     header("Location: scanner.php");
//     exit();
// }

// 3. TÍNH TRẠNG THÁI TIER (for UI display) - Optional
$userTier = 'free'; // Default to free
$isVip = false; // Default to non-VIP

if (isset($_SESSION['user_login']) && $_SESSION['user_login'] === true) {
    $currentKey = $_SESSION['current_key'] ?? '';
    $allKeys = loadDB('keys.json');
    $keyData = $allKeys[$currentKey] ?? [];
    
    // Simple tier detection from key data
    if (isset($keyData['tier'])) {
        $userTier = $keyData['tier'];
        $isVip = in_array($userTier, ['vip', 'super_vip']);
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ScriptPro - Chuyển Kịch Bản Thành Prompts | HSHOP</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #020617 0%, #0f172a 50%, #1e1b4b 100%); color: white; min-height: 100vh; }
        .glass-nav { background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(20px); border-bottom: 1px solid rgba(255,255,255,0.08); }
        .glass-card { background: rgba(30, 41, 59, 0.4); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.05); }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #1e293b; }
        ::-webkit-scrollbar-thumb { background: #475569; border-radius: 10px; }
        .scene-card { transition: all 0.3s; }
        .scene-card:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
    </style>
</head>

<body class="pb-24">

<header class="glass-nav fixed top-0 w-full z-50">
    <div class="max-w-7xl mx-auto px-4 h-16 flex justify-between items-center">
        <div class="flex items-center gap-3">
            <a href="scanner.php" class="flex items-center gap-2 hover:opacity-80 transition">
                <i class="fa-solid fa-arrow-left text-slate-400"></i>
                <span class="text-sm text-slate-400">Dashboard</span>
            </a>
            <div class="w-px h-6 bg-slate-700"></div>
            <div class="flex items-center gap-2">
                <div class="w-9 h-9 bg-gradient-to-br from-purple-600 to-pink-600 rounded-lg flex items-center justify-center shadow-lg">
                    <i class="fa-solid fa-wand-magic-sparkles text-white"></i>
                </div>
                <span class="font-black text-xl">Script<span class="text-purple-500">Pro</span></span>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <button onclick="toggleSettings()" class="flex items-center gap-2 px-3 py-2 bg-slate-800/50 hover:bg-slate-700/50 border border-slate-600/30 rounded-lg transition-all">
                <i class="fa-solid fa-key text-purple-400"></i>
                <span class="text-xs text-slate-300">API Config</span>
                <span id="keyCountBadge" class="ml-1 px-1.5 py-0.5 rounded-full text-[9px] bg-slate-800 text-slate-400">0</span>
            </button>
            <div id="apiStatusBadge" class="text-xs">
                <span class="text-[9px] px-2 py-0.5 bg-red-500/20 text-red-300 border border-red-500/30 rounded-full">🔴 Google</span>
            </div>
            <span class="text-xs px-2 py-1 bg-green-900/30 text-green-300 border border-green-500/30 rounded-lg font-bold">
                🎉 FREE Tool
            </span>
        </div>
    </div>
</header>

<div class="pt-24 max-w-7xl mx-auto px-4">
    
    <!-- Hero Section -->
    <div class="text-center mb-8">
        <h1 class="text-4xl font-black mb-3 bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent">
            📜 Script to Prompts Converter
        </h1>
        <p class="text-slate-400 text-sm max-w-2xl mx-auto">
            Chuyển kịch bản có sẵn thành Video/Image Prompts + SEO + Thumbnail CTR 40%+ | Hỗ trợ 240K ký tự
        </p>
    </div>

    <!-- Main Container -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        
        <!-- Left: Input Script -->
        <div class="glass-card rounded-2xl p-6">
            <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
                <i class="fa-solid fa-file-lines text-purple-400"></i> Nhập Kịch Bản
            </h2>
            
            <textarea id="scriptInput" 
                      placeholder="Nhập kịch bản của bạn vào đây... Mỗi scene kết thúc bằng dấu chấm (.) sẽ tự động tách ra.&#10;&#10;VD:&#10;Bạn đang tìm kiếm một cách để làm giàu nhanh chóng. Warren Buffett từng nói: Hãy đầu tư vào bản thân. Đây là bí quyết của các tỷ phú. Họ không dừng lại ở việc kiếm tiền, mà còn học cách quản lý nó..."
                      class="w-full h-96 bg-black/50 border border-white/10 rounded-xl p-4 text-sm text-white outline-none focus:border-purple-500/50 resize-none"
                      maxlength="240000"></textarea>
            
            <div class="flex items-center justify-between mt-3 text-xs text-slate-400">
                <span id="charCount">0 ký tự</span>
                <span id="wordCount">0 từ</span>
                <span id="sceneCount">0 scenes</span>
            </div>

            <!-- Visual Style Selection -->
            <div class="mt-6">
                <label class="text-sm font-bold text-slate-300 mb-3 block flex items-center gap-2">
                    <i class="fa-solid fa-palette text-purple-400"></i> Chọn Phong Cách Visual
                </label>
                <select id="visualStyleSelect" class="w-full bg-black/50 border border-white/10 rounded-xl p-3 text-sm text-white outline-none focus:border-purple-500/50">
                    <!-- Will be populated by JS -->
                </select>
            </div>

            <!-- 🌍 LANGUAGE/MARKET SELECTION -->
            <div class="mt-4">
                <label class="text-sm font-bold text-slate-300 mb-3 block flex items-center gap-2">
                    <i class="fa-solid fa-globe text-cyan-400"></i> Chọn Thị Trường / Ngôn Ngữ
                </label>
                <select id="languageSelect" class="w-full bg-black/50 border border-white/10 rounded-xl p-3 text-sm text-white outline-none focus:border-cyan-500/50">
                    <!-- Will be populated by JS -->
                </select>
                <p class="text-[10px] text-slate-500 mt-2">
                    <i class="fa-solid fa-info-circle mr-1"></i> 
                    Tất cả output (Voice, Prompts, SEO) sẽ được tạo theo ngôn ngữ này
                </p>
            </div>

            <!-- Generate Button -->
            <button id="btnGenerate" onclick="handleGenerate()" 
                    class="w-full mt-6 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-500 hover:to-pink-500 text-white font-bold py-4 rounded-xl transition-all shadow-lg flex items-center justify-center gap-2">
                <i class="fa-solid fa-wand-magic-sparkles"></i> Tạo Prompts & SEO
            </button>
        </div>

        <!-- Right: Output Preview -->
        <div class="glass-card rounded-2xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold flex items-center gap-2">
                    <i class="fa-solid fa-sparkles text-pink-400"></i> Kết Quả
                </h2>
                <div class="flex items-center gap-2">
                    <span id="outputLangBadge" class="text-[10px] px-2 py-1 bg-cyan-900/30 text-cyan-300 border border-cyan-500/30 rounded-full font-bold hidden">
                        <i class="fa-solid fa-globe mr-1"></i> <span id="outputLangText">Việt Nam</span>
                    </span>
                    <button onclick="exportToJSON()" class="px-3 py-2 bg-blue-900/40 hover:bg-blue-800/50 text-blue-300 rounded-lg text-xs font-bold border border-blue-500/20 transition-all">
                        <i class="fa-solid fa-download"></i> JSON
                    </button>
                    <button onclick="exportToExcel()" class="px-3 py-2 bg-green-900/40 hover:bg-green-800/50 text-green-300 rounded-lg text-xs font-bold border border-green-500/20 transition-all">
                        <i class="fa-solid fa-file-excel"></i> Excel
                    </button>
                </div>
            </div>
            
            <div id="outputContainer" class="space-y-4 max-h-[600px] overflow-y-auto">
                <div class="text-center text-slate-500 py-20 italic">
                    <i class="fa-solid fa-magic mb-3 text-4xl opacity-50"></i>
                    <p>Nhập kịch bản và nhấn "Tạo Prompts" để bắt đầu</p>
                </div>
            </div>
        </div>

    </div>

    <!-- SEO Section (Full Width) -->
    <div id="seoSection" class="hidden glass-card rounded-2xl p-6">
        <h2 class="text-2xl font-bold mb-6 flex items-center gap-2">
            <i class="fa-solid fa-rocket text-yellow-400"></i> SEO & Marketing Assets
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Title & Description -->
            <div class="space-y-4">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-sm font-bold text-slate-300">📌 Tiêu Đề Viral (CTR 40%+)</label>
                        <button onclick="copySEOField('title')" class="text-[10px] px-2 py-1 bg-pink-900/30 hover:bg-pink-800/40 text-pink-300 rounded border border-pink-500/30 transition-all">
                            <i class="fa-solid fa-copy"></i> Copy
                        </button>
                    </div>
                    <div id="seoTitle" class="bg-black/50 border border-white/10 rounded-xl p-4 text-white text-sm"></div>
                </div>
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-sm font-bold text-slate-300">📝 Mô Tả (Description)</label>
                        <button onclick="copySEOField('description')" class="text-[10px] px-2 py-1 bg-purple-900/30 hover:bg-purple-800/40 text-purple-300 rounded border border-purple-500/30 transition-all">
                            <i class="fa-solid fa-copy"></i> Copy
                        </button>
                    </div>
                    <div id="seoDescription" class="bg-black/50 border border-white/10 rounded-xl p-4 text-white text-sm h-32 overflow-y-auto"></div>
                </div>
            </div>

            <!-- Hashtags & Keywords -->
            <div class="space-y-4">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-sm font-bold text-slate-300">#️⃣ Hashtags</label>
                        <button onclick="copySEOField('hashtags')" class="text-[10px] px-2 py-1 bg-blue-900/30 hover:bg-blue-800/40 text-blue-300 rounded border border-blue-500/30 transition-all">
                            <i class="fa-solid fa-copy"></i> Copy
                        </button>
                    </div>
                    <div id="seoHashtags" class="bg-black/50 border border-white/10 rounded-xl p-4 text-blue-400 text-sm"></div>
                </div>
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-sm font-bold text-slate-300">🔑 Keywords</label>
                        <button onclick="copySEOField('keywords')" class="text-[10px] px-2 py-1 bg-green-900/30 hover:bg-green-800/40 text-green-300 rounded border border-green-500/30 transition-all">
                            <i class="fa-solid fa-copy"></i> Copy
                        </button>
                    </div>
                    <div id="seoKeywords" class="bg-black/50 border border-white/10 rounded-xl p-4 text-green-400 text-sm"></div>
                </div>
            </div>

            <!-- Thumbnail Prompt (Full Width) -->
            <div class="md:col-span-2">
                <div class="flex items-center justify-between mb-2">
                    <label class="text-sm font-bold text-slate-300">🖼️ Thumbnail Prompt (CTR 40%+)</label>
                    <button onclick="copySEOField('thumbnail')" class="text-[10px] px-2 py-1 bg-yellow-900/30 hover:bg-yellow-800/40 text-yellow-300 rounded border border-yellow-500/30 transition-all">
                        <i class="fa-solid fa-copy"></i> Copy
                    </button>
                </div>
                <div id="seoThumbnail" class="bg-black/50 border border-white/10 rounded-xl p-4 text-white text-sm"></div>
            </div>
        </div>
    </div>

</div>

<!-- Settings Modal -->
<div id="settingsModal" class="hidden fixed inset-0 z-[9999] flex items-center justify-center bg-black/80 backdrop-blur-sm">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto m-4">
        <div class="sticky top-0 bg-slate-900 border-b border-slate-700 p-6 flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-key text-purple-400"></i> API Configuration
                </h2>
                <p class="text-xs text-slate-400 mt-1">Cấu hình Gemini 2.5 + OpenRouter + OpenAI backup</p>
            </div>
            <div class="flex items-center gap-3">
                <button onclick="resetAPIState()" class="text-xs px-3 py-1.5 bg-yellow-900/30 hover:bg-yellow-800/40 text-yellow-300 rounded-lg border border-yellow-500/30 transition-all">
                    <i class="fa-solid fa-rotate-right"></i> Reset API State
                </button>
                <button onclick="toggleSettings()" class="text-slate-400 hover:text-white text-2xl w-8 h-8 flex items-center justify-center">×</button>
            </div>
        </div>
        <div class="p-6">
            <div id="keyInputsContainer"></div>
        </div>
    </div>
</div>

<script>
// ==================================================================================
// CONFIGURATION & CONSTANTS
// ==================================================================================
const MODELS = { 
    text: "gemini-2.5-flash",
    text_backup: "gemini-2.5-flash-lite",
    text_pro: "gemini-2.5-pro",
    // OpenRouter FREE models
    openrouter_free: [
        "google/gemini-2.0-flash-exp:free",
        "google/gemini-exp-1206:free",
        "meta-llama/llama-3.3-70b-instruct:free",
        "deepseek/deepseek-chat:free",
        "mistralai/mistral-7b-instruct:free"
    ],
    // OpenRouter PAID models
    openrouter_paid: [
        "anthropic/claude-3.5-sonnet",
        "openai/gpt-4o",
        "anthropic/claude-3-opus",
        "openai/o1",
        "google/gemini-pro-1.5",
        "anthropic/claude-3.5-haiku",
        "openai/gpt-4-turbo",
        "x-ai/grok-2-1212",
        "perplexity/llama-3.1-sonar-large-128k-online",
        "meta-llama/llama-3.3-70b-instruct"
    ],
    openrouter_default: "anthropic/claude-3.5-sonnet"
};

const API_STATE = {
    exhaustedKeys: new Set(),
    keyLastUsed: new Map(),
    keyErrorCount: new Map(),
    lastGeminiError: null,
    openRouterModelIndex: 0,
    totalGeminiFailures: 0,
    backupApiActive: false,
    currentActiveApi: 'google', // 'google', 'openrouter', 'openai'
    lastBackupSwitch: null
};

// Load persistent API state from localStorage
function loadAPIState() {
    try {
        const saved = localStorage.getItem('scriptpro_api_state');
        if (saved) {
            const data = JSON.parse(saved);
            if (data.exhaustedKeys) API_STATE.exhaustedKeys = new Set(data.exhaustedKeys);
            if (data.currentActiveApi) API_STATE.currentActiveApi = data.currentActiveApi;
            if (data.backupApiActive !== undefined) API_STATE.backupApiActive = data.backupApiActive;
            if (data.totalGeminiFailures) API_STATE.totalGeminiFailures = data.totalGeminiFailures;
            if (data.lastBackupSwitch) API_STATE.lastBackupSwitch = new Date(data.lastBackupSwitch);
        }
    } catch (e) {
        console.warn('Failed to load API state:', e);
    }
}

// Save API state to localStorage
function saveAPIState() {
    try {
        const data = {
            exhaustedKeys: Array.from(API_STATE.exhaustedKeys),
            currentActiveApi: API_STATE.currentActiveApi,
            backupApiActive: API_STATE.backupApiActive,
            totalGeminiFailures: API_STATE.totalGeminiFailures,
            lastBackupSwitch: API_STATE.lastBackupSwitch
        };
        localStorage.setItem('scriptpro_api_state', JSON.stringify(data));
    } catch (e) {
        console.warn('Failed to save API state:', e);
    }
}

// Reset exhausted keys after 1 hour
function resetExhaustedKeysIfNeeded() {
    if (API_STATE.lastBackupSwitch) {
        const hoursSinceSwitch = (Date.now() - API_STATE.lastBackupSwitch.getTime()) / (1000 * 60 * 60);
        if (hoursSinceSwitch > 1) {
            console.log('⏰ 1 hour passed, resetting exhausted Gemini keys...');
            API_STATE.exhaustedKeys.clear();
            API_STATE.totalGeminiFailures = 0;
            API_STATE.backupApiActive = false;
            API_STATE.currentActiveApi = 'google';
            saveAPIState();
        }
    }
}

const STORE = {
    keyPool: [],
    currentKeyIndex: 0,
    openRouterKey: '',
    openRouterModel: MODELS.openrouter_default,
    openAiKey: '',
    openAiModel: 'gpt-4-turbo-preview',
    apiEnabled: {
        google: true,
        openrouter: false,
        openai: false
    }
};

// VISUAL STYLES (Copy từ financend.php - chỉ lấy 10 styles phổ biến nhất)
const VISUAL_STYLES = [
    { id: 'auto', name: '✨ AI Auto', desc: 'AI tự chọn phong cách phù hợp' },
    { id: 'trading_floor', name: '📊 Trading Floor Pro', desc: 'Sàn giao dịch chuyên nghiệp' },
    { id: 'vector_fox_business', name: '🦊 Fox Business', desc: 'Cáo ranh mãnh - Vector Character' },
    { id: 'stickman_finance', name: '👤 Stickman Finance', desc: 'Người que viral explainer' },
    { id: 'anime_finance_shonen', name: '🎬 Anime Shonen', desc: 'Anime năng lượng cao' },
    { id: 'cartoon_2d_flat', name: '🖼️ Cartoon 2D Flat', desc: 'Kurzgesagt style' },
    { id: 'crypto_neon', name: '⚡ Crypto Neon', desc: 'Tiền mã hóa futuristic' },
    { id: 'wall_street_power', name: '🏙️ Wall Street', desc: 'Phố Wall quyền lực' },
    { id: 'boardroom_elite', name: '🏢 Boardroom Elite', desc: 'Phòng họp cấp cao' },
    { id: 'ceo_vision', name: '👔 CEO Vision', desc: 'Tầm nhìn lãnh đạo' }
];

// 🌍 LANGUAGE/MARKET OPTIONS (17 markets)
const LANGUAGE_MARKETS = [
    { id: 'vi', name: '🇻🇳 Tiếng Việt', lang: 'Vietnamese', code: 'vi-VN', region: 'Vietnam', seo_note: 'YouTube Việt Nam, trending Việt' },
    { id: 'en-us', name: '🇺🇸 English (US)', lang: 'English', code: 'en-US', region: 'United States', seo_note: 'US market, American trends' },
    { id: 'en-uk', name: '🇬🇧 English (UK)', lang: 'English', code: 'en-GB', region: 'United Kingdom', seo_note: 'UK market, British trends' },
    { id: 'th', name: '🇹🇭 ภาษาไทย (Thai)', lang: 'Thai', code: 'th-TH', region: 'Thailand', seo_note: 'Thai market, เทรนด์ไทย' },
    { id: 'id', name: '🇮🇩 Bahasa Indonesia', lang: 'Indonesian', code: 'id-ID', region: 'Indonesia', seo_note: 'Indonesia market, trending Indo' },
    { id: 'ph', name: '🇵🇭 Filipino/Tagalog', lang: 'Filipino', code: 'fil-PH', region: 'Philippines', seo_note: 'Philippines market, Pinoy trends' },
    { id: 'ja', name: '🇯🇵 日本語 (Japanese)', lang: 'Japanese', code: 'ja-JP', region: 'Japan', seo_note: 'Japan market, 日本のトレンド' },
    { id: 'ko', name: '🇰🇷 한국어 (Korean)', lang: 'Korean', code: 'ko-KR', region: 'South Korea', seo_note: 'Korea market, 한국 트렌드' },
    { id: 'zh-cn', name: '🇨🇳 中文简体 (Chinese)', lang: 'Chinese Simplified', code: 'zh-CN', region: 'China/Singapore', seo_note: 'Mandarin market, 中国趋势' },
    { id: 'zh-tw', name: '🇹🇼 中文繁體 (Taiwan)', lang: 'Chinese Traditional', code: 'zh-TW', region: 'Taiwan/HK', seo_note: 'Taiwan/HK market, 台灣趨勢' },
    { id: 'es', name: '🇪🇸 Español (Spanish)', lang: 'Spanish', code: 'es-ES', region: 'Spain/LATAM', seo_note: 'Spanish market, tendencias' },
    { id: 'pt', name: '🇵🇹 Português (Portuguese)', lang: 'Portuguese', code: 'pt-BR', region: 'Brazil/Portugal', seo_note: 'Portuguese market, tendências Brasil' },
    { id: 'fr', name: '🇫🇷 Français (French)', lang: 'French', code: 'fr-FR', region: 'France', seo_note: 'French market, tendances françaises' },
    { id: 'de', name: '🇩🇪 Deutsch (German)', lang: 'German', code: 'de-DE', region: 'Germany', seo_note: 'German market, deutsche Trends' },
    { id: 'ru', name: '🇷🇺 Русский (Russian)', lang: 'Russian', code: 'ru-RU', region: 'Russia', seo_note: 'Russian market, российские тренды' },
    { id: 'hi', name: '🇮🇳 हिन्दी (Hindi)', lang: 'Hindi', code: 'hi-IN', region: 'India', seo_note: 'India market, भारतीय ट्रेंड' },
    { id: 'ar', name: '🇸🇦 العربية (Arabic)', lang: 'Arabic', code: 'ar-SA', region: 'Middle East', seo_note: 'Arabic market, الاتجاهات العربية' }
];

// UI State for scenes and SEO
const UI_STATE = {
    scenes: [],
    seoData: null,
    currentLang: null // 🌍 Track current language
};

// ==================================================================================
// INITIALIZATION
// ==================================================================================
document.addEventListener('DOMContentLoaded', () => {
    loadKeys();
    loadAPIState(); // ✅ Load API state from localStorage
    resetExhaustedKeysIfNeeded(); // ✅ Reset exhausted keys if >1 hour passed
    updateKeyBadge(); // ✅ Update badge immediately on page load
    updateAPIStatusBadge(); // ✅ Show current active API
    
    // Populate visual styles
    const select = document.getElementById('visualStyleSelect');
    VISUAL_STYLES.forEach(style => {
        const option = document.createElement('option');
        option.value = style.id;
        option.textContent = `${style.name} - ${style.desc}`;
        select.appendChild(option);
    });

    // 🌍 Populate language/market options
    const langSelect = document.getElementById('languageSelect');
    LANGUAGE_MARKETS.forEach(market => {
        const option = document.createElement('option');
        option.value = market.id;
        option.textContent = `${market.name} - ${market.region}`;
        langSelect.appendChild(option);
    });

    // Character counter
    document.getElementById('scriptInput').addEventListener('input', (e) => {
        const text = e.target.value;
        const charCount = text.length;
        const wordCount = text.trim().split(/\s+/).filter(w => w.length > 0).length;
        const scenes = text.split('.').filter(s => s.trim().length > 10).length;
        
        document.getElementById('charCount').textContent = `${charCount.toLocaleString()} ký tự`;
        document.getElementById('wordCount').textContent = `${wordCount.toLocaleString()} từ`;
        document.getElementById('sceneCount').textContent = `${scenes} scenes`;
    });
});

// ==================================================================================
// KEY MANAGEMENT & UTILS
// ==================================================================================
function loadKeys() {
    const stored = localStorage.getItem('scriptpro_key_pool');
    if (stored) {
        try { STORE.keyPool = JSON.parse(stored); } catch (e) { STORE.keyPool = []; }
    }
    STORE.openRouterKey = localStorage.getItem('scriptpro_openrouter_key') || '';
    STORE.openRouterModel = localStorage.getItem('scriptpro_openrouter_model') || MODELS.openrouter_default;
    STORE.openAiKey = localStorage.getItem('scriptpro_openai_key') || '';
    STORE.openAiModel = localStorage.getItem('scriptpro_openai_model') || 'gpt-4-turbo-preview';
    const storedApiEnabled = localStorage.getItem('scriptpro_api_enabled');
    if (storedApiEnabled) { try { STORE.apiEnabled = JSON.parse(storedApiEnabled); } catch (e) {} }
}

function saveKeys() {
    localStorage.setItem('scriptpro_key_pool', JSON.stringify(STORE.keyPool));
    localStorage.setItem('scriptpro_openrouter_key', STORE.openRouterKey);
    localStorage.setItem('scriptpro_openrouter_model', STORE.openRouterModel);
    localStorage.setItem('scriptpro_openai_key', STORE.openAiKey);
    localStorage.setItem('scriptpro_openai_model', STORE.openAiModel);
    localStorage.setItem('scriptpro_api_enabled', JSON.stringify(STORE.apiEnabled));
    updateKeyBadge();
}

function getNextKey() {
    if (STORE.keyPool.length === 0 || (STORE.keyPool.length === 1 && !STORE.keyPool[0])) return '';
    const nextIndex = (STORE.currentKeyIndex + 1) % STORE.keyPool.length;
    STORE.currentKeyIndex = nextIndex;
    return STORE.keyPool[STORE.currentKeyIndex];
}

function updateKeyBadge() {
    const count = STORE.keyPool.filter(k => k && k.trim() !== '').length;
    const badge = document.getElementById('keyCountBadge');
    badge.innerText = count;
    badge.className = `ml-1 px-1.5 py-0.5 rounded-full text-[9px] ${count > 0 ? 'bg-green-500/20 text-green-400 border border-green-500/30' : 'bg-slate-800 text-slate-400'}`;
}

// Update API status badge showing which API is currently active
function updateAPIStatusBadge() {
    const badge = document.getElementById('apiStatusBadge');
    if (!badge) return;
    
    let icon = '🔴', text = 'Google', color = 'red';
    
    if (API_STATE.backupApiActive) {
        if (API_STATE.currentActiveApi === 'openrouter') {
            icon = '🟢'; text = 'OpenRouter'; color = 'green';
        } else if (API_STATE.currentActiveApi === 'openai') {
            icon = '🤖'; text = 'OpenAI'; color = 'blue';
        }
    }
    
    badge.innerHTML = `<span class="text-[9px] px-2 py-0.5 bg-${color}-500/20 text-${color}-300 border border-${color}-500/30 rounded-full">${icon} ${text}</span>`;
}

function toggleSettings() {
    const modal = document.getElementById('settingsModal');
    modal.classList.toggle('hidden');
    if (!modal.classList.contains('hidden')) renderKeyInputs();
}

function renderKeyInputs() {
    const container = document.getElementById('keyInputsContainer');
    container.innerHTML = '';
    
    function createToggle(apiName, label, color) {
        const isEnabled = STORE.apiEnabled[apiName];
        const statusColor = isEnabled ? 'bg-green-500' : 'bg-red-500';
        const statusText = isEnabled ? 'ON' : 'OFF';
        const section = document.createElement('div');
        section.className = `bg-${color}-900/10 border border-${color}-500/20 rounded-xl p-4 mb-4`;
        section.innerHTML = `
            <div class="flex justify-between items-center mb-3">
                <div class="flex items-center gap-2">
                    <div class="text-xs font-bold text-${color}-400 uppercase">${label}</div>
                    <div class="px-2 py-0.5 rounded-full text-[9px] font-bold ${statusColor} text-white">${statusText}</div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" ${isEnabled ? 'checked' : ''} onchange="toggleAPI('${apiName}')" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-700 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-${color}-600"></div>
                </label>
            </div>
            <div id="${apiName}-config" class="${isEnabled ? '' : 'opacity-50 pointer-events-none'}"></div>
        `;
        return section;
    }
    
    // Google Gemini 2.5
    const googleSection = createToggle('google', '🔴 Google Gemini 2.5 Flash (Priority 1)', 'red');
    container.appendChild(googleSection);
    const googleConfig = document.getElementById('google-config');
    
    // Show key status
    const validKeysCount = STORE.keyPool.filter(k => k && k.trim() !== '').length;
    if (validKeysCount > 0) {
        const statusDiv = document.createElement('div');
        statusDiv.className = 'mb-3 px-3 py-2 bg-green-900/20 border border-green-500/30 rounded-lg flex items-center gap-2';
        statusDiv.innerHTML = `
            <i class="fa-solid fa-check-circle text-green-400"></i>
            <span class="text-xs text-green-300 font-bold">${validKeysCount} API key(s) đã được nhận</span>
        `;
        googleConfig.appendChild(statusDiv);
    }
    
    STORE.keyPool.forEach((k, i) => {
        const hasKey = k && k.trim() !== '';
        const div = document.createElement('div');
        div.className = 'flex gap-2 mb-2';
        div.innerHTML = `
            <div class="flex-1 relative">
                <input type="password" value="${k}" onchange="updateKey(${i}, this.value)" class="w-full bg-black border border-red-900/40 rounded p-2 pr-8 text-xs font-mono text-red-200 placeholder-white/20 focus:border-red-500/50 outline-none" placeholder="Gemini Key ${i+1}...">
                ${hasKey ? '<i class="fa-solid fa-check-circle text-green-400 absolute right-2 top-1/2 -translate-y-1/2"></i>' : ''}
            </div>
            <button onclick="removeKey(${i})" class="text-red-400 hover:bg-red-900/20 p-2 rounded"><i class="fa-solid fa-trash"></i></button>
        `;
        googleConfig.appendChild(div);
    });
    const addKeyBtn = document.createElement('button');
    addKeyBtn.onclick = addKeyInput;
    addKeyBtn.className = 'text-xs text-red-400 hover:text-red-300 flex items-center gap-1 mt-1';
    addKeyBtn.innerHTML = '<i class="fa-solid fa-plus"></i> Add Key';
    googleConfig.appendChild(addKeyBtn);
    
    // OpenRouter
    const orSection = createToggle('openrouter', '🟢 OpenRouter (Backup - 5 Free + 10 Paid)', 'green');
    container.appendChild(orSection);
    const orConfig = document.getElementById('openrouter-config');
    
    const paidModels = MODELS.openrouter_paid || [];
    const modelIcons = {
        'anthropic/claude-3.5-sonnet': '💎',
        'openai/gpt-4o': '🚀',
        'anthropic/claude-3-opus': '🏆',
        'openai/o1': '🧠',
        'google/gemini-pro-1.5': '✨',
        'anthropic/claude-3.5-haiku': '⚡',
        'openai/gpt-4-turbo': '🔥',
        'x-ai/grok-2-1212': '🤖',
        'perplexity/llama-3.1-sonar-large-128k-online': '🌐',
        'meta-llama/llama-3.3-70b-instruct': '🦙'
    };
    
    const modelOptions = paidModels.map(model => {
        const icon = modelIcons[model] || '🤖';
        const displayName = model.split('/')[1].replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        const isSelected = STORE.openRouterModel === model ? 'selected' : '';
        return `<option value="${model}" ${isSelected}>${icon} ${displayName}</option>`;
    }).join('');
    
    orConfig.innerHTML = `
        <div class="mb-3 px-3 py-2 bg-green-900/10 border border-green-500/20 rounded-lg">
            <div class="text-[10px] text-green-300 font-bold mb-1">💡 Chi phí tiết kiệm</div>
            <div class="text-[9px] text-green-400">Tự động thử 5 FREE models trước → Chỉ dùng PAID models khi free models hết</div>
        </div>
        <div class="mb-2">
            <div class="text-[10px] text-green-300 mb-1 flex items-center justify-between">
                <span>API Key (Optional - có key = unlock paid models)</span>
                ${STORE.openRouterKey && STORE.openRouterKey.trim() !== '' ? '<span class="text-[9px] px-2 py-0.5 bg-green-500/20 text-green-300 border border-green-500/30 rounded-full flex items-center gap-1"><i class="fa-solid fa-check-circle"></i> Đã nhận</span>' : ''}
            </div>
            <input type="password" value="${STORE.openRouterKey}" onchange="updateOpenRouterKey(this.value)" class="w-full bg-black border border-green-900/40 rounded p-2 text-xs font-mono text-green-200" placeholder="sk-or-...">
        </div>
        <div class="mb-2">
            <div class="text-[10px] text-green-300 mb-1">Preferred Paid Model (khi free models fail)</div>
            <select id="openRouterModelSelect" onchange="updateOpenRouterModel(this.value)" class="w-full bg-black border border-green-900/40 rounded p-2 text-xs text-green-200">
                ${modelOptions}
            </select>
        </div>
    `;
    
    // OpenAI
    const oaSection = createToggle('openai', '🤖 OpenAI (Fallback)', 'blue');
    container.appendChild(oaSection);
    const oaConfig = document.getElementById('openai-config');
    oaConfig.innerHTML = `
        <div class="mb-2">
            <div class="text-[10px] text-blue-300 mb-1 flex items-center justify-between">
                <span>API Key</span>
                ${STORE.openAiKey && STORE.openAiKey.trim() !== '' ? '<span class="text-[9px] px-2 py-0.5 bg-blue-500/20 text-blue-300 border border-blue-500/30 rounded-full flex items-center gap-1"><i class="fa-solid fa-check-circle"></i> Đã nhận</span>' : ''}
            </div>
            <input type="password" value="${STORE.openAiKey}" onchange="updateOpenAiKey(this.value)" class="w-full bg-black border border-blue-900/40 rounded p-2 text-xs font-mono text-blue-200" placeholder="sk-...">
        </div>
        <div>
            <div class="text-[10px] text-blue-300 mb-1">Model</div>
            <select onchange="updateOpenAiModel(this.value)" class="w-full bg-black border border-blue-900/40 rounded p-2 text-xs text-blue-200">
                <option value="gpt-4-turbo-preview" ${STORE.openAiModel === 'gpt-4-turbo-preview' ? 'selected' : ''}>GPT-4 Turbo</option>
                <option value="gpt-4o" ${STORE.openAiModel === 'gpt-4o' ? 'selected' : ''}>GPT-4o</option>
                <option value="gpt-4" ${STORE.openAiModel === 'gpt-4' ? 'selected' : ''}>GPT-4</option>
            </select>
        </div>
    `;
}

function toggleAPI(apiName) { 
    STORE.apiEnabled[apiName] = !STORE.apiEnabled[apiName]; 
    saveKeys(); 
    renderKeyInputs(); 
}

function resetAPIState() {
    if (!confirm('Reset API state? Sẽ xóa tất cả exhausted keys và chuyển về Google Gemini.')) return;
    
    API_STATE.exhaustedKeys.clear();
    API_STATE.totalGeminiFailures = 0;
    API_STATE.backupApiActive = false;
    API_STATE.currentActiveApi = 'google';
    API_STATE.lastBackupSwitch = null;
    saveAPIState();
    updateAPIStatusBadge();
    
    showSuccess('✅ API State đã được reset! Quay về Google Gemini.');
}

function updateOpenRouterKey(val) { STORE.openRouterKey = val; saveKeys(); }
function updateOpenRouterModel(val) { STORE.openRouterModel = val; saveKeys(); }
function updateOpenAiKey(val) { STORE.openAiKey = val; saveKeys(); }
function updateOpenAiModel(val) { STORE.openAiModel = val; saveKeys(); }
function updateKey(index, value) { STORE.keyPool[index] = value; saveKeys(); }
function addKeyInput() { STORE.keyPool.push(''); saveKeys(); renderKeyInputs(); }
function removeKey(index) { 
    STORE.keyPool = STORE.keyPool.filter((_, idx) => idx !== index); 
    if (!STORE.keyPool.length) STORE.keyPool = ['']; 
    saveKeys(); 
    renderKeyInputs(); 
}

function showError(msg) {
    alert('❌ ' + msg);
}

function showSuccess(msg) {
    const toast = document.createElement('div');
    toast.className = 'fixed top-4 left-1/2 -translate-x-1/2 z-[9999] bg-green-900/95 border border-green-500/50 text-green-100 px-6 py-3 rounded-xl shadow-lg';
    toast.textContent = '✅ ' + msg;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 2000);
}

// ==================================================================================
// JSON PARSER
// ==================================================================================
function safeJSONParse(str) {
    if (!str) return null;
    
    let cleanStr = str.replace(/```json/gi, '').replace(/```/g, '').trim();
    const firstBrace = cleanStr.indexOf('{');
    const firstBracket = cleanStr.indexOf('[');
    let startIndex = -1;
    let endIndex = -1;
    
    if (firstBrace !== -1 && (firstBracket === -1 || firstBrace < firstBracket)) { 
        startIndex = firstBrace; 
        endIndex = cleanStr.lastIndexOf('}'); 
    } else if (firstBracket !== -1) { 
        startIndex = firstBracket; 
        endIndex = cleanStr.lastIndexOf(']'); 
    }
    
    if (startIndex !== -1 && endIndex !== -1) {
        cleanStr = cleanStr.substring(startIndex, endIndex + 1);
    } else {
        throw new Error("Invalid JSON structure");
    }

    try { 
        return JSON.parse(cleanStr); 
    } catch (e) {
        console.warn('⚠️ JSON parse failed, attempting repair...', e.message);
        
        try {
            let repaired = cleanStr;
            repaired = repaired.replace(/,\s*([}\]])/g, '$1');
            
            if (repaired.startsWith('{')) {
                const openBraces = (repaired.match(/\{/g) || []).length;
                const closeBraces = (repaired.match(/\}/g) || []).length;
                if (openBraces > closeBraces) {
                    const lastComma = repaired.lastIndexOf(',');
                    const lastCloseBrace = repaired.lastIndexOf('}');
                    if (lastComma > lastCloseBrace) {
                        repaired = repaired.substring(0, lastComma);
                    }
                    repaired += '}'.repeat(openBraces - closeBraces);
                }
            }
            
            const parsed = JSON.parse(repaired);
            console.log('✅ JSON repair successful!');
            return parsed;
        } catch (repairError) {
            throw new Error(`JSON Parse Error: ${e.message}`);
        }
    }
}

// ==================================================================================
// MAIN GENERATION FUNCTION
// ==================================================================================
async function handleGenerate() {
    const scriptText = document.getElementById('scriptInput').value.trim();
    const visualStyle = document.getElementById('visualStyleSelect').value;
    const selectedLang = document.getElementById('languageSelect').value;
    
    // 🌍 Get language data
    const langData = LANGUAGE_MARKETS.find(m => m.id === selectedLang) || LANGUAGE_MARKETS[0];
    
    if (!scriptText) {
        alert('❌ Vui lòng nhập kịch bản!');
        return;
    }

    const btn = document.getElementById('btnGenerate');
    btn.disabled = true;
    btn.innerHTML = `<i class="fa-solid fa-sync fa-spin"></i> Đang xử lý (${langData.name})...`;

    try {
        // Step 1: Split into scenes (improved logic based on words, not just periods)
        let rawScenes = scriptText.split('.').filter(s => s.trim().length > 0);
        
        // Filter scenes: Minimum 5 words per scene (more reliable than 10 characters)
        const scenes = rawScenes
            .map(s => s.trim() + '.')
            .filter(s => {
                const wordCount = s.trim().split(/\s+/).filter(w => w.length > 0).length;
                return wordCount >= 5; // At least 5 words = meaningful scene
            });
        
        if (scenes.length === 0) {
            throw new Error('Không tìm thấy scene hợp lệ! Mỗi scene cần ít nhất 5 từ.');
        }

        console.log(`✅ Phát hiện ${scenes.length} scenes hợp lệ`);
        UI_STATE.scenes = [];
        UI_STATE.currentLang = langData; // 🌍 Save current language
        
        // 🌍 Update output language badge
        document.getElementById('outputLangBadge').classList.remove('hidden');
        document.getElementById('outputLangText').textContent = langData.name;

        // Step 2: Generate prompts for each scene
        for (let i = 0; i < scenes.length; i++) {
            btn.innerHTML = `<i class="fa-solid fa-sync fa-spin"></i> Scene ${i + 1}/${scenes.length} (${langData.lang})...`;
            
            const voiceText = scenes[i];
            const prompts = await generatePromptsForScene(voiceText, visualStyle, i + 1, langData);
            
            UI_STATE.scenes.push({
                scene_number: i + 1,
                voice_text: voiceText,
                video_prompt: prompts.video_prompt,
                image_prompt: prompts.image_prompt
            });

            renderScenes();
        }

        // Step 3: Generate SEO
        btn.innerHTML = `<i class="fa-solid fa-sync fa-spin"></i> Đang tạo SEO (${langData.lang})...`;
        const fullScript = scenes.join(' ');
        UI_STATE.seoData = await generateSEO(fullScript, langData);
        renderSEO();

        btn.innerHTML = '<i class="fa-solid fa-check"></i> Hoàn thành!';
        setTimeout(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> Tạo Prompts & SEO';
        }, 2000);

    } catch (error) {
        console.error(error);
        alert('❌ Lỗi: ' + error.message);
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> Tạo Prompts & SEO';
    }
}

// ==================================================================================
// AI GENERATION FUNCTIONS (MULTI-API SUPPORT)
// ==================================================================================
async function callAI(prompt, systemPrompt = '') {
    const anyEnabled = STORE.apiEnabled.google || STORE.apiEnabled.openrouter || STORE.apiEnabled.openai;
    if (!anyEnabled) {
        throw new Error("❌ Vui lòng BẬT ít nhất 1 API trong Config! (Click 'API Config' ở góc trên)");
    }
    
    const hasGoogleKeys = STORE.keyPool.some(k => k && k.trim() !== '');
    const hasOpenRouterKey = STORE.openRouterKey && STORE.openRouterKey.trim() !== '';
    const hasOpenAiKey = STORE.openAiKey && STORE.openAiKey.trim() !== '';
    
    // Validate: At least ONE enabled API must have valid keys
    const googleReady = STORE.apiEnabled.google && hasGoogleKeys;
    const openrouterReady = STORE.apiEnabled.openrouter; // OpenRouter có free models, không cần key
    const openaiReady = STORE.apiEnabled.openai && hasOpenAiKey;
    
    if (!googleReady && !openrouterReady && !openaiReady) {
        let errorMsg = "❌ Không có API nào sẵn sàng!\n\n";
        if (STORE.apiEnabled.google && !hasGoogleKeys) errorMsg += "• Google: BẬT nhưng CHƯA có key\n";
        if (STORE.apiEnabled.openrouter) errorMsg += "• OpenRouter: Sẵn sàng (dùng free models)\n";
        if (STORE.apiEnabled.openai && !hasOpenAiKey) errorMsg += "• OpenAI: BẬT nhưng CHƯA có key\n";
        errorMsg += "\n👉 Vui lòng thêm API keys trong Config!";
        throw new Error(errorMsg);
    }
    
    // Check if all Gemini keys are exhausted (based on persistent state)
    const allGeminiKeysExhausted = googleReady && API_STATE.exhaustedKeys.size >= STORE.keyPool.filter(k => k && k.trim() !== '').length;
    
    // ✅ PRIORITY 1: Google Gemini 2.5 Flash (ALWAYS try first if enabled, unless ALL keys proven exhausted)
    if (googleReady && !allGeminiKeysExhausted) {
        console.log('🔴 PRIORITY 1: Trying Google Gemini 2.5 (FREE)...');
        try {
            const result = await callGoogleWithRetry(prompt, systemPrompt);
            API_STATE.totalGeminiFailures = 0;
            API_STATE.currentActiveApi = 'google';
            API_STATE.backupApiActive = false; // Reset backup flag on success
            saveAPIState();
            console.log('✅ Google Gemini SUCCESS');
            return result;
        } catch (e) {
            console.warn("❌ Google Gemini Failed:", e.message);
            API_STATE.lastGeminiError = e.message;
            API_STATE.totalGeminiFailures++;
            
            // Detect quota exhaustion or repeated failures
            const isQuotaError = e.message.includes('429') || e.message.includes('Quota') || e.message.includes('ALL_KEYS_EXHAUSTED');
            const tooManyFailures = API_STATE.totalGeminiFailures >= 3;
            
            if (isQuotaError || tooManyFailures) {
                console.warn('⚠️ Gemini exhausted, will try backup APIs for this call...');
                API_STATE.backupApiActive = true;
                API_STATE.lastBackupSwitch = new Date();
                saveAPIState();
                updateAPIStatusBadge();
            }
        }
    } else if (googleReady && allGeminiKeysExhausted) {
        console.log('⏭️ Skipping Gemini: All keys exhausted (will auto-reset after 1 hour)');
    }
    
    // ✅ PRIORITY 2: OpenRouter FREE models (try when Gemini fails or exhausted)
    if (openrouterReady) {
        console.log('🟢 PRIORITY 2: Trying OpenRouter (FREE models first)...');
        try {
            const result = await callOpenRouterWithFallback(prompt, systemPrompt);
            API_STATE.currentActiveApi = 'openrouter';
            saveAPIState();
            updateAPIStatusBadge();
            console.log('✅ OpenRouter SUCCESS');
            return result;
        } catch (orError) { 
            console.warn("❌ OpenRouter Failed:", orError.message);
        }
    }
    
    // ✅ PRIORITY 3: OpenAI (final fallback)
    if (openaiReady) {
        console.log('🤖 PRIORITY 3: Trying OpenAI (PAID fallback)...');
        try {
            const result = await callOpenAI(prompt, systemPrompt);
            API_STATE.currentActiveApi = 'openai';
            API_STATE.backupApiActive = true;
            saveAPIState();
            updateAPIStatusBadge();
            console.log('✅ OpenAI SUCCESS');
            return result;
        } catch (oaError) { 
            console.warn("❌ OpenAI Failed:", oaError.message);
        }
    }
    
    // Build detailed error message
    let finalError = "❌ Tất cả API đã thử đều thất bại!\n\n";
    if (googleReady) finalError += "• Google Gemini: " + (API_STATE.lastGeminiError || "Unknown error") + "\n";
    if (openrouterReady) finalError += "• OpenRouter: Tất cả models đều failed\n";
    if (openaiReady) finalError += "• OpenAI: Connection error\n";
    finalError += "\n💡 Kiểm tra:\n1. API keys có hợp lệ?\n2. Keys có còn quota?\n3. Thử tắt/bật lại APIs trong Config";
    
    throw new Error(finalError);
}

async function callGoogleWithRetry(prompt, systemPrompt, retries = 3) {
    let lastError;
    let validKeyFound = false;
    
    for (let i = 0; i < retries; i++) {
        const apiKey = getNextKey();
        
        if (!apiKey || apiKey.trim() === '') {
            lastError = new Error('❌ Không có Gemini API key! Vui lòng thêm key.');
            break;
        }
        
        validKeyFound = true;
        
        try {
            const url = `https://generativelanguage.googleapis.com/v1beta/models/${MODELS.text}:generateContent?key=${apiKey}`;
            const body = { 
                contents: [{ role: "user", parts: [{ text: prompt }] }], 
                systemInstruction: systemPrompt ? { parts: [{ text: systemPrompt }] } : undefined,
                generationConfig: { 
                    responseMimeType: "application/json", 
                    maxOutputTokens: 2048, 
                    temperature: 0.75, 
                    topP: 0.95 
                } 
            };
            const res = await fetch(url, { 
                method: 'POST', 
                headers: {'Content-Type': 'application/json'}, 
                body: JSON.stringify(body) 
            });
            
            // Fast 429 detection
            if (res.status === 429) {
                lastError = new Error("429 Gemini Quota Exceeded");
                console.warn(`⚠️ Key ${i+1} hết quota, thử key tiếp...`);
                API_STATE.exhaustedKeys.add(apiKey);
                
                if (API_STATE.exhaustedKeys.size >= STORE.keyPool.length) {
                    throw new Error("ALL_KEYS_EXHAUSTED: Gemini quota exceeded");
                }
                continue;
            }
            
            if (!res.ok) { 
                const errText = await res.text(); 
                lastError = new Error(`Google Error ${res.status}: ${errText.substring(0, 200)}`);
                if (i < retries - 1) await new Promise(r => setTimeout(r, 500));
                continue;
            }
            
            const data = await res.json();
            if (!data.candidates || !data.candidates[0] || !data.candidates[0].content) {
                lastError = new Error("Invalid Gemini Response");
                continue;
            }
            
            const finishReason = data.candidates[0].finishReason;
            if (finishReason === 'SAFETY') {
                lastError = new Error('Response blocked by safety filters');
                continue;
            }
            
            const rawText = data.candidates[0].content.parts[0].text;
            return safeJSONParse(rawText);
            
        } catch (e) {
            lastError = e;
            console.warn(`❌ Attempt ${i+1}/${retries} failed:`, e.message);
            
            if (i < retries - 1) {
                const delay = e.message.includes('429') ? 1000 : 500;
                await new Promise(r => setTimeout(r, delay));
            }
        }
    }
    
    if (!validKeyFound) {
        throw new Error('❌ KHÔNG CÓ GEMINI KEY! Vui lòng thêm key.');
    }
    throw lastError || new Error(`Tất cả Gemini keys thất bại sau ${retries} lần thử.`);
}

async function callOpenRouterWithFallback(prompt, systemPrompt) {
    const freeModels = MODELS.openrouter_free || [];
    const paidModels = MODELS.openrouter_paid || [];
    const hasKey = STORE.openRouterKey && STORE.openRouterKey.trim() !== '';
    
    // ✅ PRIORITY 1: Try FREE models FIRST (tiết kiệm chi phí)
    console.log('🆓 OpenRouter FREE: Trying free models first...');
    for (let i = 0; i < freeModels.length; i++) {
        const modelIndex = (API_STATE.openRouterModelIndex + i) % freeModels.length;
        const model = freeModels[modelIndex];
        try {
            console.log(`🆓 Trying: ${model}`);
            const result = await callOpenRouterModel(prompt, systemPrompt, model);
            API_STATE.openRouterModelIndex = modelIndex;
            console.log(`✅ SUCCESS (FREE): ${model}`);
            return result;
        } catch (e) { 
            console.warn(`❌ ${model} failed:`, e.message);
        }
    }
    
    // ✅ PRIORITY 2: Fallback to PAID models if has key (chỉ khi free models hết)
    if (hasKey && paidModels.length > 0) {
        console.log('💵 OpenRouter PAID: Free models failed, trying paid models...');
        for (const model of paidModels) {
            try {
                console.log(`💵 Trying: ${model}`);
                const result = await callOpenRouterModel(prompt, systemPrompt, model);
                console.log(`✅ SUCCESS (PAID): ${model}`);
                return result;
            } catch (e) {
                console.warn(`❌ ${model} failed:`, e.message);
            }
        }
    }
    
    throw new Error('❌ All OpenRouter models failed');
}

async function callOpenRouterModel(prompt, systemPrompt, model) {
    const url = "https://openrouter.ai/api/v1/chat/completions";
    const body = { 
        model: model, 
        messages: [
            { role: "system", content: systemPrompt || 'You are a helpful AI assistant.' }, 
            { role: "user", content: prompt }
        ], 
        response_format: { type: "json_object" }, 
        max_tokens: 4000, 
        temperature: 0.75 
    };
    const headers = { 
        'Content-Type': 'application/json', 
        'HTTP-Referer': 'https://ndgroup.vn', 
        'X-Title': 'HSHOP ScriptPro' 
    };
    if (STORE.openRouterKey && STORE.openRouterKey.trim() !== '') {
        headers['Authorization'] = `Bearer ${STORE.openRouterKey}`;
    }
    const res = await fetch(url, { 
        method: 'POST', 
        headers: headers, 
        body: JSON.stringify(body) 
    });
    if (!res.ok) { 
        const err = await res.text(); 
        throw new Error(`OpenRouter Error ${res.status}: ${err.substring(0, 200)}`); 
    }
    const data = await res.json();
    return safeJSONParse(data.choices[0].message.content);
}

async function callOpenAI(prompt, systemPrompt) {
    const url = "https://api.openai.com/v1/chat/completions";
    const body = { 
        model: STORE.openAiModel || 'gpt-4-turbo-preview', 
        messages: [
            { role: "system", content: systemPrompt || 'You are a helpful AI assistant.' }, 
            { role: "user", content: prompt }
        ], 
        response_format: { type: "json_object" }, 
        temperature: 0.75 
    };
    const res = await fetch(url, { 
        method: 'POST', 
        headers: { 
            'Authorization': `Bearer ${STORE.openAiKey}`, 
            'Content-Type': 'application/json' 
        }, 
        body: JSON.stringify(body) 
    });
    if (!res.ok) { 
        const err = await res.text(); 
        throw new Error(`OpenAI Error: ${err}`); 
    }
    const data = await res.json();
    return safeJSONParse(data.choices[0].message.content);
}

async function generatePromptsForScene(voiceText, visualStyle, sceneNumber, langData) {
    const styleData = VISUAL_STYLES.find(s => s.id === visualStyle) || VISUAL_STYLES[0];
    
    const prompt = `Bạn là AI tạo prompts cho video/image generation.

**LANGUAGE/MARKET:** ${langData.lang} (${langData.region})
**Language Code:** ${langData.code}

**NHIỆM VỤ:** Tạo 2 prompts cho scene sau:

**Voice Text (Kịch bản):**
"${voiceText}"

**Visual Style:** ${styleData.name} - ${styleData.desc}

**YÊU CẦU OUTPUT (JSON):**
{
  "video_prompt": "English prompt for video generation (70-100 words). Mô tả hành động, góc máy, lighting, style: ${styleData.desc}. 8k, no text, no watermark.",
  "image_prompt": "English prompt for still image (50-80 words). Tương tự video_prompt nhưng tập trung vào composition."
}

**CRITICAL RULES:**
- Video_prompt và Image_prompt: LUÔN bằng tiếng Anh (để dùng cho AI video/image generation)
- BÁM SÁT voice_text để tạo visual phù hợp
- Video_prompt: Mô tả action, movement, camera angle
- Image_prompt: Mô tả composition, framing, visual elements
- Áp dụng visual style: ${styleData.desc}
- Không dùng tên người nổi tiếng, chỉ mô tả ngoại hình
- Kết thúc bằng: "8k quality, no text, no watermark"

ONLY return valid JSON, no markdown, no explanation.`;

    const systemPrompt = 'You are an expert prompt engineer for AI video/image generation. Generate precise, detailed prompts in English.';
    
    return await callAI(prompt, systemPrompt);
}

async function generateSEO(fullScript, langData) {
    const prompt = `Bạn là chuyên gia SEO YouTube cho thị trường ${langData.region}.

**TARGET MARKET:** ${langData.lang} (${langData.region})
**Language Code:** ${langData.code}
**SEO Note:** ${langData.seo_note}

**NHIỆM VỤ:** Tạo SEO assets cho video với kịch bản sau:

"${fullScript.substring(0, 5000)}..."

**YÊU CẦU OUTPUT (JSON):**
{
  "title": "Tiêu đề viral bằng ${langData.lang} (50-70 ký tự). Dùng số, emoji, power words. CTR 40%+. PHẢI VIẾT BẰNG ${langData.lang.toUpperCase()}!",
  "description": "Mô tả 3-4 dòng bằng ${langData.lang}. Hook đầu, benefit giữa, CTA cuối. PHẢI VIẾT BẰNG ${langData.lang.toUpperCase()}!",
  "hashtags": "10-15 hashtags trending cho thị trường ${langData.region}, cách nhau dấu cách. Dùng hashtags phổ biến tại ${langData.region}!",
  "keywords": "15-20 keywords SEO bằng ${langData.lang}, ngăn cách bằng dấu phẩy. PHẢI VIẾT BẰNG ${langData.lang.toUpperCase()}!",
  "thumbnail_prompt": "English prompt for thumbnail image (CTR 40%+). Bold text overlay concept in ${langData.lang}, contrasting colors, facial expression/emotion, 8k, YouTube thumbnail style."
}

**CRITICAL RULES - ĐỌC KỸ:**
1. title: VIẾT BẰNG ${langData.lang.toUpperCase()} - Dùng số (VD: "7", "10"), emoji, từ mạnh trong ${langData.lang}
2. description: VIẾT BẰNG ${langData.lang.toUpperCase()} - Hook gây tò mò, benefit rõ ràng, CTA cuối
3. hashtags: Dùng hashtags TRENDING tại ${langData.region} - Kết hợp hashtags ${langData.lang} và tiếng Anh phổ biến
4. keywords: VIẾT BẰNG ${langData.lang.toUpperCase()} - Từ khóa người ${langData.region} thường tìm kiếm
5. thumbnail_prompt: Tiếng Anh nhưng text overlay concept phải bằng ${langData.lang}

ONLY return valid JSON, no markdown.`;

    const systemPrompt = `You are a TOP YouTube SEO expert specializing in ${langData.region} market with >40% CTR track record. Generate viral titles, descriptions in ${langData.lang}, and thumbnail concepts optimized for ${langData.region} audience.`;
    
    return await callAI(prompt, systemPrompt);
}

// ==================================================================================
// RENDER FUNCTIONS
// ==================================================================================
function renderScenes() {
    const container = document.getElementById('outputContainer');
    container.innerHTML = '';

    UI_STATE.scenes.forEach((scene, idx) => {
        const card = document.createElement('div');
        card.className = 'scene-card bg-black/50 border border-white/10 rounded-xl p-4';
        card.innerHTML = `
            <div class="flex items-start gap-3 mb-3">
                <div class="w-8 h-8 bg-purple-600/20 border border-purple-500/30 rounded-lg flex items-center justify-center flex-shrink-0">
                    <span class="text-xs font-bold text-purple-400">${scene.scene_number}</span>
                </div>
                <div class="flex-1">
                    <!-- Voice Text -->
                    <div class="mb-3">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-bold text-slate-300">🗣️ Voice Text</span>
                            <button onclick="copyText(\`${scene.voice_text.replace(/`/g, '\\`').replace(/\$/g, '\\$')}\`, 'Voice')" class="text-[10px] px-2 py-1 bg-slate-700/50 hover:bg-slate-600/50 text-slate-300 rounded border border-slate-600/30 transition-all">
                                <i class="fa-solid fa-copy"></i> Copy
                            </button>
                        </div>
                        <p class="text-sm text-slate-300 bg-slate-800/30 rounded p-2">${scene.voice_text}</p>
                    </div>
                    
                    <!-- Video Prompt -->
                    <div class="mb-3">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-bold text-green-400">📹 Video Prompt</span>
                            <button onclick="copyText(\`${scene.video_prompt.replace(/`/g, '\\`').replace(/\$/g, '\\$')}\`, 'Video')" class="text-[10px] px-2 py-1 bg-green-900/30 hover:bg-green-800/40 text-green-300 rounded border border-green-500/30 transition-all">
                                <i class="fa-solid fa-copy"></i> Copy
                            </button>
                        </div>
                        <p class="text-xs text-slate-400 bg-slate-900/50 rounded p-2">${scene.video_prompt}</p>
                    </div>
                    
                    <!-- Image Prompt -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-bold text-blue-400">🖼️ Image Prompt</span>
                            <button onclick="copyText(\`${scene.image_prompt.replace(/`/g, '\\`').replace(/\$/g, '\\$')}\`, 'Image')" class="text-[10px] px-2 py-1 bg-blue-900/30 hover:bg-blue-800/40 text-blue-300 rounded border border-blue-500/30 transition-all">
                                <i class="fa-solid fa-copy"></i> Copy
                            </button>
                        </div>
                        <p class="text-xs text-slate-400 bg-slate-900/50 rounded p-2">${scene.image_prompt}</p>
                    </div>
                </div>
            </div>
        `;
        container.appendChild(card);
    });
}

function copyText(text, type) {
    navigator.clipboard.writeText(text).then(() => {
        showSuccess(`${type} prompt copied!`);
    }).catch(err => {
        console.error('Copy failed:', err);
        // Fallback method
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        showSuccess(`${type} prompt copied!`);
    });
}

function copySEOField(fieldName) {
    let text = '';
    let label = '';
    
    switch(fieldName) {
        case 'title':
            text = UI_STATE.seoData?.title || '';
            label = 'Title';
            break;
        case 'description':
            text = UI_STATE.seoData?.description || '';
            label = 'Description';
            break;
        case 'hashtags':
            text = UI_STATE.seoData?.hashtags || '';
            label = 'Hashtags';
            break;
        case 'keywords':
            text = UI_STATE.seoData?.keywords || '';
            label = 'Keywords';
            break;
        case 'thumbnail':
            text = UI_STATE.seoData?.thumbnail_prompt || '';
            label = 'Thumbnail Prompt';
            break;
    }
    
    if (!text) {
        showError('No content to copy!');
        return;
    }
    
    navigator.clipboard.writeText(text).then(() => {
        showSuccess(`✅ ${label} copied!`);
    }).catch(err => {
        console.error('Copy failed:', err);
        // Fallback method
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        showSuccess(`✅ ${label} copied!`);
    });
}

function renderSEO() {
    document.getElementById('seoSection').classList.remove('hidden');
    document.getElementById('seoTitle').textContent = UI_STATE.seoData.title;
    document.getElementById('seoDescription').textContent = UI_STATE.seoData.description;
    document.getElementById('seoHashtags').textContent = UI_STATE.seoData.hashtags;
    document.getElementById('seoKeywords').textContent = UI_STATE.seoData.keywords;
    document.getElementById('seoThumbnail').textContent = UI_STATE.seoData.thumbnail_prompt;
}

// ==================================================================================
// EXPORT FUNCTIONS
// ==================================================================================
function exportToJSON() {
    const data = {
        scenes: UI_STATE.scenes,
        seo: UI_STATE.seoData,
        language: UI_STATE.currentLang ? {
            id: UI_STATE.currentLang.id,
            name: UI_STATE.currentLang.name,
            lang: UI_STATE.currentLang.lang,
            region: UI_STATE.currentLang.region
        } : null,
        generated_at: new Date().toISOString()
    };
    
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `scriptpro_${Date.now()}.json`;
    a.click();
}

function exportToExcel() {
    let csv = 'Scene,Voice Text,Video Prompt,Image Prompt\n';
    
    UI_STATE.scenes.forEach(scene => {
        csv += `${scene.scene_number},"${scene.voice_text.replace(/"/g, '""')}","${scene.video_prompt.replace(/"/g, '""')}","${scene.image_prompt.replace(/"/g, '""')}"\n`;
    });
    
    const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `scriptpro_${Date.now()}.csv`;
    a.click();
}
</script>

</body>
</html>
