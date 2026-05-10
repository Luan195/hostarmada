<?php
/**
 * 🧧 LÌ XÌ TẾT 2026 - SPECIAL CAMPAIGN PRICING
 * 
 * Giá ưu đãi đặc biệt dịp Tết Ất Tỵ 2026
 * Campaign period: Linh hoạt theo admin setting
 * 
 * ⚠️ QUAN TRỌNG: File này KHÔNG ẢNH HƯỞNG includes/pricing_data.php (giá thường)
 */

// 🎊 CAMPAIGN DATES - ACTIVE NOW (Updated 2026)
define('TET_CAMPAIGN_START', '2026-02-17 00:00:00'); // Mùng 1 Tết
define('TET_CAMPAIGN_END', '2026-03-15 14:59:59');   // (15/03/2026)

/**
 * Check if Tet campaign is active
 */
function isTetCampaignActive() {
    $now = time();
    $start = strtotime(TET_CAMPAIGN_START);
    $end = strtotime(TET_CAMPAIGN_END);
    return ($now >= $start && $now <= $end);
}

/**
 * Get remaining days of campaign
 */
function getTetCampaignDaysRemaining() {
    if (!isTetCampaignActive()) {
        return 0;
    }
    $now = time();
    $end = strtotime(TET_CAMPAIGN_END);
    $diff = $end - $now;
    return max(0, ceil($diff / 86400)); // Days
}

/**
 * 🧧 LÌ XÌ TẾT 2026 PRICING PLANS
 * 
 * SHOCK PRICING (Aggressive Strategy):
 * - 6 tháng: 805.500đ → 299.000đ (GIẢM 63%)
 * - 12 tháng: 1.290.000đ → 399.000đ (GIẢM 69%)
 * 
 * ✅ Psychological Pricing: < 400K barrier, cheaper than 3-month plan!
 */
$TET_PRICING = [
    'tet_6m' => [
        'id' => 'tet_6m',
        'name' => '🎊 Lì Xì Tết 6 Tháng',
        'duration' => '6 tháng',
        'duration_days' => 180,
        'original_price' => 805500,      // Giá thường (from pricing_data.php)
        'tet_price' => 299000,           // 🧧 GIÁ TẾT (SHOCK PRICE!)
        'discount_percent' => 63,        // 63% off
        'per_day' => 1661,               // 299K / 180 = 1.661đ/ngày
        'save_amount' => 506500,         // Tiết kiệm 506.500đ
        'color' => 'red',
        'badge' => '🎊 LÌ XÌ TẾT',
        'popular' => false,
        'campaign' => 'tet2026',
        'tier' => 'vip',                 // Map to VIP tier
        'features' => [
            '♾️ Không giới hạn tìm kiếm',
            '🔍 Xem đầy đủ kết quả',
            '📊 Xuất CSV không giới hạn',
            '🖼️ Phân tích thumbnail AI',
            '🧠 Phân tích Deep AI (Gemini)',
            '🔑 Tự gắn YouTube API key',
            '⚡ Hỗ trợ VIP ưu tiên',
            '💰 Hoa hồng CTV 20%',
            '🧧 Giá Tết đặc biệt',
            '💸 Tiết kiệm 506.500đ (63%)'
        ],
        'cta' => '🧧 Nhận Lì Xì Ngay'
    ],
    'tet_12m' => [
        'id' => 'tet_12m',
        'name' => '👑 Lì Xì Tết 12 Tháng',
        'duration' => '12 tháng (1 năm)',
        'duration_days' => 365,
        'original_price' => 1290000,     // Giá thường
        'tet_price' => 399000,           // 🧧 GIÁ TẾT (SHOCK PRICE!)
        'discount_percent' => 69,        // 69% off - VIRAL NUMBER!
        'per_day' => 1093,               // 399K / 365 = 1.093đ/ngày (Cheaper than coffee!)
        'save_amount' => 891000,         // Tiết kiệm 891.000đ
        'color' => 'yellow',
        'badge' => '👑 ĐÁNG MUA NHẤT',
        'popular' => true,
        'campaign' => 'tet2026',
        'tier' => 'vip',                 // Map to VIP tier
        'features' => [
            '♾️ Không giới hạn tìm kiếm',
            '🔍 Xem đầy đủ kết quả',
            '📊 Xuất CSV không giới hạn',
            '🖼️ Phân tích thumbnail AI',
            '🧠 Phân tích Deep AI (Gemini)',
            '🔑 Tự gắn YouTube API key',
            '⚡ Hỗ trợ VIP ưu tiên',
            '💰 Hoa hồng CTV 20%',
            '🧧 Giá Tết đặc biệt',
            '👑 Rẻ nhất chỉ 1.093đ/ngày',
            '💸 Tiết kiệm 891.000đ (69%)'
        ],
        'cta' => '🧧 Mua Ngay - Giá Sốc'
    ]
];

/**
 * Get Tet pricing plan by ID
 * 
 * @param string $planId Plan identifier (e.g., 'tet_6m', 'tet_12m')
 * @return array|null Plan data or null if not found
 */
function getTetPricingPlan($planId) {
    global $TET_PRICING;
    return $TET_PRICING[$planId] ?? null;
}

/**
 * Get all Tet pricing plans
 * 
 * @return array All Tet plans
 */
function getAllTetPricingPlans() {
    global $TET_PRICING;
    return $TET_PRICING;
}

/**
 * Format price in VND currency
 * 
 * @param int $amount Amount in VND
 * @return string Formatted price (e.g., "299.000đ")
 */
function formatTetPrice($amount) {
    return number_format($amount, 0, ',', '.') . 'đ';
}

/**
 * Validate if plan is Tet campaign plan
 * 
 * @param string $planId Plan identifier
 * @return bool True if Tet plan
 */
function isTetPlan($planId) {
    return (strpos($planId, 'tet_') === 0);
}

/**
 * Get plan duration in days from plan ID
 * 
 * @param string $planId Plan identifier
 * @return int Duration in days
 */
function getTetPlanDuration($planId) {
    $plan = getTetPricingPlan($planId);
    return $plan ? $plan['duration_days'] : 0;
}

/**
 * Get plan tier from plan ID (for admin orders.php)
 * 
 * @param string $planId Plan identifier
 * @return string Tier name ('vip', 'basic', etc.)
 */
function getTetPlanTier($planId) {
    $plan = getTetPricingPlan($planId);
    return $plan ? ($plan['tier'] ?? 'basic') : 'basic';
}

/**
 * Get countdown timer HTML with urgency styling
 * 
 * @return string HTML countdown display
 */
function getTetCountdownHTML() {
    $daysLeft = getTetCampaignDaysRemaining();
    
    if ($daysLeft <= 0) {
        return '<div class="bg-gray-500 text-white px-4 py-2 rounded-lg inline-block">
                    <i class="fa-solid fa-calendar-xmark mr-2"></i>
                    Chương trình đã kết thúc
                </div>';
    }
    
    // Urgency styling based on days remaining
    $urgencyClass = $daysLeft <= 3 ? 'bg-red-600 animate-pulse' : 'bg-orange-600';
    $urgencyIcon = $daysLeft <= 3 ? 'fa-solid fa-fire' : 'fa-solid fa-clock';
    
    return '<div class="' . $urgencyClass . ' text-white px-6 py-3 rounded-xl inline-block shadow-lg text-lg font-bold">
                <i class="' . $urgencyIcon . ' mr-2"></i>
                Còn ' . $daysLeft . ' ngày để nhận Lì Xì Tết!
            </div>';
}

/**
 * Compare regular pricing vs Tet pricing
 * 
 * ⚠️ FIX: Properly handle pricing_data.php structure
 * 
 * @return array Comparison data
 */
function getTetPricingComparison() {
    // Load pricing_data.php from includes/ directory
    require_once __DIR__ . '/../includes/pricing_data.php';
    
    // ✅ FIX: Access $GLOBAL_PRICING directly (not through function)
    global $GLOBAL_PRICING;
    
    // Safely get regular pricing
    $regular6m = isset($GLOBAL_PRICING['6m']) ? $GLOBAL_PRICING['6m']['sale_price'] : 805500;
    $regular12m = isset($GLOBAL_PRICING['12m']) ? $GLOBAL_PRICING['12m']['sale_price'] : 1290000;
    
    // Get Tet pricing
    $tet6m = getTetPricingPlan('tet_6m')['tet_price'] ?? 299000;
    $tet12m = getTetPricingPlan('tet_12m')['tet_price'] ?? 399000;
    
    return [
        '6m' => [
            'regular' => $regular6m,
            'tet' => $tet6m,
            'save' => $regular6m - $tet6m,
            'discount' => round((($regular6m - $tet6m) / $regular6m) * 100)
        ],
        '12m' => [
            'regular' => $regular12m,
            'tet' => $tet12m,
            'save' => $regular12m - $tet12m,
            'discount' => round((($regular12m - $tet12m) / $regular12m) * 100)
        ]
    ];
}

/**
 * Get Tet campaign label (for display)
 * 
 * @return string Campaign label
 */
function getTetCampaignLabel() {
    return '🧧 LÌ XÌ TẾT 2026';
}

/**
 * Get Tet campaign slogan
 * 
 * @return string Catchy slogan
 */
function getTetCampaignSlogan() {
    return 'Xuân Về - Lộc Đến - Giá Sốc!';
}

?>
