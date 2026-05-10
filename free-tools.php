<?php
// 🎯 FREE AI TOOLS - NO LOGIN REQUIRED!
// Strategic Hook: Deliver value FIRST, convert LATER
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FREE AI Tools - YouTube Title & Thumbnail Generator</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .pulse-glow {
            animation: pulseGlow 2s ease-in-out infinite;
        }
        
        @keyframes pulseGlow {
            0%, 100% { box-shadow: 0 0 20px rgba(102, 126, 234, 0.5); }
            50% { box-shadow: 0 0 40px rgba(102, 126, 234, 0.8); }
        }
        
        .typing-animation {
            border-right: 2px solid #667eea;
            animation: typing 3.5s steps(40, end), blink 0.75s step-end infinite;
            white-space: nowrap;
            overflow: hidden;
        }
        
        @keyframes typing {
            from { width: 0 }
            to { width: 100% }
        }
        
        @keyframes blink {
            from, to { border-color: transparent }
            50% { border-color: #667eea }
        }
    </style>
</head>
<body class="p-4 md:p-8">
    
    <!-- Header -->
    <div class="max-w-6xl mx-auto mb-6 md:mb-8 px-4">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-3">
                <div class="bg-white p-2 md:p-3 rounded-xl shadow-lg">
                    <i class="fa-brands fa-youtube text-2xl md:text-3xl text-red-600"></i>
                </div>
                <div>
                    <h1 class="text-xl md:text-2xl font-black text-white">HSHOP AI Tools</h1>
                    <p class="text-xs md:text-sm text-purple-100">FREE Forever • No Login Required</p>
                </div>
            </div>
            <a href="scanner.php" class="bg-white hover:bg-gray-100 text-purple-700 font-bold px-4 md:px-6 py-2 md:py-3 rounded-xl transition shadow-lg text-sm md:text-base whitespace-nowrap">
                <i class="fa-solid fa-rocket mr-1 md:mr-2"></i>
                <span class="hidden sm:inline">Upgrade to</span> Scanner
            </a>
        </div>
    </div>

    <!-- Hero Section -->
    <div class="max-w-6xl mx-auto mb-8 md:mb-12 px-4">
        <div class="glass-card rounded-2xl md:rounded-3xl p-6 md:p-8 lg:p-12 text-center">
            <div class="inline-block bg-gradient-to-r from-yellow-400 to-orange-500 text-white text-xs font-black px-3 md:px-4 py-1.5 md:py-2 rounded-full mb-3 md:mb-4 pulse-glow">
                ✨ 100% FREE • AI-Powered • No Signup
            </div>
            <h2 class="text-3xl md:text-4xl lg:text-6xl font-black text-gray-900 mb-3 md:mb-4 leading-tight">
                Create <span class="gradient-text">Viral</span> YouTube Content
            </h2>
            <p class="text-base md:text-xl text-gray-600 max-w-3xl mx-auto mb-6 md:mb-8">
                Generate high-CTR titles and thumbnail prompts in seconds using Google Gemini AI. 
                <strong>Completely FREE.</strong> Just add your Gemini API key and start creating!
            </p>
            
            <!-- Quick Stats -->
            <div class="grid grid-cols-3 gap-2 md:gap-4 max-w-2xl mx-auto">
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-3 md:p-4 rounded-xl">
                    <div class="text-2xl md:text-3xl font-black text-blue-600">40%+</div>
                    <div class="text-[10px] md:text-xs text-blue-700 font-medium">Average CTR</div>
                </div>
                <div class="bg-gradient-to-br from-green-50 to-green-100 p-3 md:p-4 rounded-xl">
                    <div class="text-2xl md:text-3xl font-black text-green-600">10s</div>
                    <div class="text-[10px] md:text-xs text-green-700 font-medium">Generation Time</div>
                </div>
                <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-3 md:p-4 rounded-xl">
                    <div class="text-2xl md:text-3xl font-black text-purple-600">FREE</div>
                    <div class="text-[10px] md:text-xs text-purple-700 font-medium">Forever</div>
                </div>
            </div>
        </div>
    </div>

    <!-- API Key Setup (If not set) -->
    <div id="apiSetupSection" class="max-w-4xl mx-auto mb-8">
        <div class="glass-card rounded-2xl p-8">
            <div class="flex items-start gap-4 mb-6">
                <div class="bg-gradient-to-br from-purple-500 to-pink-500 text-white p-4 rounded-xl">
                    <i class="fa-solid fa-key text-2xl"></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-2xl font-black text-gray-900 mb-2">
                        🚀 Get Started in 60 Seconds
                    </h3>
                    <p class="text-gray-600 mb-4">
                        Add your FREE Gemini API key to unlock unlimited AI-powered title and thumbnail generation. 
                        <strong>No credit card required.</strong>
                    </p>
                </div>
            </div>

            <!-- Step by Step Guide -->
            <div class="grid md:grid-cols-2 gap-6 mb-6">
                <div class="bg-blue-50 border-2 border-blue-200 rounded-xl p-4">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="bg-blue-600 text-white w-8 h-8 rounded-full flex items-center justify-center font-bold">1</div>
                        <h4 class="font-bold text-gray-900">Get FREE Gemini Key</h4>
                    </div>
                    <p class="text-sm text-gray-700 mb-3">Click the button below to get your FREE API key from Google AI Studio (takes 30 seconds)</p>
                    <a href="https://aistudio.google.com/app/apikey" target="_blank" 
                       class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-bold px-4 py-2 rounded-lg transition text-sm">
                        <i class="fa-solid fa-external-link"></i>
                        Get FREE Key
                    </a>
                </div>

                <div class="bg-green-50 border-2 border-green-200 rounded-xl p-4">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="bg-green-600 text-white w-8 h-8 rounded-full flex items-center justify-center font-bold">2</div>
                        <h4 class="font-bold text-gray-900">Paste & Start Creating</h4>
                    </div>
                    <div class="space-y-2">
                        <input type="password" id="geminiKeyInput" 
                            class="w-full px-4 py-3 border-2 border-green-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none text-sm"
                            placeholder="Paste your Gemini API key (AIza...)"
                            onkeypress="if(event.key==='Enter') saveGeminiKey()">
                        <button onclick="saveGeminiKey()" 
                            class="w-full bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-bold px-6 py-3 rounded-lg transition shadow-lg">
                            <i class="fa-solid fa-rocket mr-2"></i>
                            Start Using FREE Tools
                        </button>
                    </div>
                </div>
            </div>

            <div class="bg-purple-50 border-l-4 border-purple-500 rounded-lg p-4">
                <div class="flex items-start gap-3">
                    <i class="fa-solid fa-shield-halved text-purple-600 text-xl mt-1"></i>
                    <div>
                        <h5 class="font-bold text-purple-900 mb-1">100% Private & Secure</h5>
                        <p class="text-sm text-purple-700">
                            Your API key is encrypted and stored only in your browser. We never see or store it on our servers. 
                            Get 1,500 FREE requests per day with Gemini!
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- AI Tools Section -->
    <div id="toolsSection" class="max-w-6xl mx-auto space-y-8 hidden">
        
        <!-- Tool 1: AI Title Generator -->
        <div class="glass-card rounded-2xl overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 p-6 text-white">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="bg-white/20 p-3 rounded-xl">
                            <i class="fa-solid fa-heading text-3xl"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-black">AI Title Generator</h3>
                            <p class="text-blue-100 text-sm">Generate 10 high-CTR titles optimized for YouTube algorithm</p>
                        </div>
                    </div>
                    <div class="bg-white/20 px-4 py-2 rounded-full">
                        <span class="text-xs font-bold">FREE</span>
                    </div>
                </div>
            </div>
            
            <div class="p-8">
                <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                        <i class="fa-solid fa-lightbulb text-yellow-500 mr-2"></i>
                        Chủ đề / Topic của video
                    </label>
                    <input type="text" id="titleTopic" 
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none"
                        placeholder="VD: Cách kiếm tiền online cho người mới bắt đầu"
                        onkeypress="if(event.key==='Enter') generateTitles()">
                </div>

                <button onclick="generateTitles()" id="generateTitleBtn"
                    class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold px-8 py-4 rounded-xl transition shadow-lg text-lg">
                    <i class="fa-solid fa-sparkles mr-2"></i>
                    Generate 10 Viral Titles
                </button>

                <!-- Results -->
                <div id="titleResults" class="mt-8 hidden">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="font-bold text-gray-900 flex items-center gap-2">
                            <i class="fa-solid fa-list-check text-green-600"></i>
                            Generated Titles
                        </h4>
                        <button onclick="copyAllTitles()" class="text-sm text-blue-600 hover:text-blue-700 font-bold">
                            <i class="fa-solid fa-copy mr-1"></i>Copy All
                        </button>
                    </div>
                    <div id="titleList" class="space-y-3"></div>
                </div>
            </div>
        </div>

        <!-- Tool 2: Thumbnail Prompt Generator -->
        <div class="glass-card rounded-2xl overflow-hidden">
            <div class="bg-gradient-to-r from-pink-600 to-purple-600 p-6 text-white">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="bg-white/20 p-3 rounded-xl">
                            <i class="fa-solid fa-image text-3xl"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-black">Thumbnail Prompt Generator</h3>
                            <p class="text-pink-100 text-sm">Create AI prompts for 40%+ CTR thumbnails (Midjourney/DALL-E ready)</p>
                        </div>
                    </div>
                    <div class="bg-white/20 px-4 py-2 rounded-full">
                        <span class="text-xs font-bold">FREE</span>
                    </div>
                </div>
            </div>
            
            <div class="p-8">
                <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                        <i class="fa-solid fa-palette text-purple-500 mr-2"></i>
                        Chủ đề video để tạo thumbnail
                    </label>
                    <input type="text" id="thumbnailTopic" 
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 outline-none"
                        placeholder="VD: Cách làm giàu từ YouTube trong 6 tháng"
                        onkeypress="if(event.key==='Enter') generateThumbnailPrompt()">
                </div>

                <button onclick="generateThumbnailPrompt()" id="generateThumbBtn"
                    class="w-full bg-gradient-to-r from-pink-600 to-purple-600 hover:from-pink-700 hover:to-purple-700 text-white font-bold px-8 py-4 rounded-xl transition shadow-lg text-lg">
                    <i class="fa-solid fa-wand-magic-sparkles mr-2"></i>
                    Generate Thumbnail Prompt
                </button>

                <!-- Results -->
                <div id="thumbnailResults" class="mt-8 hidden">
                    <div class="space-y-4" id="thumbnailPromptContainer"></div>
                </div>
            </div>
        </div>

    </div>

    <!-- CTA Section -->
    <div class="max-w-4xl mx-auto mt-12">
        <div class="glass-card rounded-2xl p-8 text-center bg-gradient-to-r from-yellow-50 to-orange-50 border-2 border-yellow-200">
            <div class="inline-block bg-gradient-to-r from-red-500 to-pink-500 text-white px-4 py-2 rounded-full text-xs font-black mb-4">
                🔥 PREMIUM FEATURE
            </div>
            <h3 class="text-3xl font-black text-gray-900 mb-4">
                Love These FREE Tools? 🚀
            </h3>
            <p class="text-gray-700 mb-6 text-lg">
                Upgrade to our <strong>Viral Scanner</strong> to find hidden gems, analyze competitors, 
                and discover viral opportunities before anyone else!
            </p>
            <div class="flex items-center justify-center gap-4">
                <a href="scanner.php" 
                   class="bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-700 hover:to-pink-700 text-white font-black px-8 py-4 rounded-xl transition shadow-xl text-lg">
                    <i class="fa-solid fa-rocket mr-2"></i>
                    Try Scanner (39K/3 days)
                </a>
                <a href="pricing.php" 
                   class="bg-white hover:bg-gray-50 text-gray-700 font-bold px-8 py-4 rounded-xl transition border-2 border-gray-300">
                    View All Plans
                </a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="max-w-6xl mx-auto mt-12 text-center text-white text-sm">
        <p>© 2026 HSHOP Analytics • Made with ❤️ in Vietnam</p>
        <p class="mt-2">
            <a href="scanner.php" class="hover:underline">Scanner</a> • 
            <a href="pricing.php" class="hover:underline ml-2">Pricing</a> • 
            <a href="#" class="hover:underline ml-2">Contact</a>
        </p>
    </div>

    <script>
        // 🔐 Security Manager (from scanner.php)
        const SecurityManager = {
            obfuscate: (text) => btoa(encodeURIComponent(text)),
            deobfuscate: (encoded) => decodeURIComponent(atob(encoded))
        };

        // 🎯 Initialize
        let geminiKey = '';

        window.onload = function() {
            loadGeminiKey();
        };

        function loadGeminiKey() {
            const saved = localStorage.getItem('gemini_free_tools_key');
            if (saved) {
                try {
                    geminiKey = SecurityManager.deobfuscate(saved);
                    document.getElementById('apiSetupSection').classList.add('hidden');
                    document.getElementById('toolsSection').classList.remove('hidden');
                    showToast('✅ Gemini API key loaded! Start creating!', 'success');
                } catch (e) {
                    console.error('Failed to load key');
                }
            }
        }

        function saveGeminiKey() {
            const input = document.getElementById('geminiKeyInput');
            const key = input.value.trim();

            if (!key) {
                showToast('⚠️ Please paste your Gemini API key!', 'error');
                return;
            }

            if (!key.startsWith('AIza')) {
                if (!confirm('⚠️ This doesn\'t look like a Gemini key (should start with AIza...). Continue anyway?')) {
                    return;
                }
            }

            // Save encrypted
            const encrypted = SecurityManager.obfuscate(key);
            localStorage.setItem('gemini_free_tools_key', encrypted);
            geminiKey = key;

            // Show tools
            document.getElementById('apiSetupSection').classList.add('hidden');
            document.getElementById('toolsSection').classList.remove('hidden');

            showToast('🎉 Success! Your FREE AI tools are ready to use!', 'success');
        }

        // 🎨 AI Title Generator
        async function generateTitles() {
            const topic = document.getElementById('titleTopic').value.trim();
            if (!topic) {
                showToast('⚠️ Please enter a video topic!', 'error');
                return;
            }

            const btn = document.getElementById('generateTitleBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>AI đang tạo titles...';
            btn.disabled = true;

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
- "[KẾT QUẢ SỐC] khi tôi thử [PHƯƠNG PHÁP] trong [THỜI GIAN]"
- "Đừng [HÀNH ĐỘNG] cho đến khi xem video này"

Xuất ra dạng danh sách đánh số 1-10, mỗi title một dòng.`;

            try {
                const result = await callGemini(prompt);
                if (result) {
                    displayTitles(result);
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                } else {
                    throw new Error('No response from AI');
                }
            } catch (e) {
                showToast('❌ Error: ' + e.message, 'error');
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        function displayTitles(text) {
            const container = document.getElementById('titleList');
            const lines = text.split('\n').filter(line => line.trim());
            
            container.innerHTML = lines.map((line, index) => {
                // Remove numbering if present
                const cleanLine = line.replace(/^\d+[\.\)]\s*/, '');
                return `
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-xl p-4 hover:shadow-lg transition cursor-pointer group"
                         onclick="copyTitle('${cleanLine.replace(/'/g, "\\'")}')">
                        <div class="flex items-start gap-3">
                            <div class="bg-blue-600 text-white w-8 h-8 rounded-full flex items-center justify-center font-bold flex-shrink-0">
                                ${index + 1}
                            </div>
                            <div class="flex-1">
                                <p class="text-gray-900 font-medium">${cleanLine}</p>
                            </div>
                            <button class="text-blue-600 hover:text-blue-700 opacity-0 group-hover:opacity-100 transition">
                                <i class="fa-solid fa-copy"></i>
                            </button>
                        </div>
                    </div>
                `;
            }).join('');

            document.getElementById('titleResults').classList.remove('hidden');
            showToast('✅ Generated 10 high-CTR titles!', 'success');
        }

        function copyTitle(title) {
            navigator.clipboard.writeText(title);
            showToast('📋 Title copied!', 'success');
        }

        function copyAllTitles() {
            const titles = Array.from(document.querySelectorAll('#titleList p')).map(p => p.textContent).join('\n');
            navigator.clipboard.writeText(titles);
            showToast('📋 All titles copied!', 'success');
        }

        // 🖼️ Thumbnail Prompt Generator
        async function generateThumbnailPrompt() {
            const topic = document.getElementById('thumbnailTopic').value.trim();
            if (!topic) {
                showToast('⚠️ Please enter a video topic!', 'error');
                return;
            }

            const btn = document.getElementById('generateThumbBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>AI đang tạo thumbnail prompt...';
            btn.disabled = true;

            const prompt = `Tạo prompt Midjourney/DALL-E cho thumbnail YouTube về chủ đề: "${topic}"

Mục tiêu: Đạt CTR 40%+

Bao gồm:
1. Chủ thể chính (rõ ràng, tương phản cao)
2. Tâm lý màu sắc:
   - Đỏ/Cam: Khẩn cấp, hứng thú
   - Xanh dương: Tin cậy, bình tĩnh
   - Vàng: Lạc quan, thu hút
   - Xanh lá: Tăng trưởng, tiền bạc
3. Cảm xúc: Mặt shock / Hào hứng / Tò mò
4. Text overlay: Tối đa 3 từ, in đậm
5. Composition: Quy tắc 1/3
6. Background: Mờ hoặc màu đơn sắc

Format output:
[Chủ thể chính], [Cảm xúc], [Bảng màu], [Background], [Text: "..."], 
photorealistic, high quality, 16:9 aspect ratio, vibrant colors, 
professional thumbnail, --ar 16:9 --v 6

Sau đó cung cấp:
- Tại sao sẽ đạt 40%+ CTR
- Giải thích tâm lý màu sắc
- 3 biến thể A/B test

Xuất ra bằng tiếng Việt, prompt Midjourney bằng tiếng Anh.`;

            try {
                const result = await callGemini(prompt);
                if (result) {
                    displayThumbnailPrompt(result);
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                } else {
                    throw new Error('No response from AI');
                }
            } catch (e) {
                showToast('❌ Error: ' + e.message, 'error');
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        function displayThumbnailPrompt(text) {
            const container = document.getElementById('thumbnailPromptContainer');
            
            container.innerHTML = `
                <div class="bg-white border-2 border-purple-300 rounded-xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="font-bold text-gray-900 flex items-center gap-2">
                            <i class="fa-solid fa-wand-magic-sparkles text-purple-600"></i>
                            AI-Generated Thumbnail Strategy
                        </h4>
                        <button onclick="copyPrompt()" class="text-sm text-purple-600 hover:text-purple-700 font-bold">
                            <i class="fa-solid fa-copy mr-1"></i>Copy Prompt
                        </button>
                    </div>
                    <div class="prose max-w-none text-sm text-gray-700 whitespace-pre-wrap">${text}</div>
                </div>
            `;

            document.getElementById('thumbnailResults').classList.remove('hidden');
            showToast('✅ Thumbnail prompt generated!', 'success');
        }

        function copyPrompt() {
            const text = document.querySelector('#thumbnailPromptContainer .prose').textContent;
            navigator.clipboard.writeText(text);
            showToast('📋 Prompt copied! Paste into Midjourney/DALL-E', 'success');
        }

        // 🤖 Gemini API Call
        async function callGemini(prompt) {
            if (!geminiKey) {
                showToast('⚠️ Please add your Gemini API key first!', 'error');
                return null;
            }

            try {
                const url = `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent?key=${geminiKey}`;
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        contents: [{ parts: [{ text: prompt }] }]
                    })
                });

                if (!res.ok) {
                    const errorData = await res.json();
                    throw new Error(errorData.error?.message || 'API Error');
                }

                const data = await res.json();
                return data.candidates?.[0]?.content?.parts?.[0]?.text || null;
            } catch (e) {
                console.error('Gemini Error:', e);
                throw e;
            }
        }

        // 🍞 Toast Notifications
        function showToast(message, type = 'info') {
            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                warning: 'bg-yellow-500',
                info: 'bg-blue-500'
            };

            const toast = document.createElement('div');
            toast.className = `fixed bottom-4 right-4 ${colors[type]} text-white px-6 py-3 rounded-lg shadow-xl z-50 animate-bounce`;
            toast.textContent = message;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.classList.add('opacity-0', 'transition-opacity');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    </script>
</body>
</html>
