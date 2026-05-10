<?php
require_once 'includes/session.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$currentUser = getCurrentUser();
$username = $_SESSION['username'];

// Get user's AI Deep Dive history
$history = getAIDeepDiveHistory($username, 50);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch Sử Phân Tích AI - HSHOP Analytics</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        .animate-slide-in {
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes fadeOut {
            from {
                opacity: 1;
                transform: translateY(0);
            }
            to {
                opacity: 0;
                transform: translateY(-10px);
            }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 to-slate-100 min-h-screen">

    <!-- Header -->
    <header class="bg-white shadow-md py-4">
        <div class="max-w-7xl mx-auto px-4 flex justify-between items-center">
            <a href="index.php" class="text-2xl font-black text-red-600">
                <i class="fa-brands fa-youtube"></i> HSHOP Analytics
            </a>
            <a href="scanner.php" class="text-slate-600 hover:text-slate-900">
                <i class="fa-solid fa-arrow-left mr-2"></i> Quay lại
            </a>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-8">
        
        <!-- Page Title -->
        <div class="mb-8">
            <h1 class="text-4xl font-black text-slate-900 mb-2">💾 Lịch Sử Phân Tích AI</h1>
            <p class="text-slate-600 text-lg">Tất cả các kênh bạn đã phân tích với AI Deep Dive</p>
        </div>
        
        <!-- Action Buttons -->
        <?php if (!empty($history)): ?>
        <div class="mb-6 flex gap-3">
            <button onclick="exportToCSV()" 
                    class="bg-green-600 hover:bg-green-700 text-white font-bold px-6 py-3 rounded-xl transition shadow-lg">
                <i class="fa-solid fa-file-csv mr-2"></i>Xuất CSV
            </button>
            <button onclick="shareResults()" 
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-3 rounded-xl transition shadow-lg">
                <i class="fa-solid fa-share-nodes mr-2"></i>Chia Sẻ Kết Quả
            </button>
        </div>
        <?php endif; ?>

        <!-- History Table -->
        <div class="bg-white rounded-2xl shadow-lg border-2 border-slate-200 overflow-hidden">
            
            <?php if (empty($history)): ?>
            <div class="text-center py-20 text-slate-400">
                <i class="fa-solid fa-folder-open text-6xl mb-4 opacity-50"></i>
                <p class="text-xl font-bold text-slate-600">Chưa có lịch sử phân tích</p>
                <p class="text-sm text-slate-500 mt-2">Bắt đầu phân tích kênh đầu tiên của bạn!</p>
                <a href="scanner.php#deepAnalysisTab" class="inline-block mt-6 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-bold px-8 py-3 rounded-xl hover:shadow-lg transition">
                    <i class="fa-solid fa-brain mr-2"></i>Phân Tích Kênh Ngay
                </a>
            </div>
            <?php else: ?>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b-2 border-slate-200 bg-slate-50">
                        <tr>
                            <th class="py-4 px-4 font-black text-slate-700 uppercase text-xs">Kênh</th>
                            <th class="py-4 px-4 font-black text-slate-700 uppercase text-xs text-center">Subscribers</th>
                            <th class="py-4 px-4 font-black text-slate-700 uppercase text-xs text-center">Videos</th>
                            <th class="py-4 px-4 font-black text-slate-700 uppercase text-xs text-center">Total Views</th>
                            <th class="py-4 px-4 font-black text-slate-700 uppercase text-xs text-right">Ngày phân tích</th>
                            <th class="py-4 px-4 font-black text-slate-700 uppercase text-xs text-center">Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($history as $entry): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="py-4 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="bg-red-100 p-2 rounded-full">
                                        <i class="fa-brands fa-youtube text-red-600"></i>
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900"><?php echo htmlspecialchars($entry['channel_name']); ?></div>
                                        <a href="<?php echo htmlspecialchars($entry['channel_url']); ?>" target="_blank" class="text-xs text-blue-600 hover:underline">
                                            <i class="fa-solid fa-external-link"></i> Xem kênh
                                        </a>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-4 text-center">
                                <span class="font-bold text-slate-700"><?php echo number_format($entry['subscribers'] / 1000, 1); ?>K</span>
                            </td>
                            <td class="py-4 px-4 text-center">
                                <span class="text-slate-600"><?php echo number_format($entry['total_videos']); ?></span>
                            </td>
                            <td class="py-4 px-4 text-center">
                                <span class="text-slate-600"><?php echo number_format($entry['total_views'] / 1000000, 1); ?>M</span>
                            </td>
                            <td class="py-4 px-4 text-right">
                                <span class="text-slate-600"><?php echo date('d/m/Y H:i', strtotime($entry['analyzed_at'])); ?></span>
                            </td>
                            <td class="py-4 px-4 text-center">
                                <button onclick="viewAnalysis('<?php echo $entry['id']; ?>')" 
                                        class="bg-purple-600 hover:bg-purple-700 text-white font-bold px-4 py-2 rounded-lg text-xs transition">
                                    <i class="fa-solid fa-eye"></i> Xem AI
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

    </main>

    <!-- View Analysis Modal -->
    <div id="viewModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b border-slate-200 px-6 py-4 flex justify-between items-center">
                <h3 class="text-2xl font-black text-slate-900">📊 Kết Quả Phân Tích AI</h3>
                <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-times text-2xl"></i>
                </button>
            </div>
            <div id="modalContent" class="p-6">
                <!-- Will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <footer class="bg-slate-900 text-slate-400 py-8 mt-16">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-sm">© <?php echo date('Y'); ?> HSHOP Media. Hotline: <strong class="text-red-500"><?php echo SUPPORT_HOTLINE; ?></strong></p>
        </div>
    </footer>

    <script>
        // Store history data for modal view
        const historyData = <?php echo json_encode($history); ?>;

        // 📊 EXPORT TO CSV
        function exportToCSV() {
            if (historyData.length === 0) {
                alert('Không có dữ liệu để xuất!');
                return;
            }
            
            // Create CSV content
            let csv = '\uFEFF'; // BOM for UTF-8
            csv += 'STT,Tên Kênh,Channel ID,Subscribers,Videos,Total Views,Ngày Phân Tích,URL\n';
            
            historyData.forEach((entry, index) => {
                const row = [
                    index + 1,
                    `"${(entry.channel_name || '').replace(/"/g, '""')}"`,
                    entry.channel_id || '',
                    entry.subscribers || 0,
                    entry.total_videos || 0,
                    entry.total_views || 0,
                    new Date(entry.analyzed_at).toLocaleDateString('vi-VN'),
                    entry.channel_url || ''
                ].join(',');
                csv += row + '\n';
            });
            
            // Download CSV
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `ai_deep_dive_history_${new Date().toISOString().split('T')[0]}.csv`;
            link.click();
            
            showToast('✅ Đã xuất file CSV thành công!', 'success');
        }
        
        // 🔗 SHARE RESULTS
        async function shareResults() {
            if (historyData.length === 0) {
                alert('Không có dữ liệu để chia sẻ!');
                return;
            }
            
            const shareUrl = window.location.href;
            const shareText = `Tôi đã phân tích ${historyData.length} kênh YouTube với AI Deep Dive. Xem ngay!`;
            
            // Check if Web Share API is supported
            if (navigator.share) {
                try {
                    await navigator.share({
                        title: 'Lịch Sử Phân Tích AI - HSHOP Analytics',
                        text: shareText,
                        url: shareUrl
                    });
                    console.log('✅ Shared successfully');
                } catch (err) {
                    console.error('Share failed:', err);
                    fallbackShare();
                }
            } else {
                fallbackShare();
            }
        }
        
        // Fallback share method
        function fallbackShare() {
            const shareUrl = window.location.href;
            const shareText = `Tôi đã phân tích ${historyData.length} kênh YouTube với AI Deep Dive. Xem ngay: ${shareUrl}`;
            
            // Copy to clipboard
            navigator.clipboard.writeText(shareText).then(() => {
                showToast('✅ Đã copy link vào bộ nhớ tạm!', 'success');
            }).catch(err => {
                alert('Copy link này và chia sẻ:\n\n' + shareUrl);
            });
        }
        
        // Show toast notification
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-4 rounded-xl shadow-2xl z-50 font-bold animate-slide-in';
            toast.innerHTML = '<i class="fa-solid fa-check-circle mr-2"></i>' + message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'fadeOut 0.3s ease-out forwards';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
        
        // 🔗 SHARE SINGLE ANALYSIS
        async function shareSingleAnalysis(entryId) {
            const entry = historyData.find(h => h.id === entryId);
            if (!entry) return;
            
            const channelName = entry.channel_name;
            const subscribers = (entry.subscribers / 1000).toFixed(1) + 'K';
            const videos = entry.total_videos;
            const views = (entry.total_views / 1000000).toFixed(1) + 'M';
            
            const shareText = `📊 PHÂN TÍCH AI: ${channelName}\n\n` +
                            `Subscribers: ${subscribers}\n` +
                            `Videos: ${videos}\n` +
                            `Total Views: ${views}\n\n` +
                            `Phân tích bởi HSHOP Analytics - AI Deep Dive tool!`;
            
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
                    fallbackShareSingle(shareText);
                }
            } else {
                fallbackShareSingle(shareText);
            }
        }
        
        function fallbackShareSingle(text) {
            navigator.clipboard.writeText(text).then(() => {
                showToast('✅ Đã copy kết quả vào bộ nhớ tạm!', 'success');
            }).catch(err => {
                alert('Copy nội dung này và chia sẻ:\n\n' + text);
            });
        }

        function viewAnalysis(entryId) {
            const entry = historyData.find(h => h.id === entryId);
            if (!entry) return;

            const modalContent = document.getElementById('modalContent');
            modalContent.innerHTML = `
                <div class="space-y-6">
                    <!-- Channel Info -->
                    <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl p-6 border-2 border-purple-200">
                        <div class="flex items-start gap-4">
                            <div class="bg-red-600 p-4 rounded-xl">
                                <i class="fa-brands fa-youtube text-4xl text-white"></i>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-2xl font-black text-slate-800 mb-2">${entry.channel_name}</h4>
                                <div class="grid grid-cols-3 gap-4 mb-3">
                                    <div class="text-center">
                                        <div class="text-2xl font-black text-purple-600">${(entry.subscribers / 1000).toFixed(1)}K</div>
                                        <div class="text-xs text-slate-500">Subscribers</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-2xl font-black text-blue-600">${entry.total_videos}</div>
                                        <div class="text-xs text-slate-500">Videos</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-2xl font-black text-green-600">${(entry.total_views / 1000000).toFixed(1)}M</div>
                                        <div class="text-xs text-slate-500">Views</div>
                                    </div>
                                </div>
                                <a href="${entry.channel_url}" target="_blank" class="inline-flex items-center text-blue-600 hover:underline text-sm font-bold">
                                    <i class="fa-solid fa-external-link mr-2"></i>Xem kênh trên YouTube
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- AI Analysis -->
                    <div class="bg-white border-2 border-slate-200 rounded-xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="bg-purple-600 p-3 rounded-xl">
                                    <i class="fa-solid fa-brain text-2xl text-white"></i>
                                </div>
                                <h4 class="text-xl font-black text-slate-800">Phân Tích AI</h4>
                            </div>
                            <button onclick="shareSingleAnalysis('${entry.id}')" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-4 py-2 rounded-lg text-sm transition">
                                <i class="fa-solid fa-share mr-2"></i>Chia Sẻ
                            </button>
                        </div>
                        <div class="prose max-w-none">
                            <div class="whitespace-pre-wrap text-slate-700 leading-relaxed">${entry.ai_response}</div>
                        </div>
                    </div>

                    <!-- Timestamp -->
                    <div class="text-center text-slate-500 text-sm">
                        <i class="fa-regular fa-clock mr-2"></i>
                        Phân tích ngày ${new Date(entry.analyzed_at).toLocaleDateString('vi-VN')}
                    </div>
                </div>
            `;

            document.getElementById('viewModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            document.getElementById('viewModal').classList.add('hidden');
            document.body.style.overflow = '';
        }

        // Close modal when clicking outside
        document.getElementById('viewModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Close on ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>

</body>
</html>
