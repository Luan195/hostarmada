<?php
/**
 * Centralized Pricing Data - Single Source of Truth
 * 
 * All pricing pages MUST use this file to ensure consistency
 * Last updated: 2026-03-23
 * 
 * PRICING STRUCTURE (Base: 99K/month) - BIG PLAY STRATEGY:
 * - 1 month:  99.000đ (0% discount)
 * - 3 months: 252.450đ (15% discount = 99K x 3 x 0.85)
 * - 6 months: 415.800đ (30% discount = 99K x 6 x 0.70)
 * - 12 months: 653.400đ (45% discount = 99K x 12 x 0.55)
 */

// PRICING STRATEGY: Strike-through (Original Price → Sale Price)
// This creates psychological anchoring effect

$GLOBAL_PRICING = [
    'trial' => [
        'id' => 'trial',
        'name' => 'Dùng Thử',
        'duration' => '3 ngày',
        'duration_days' => 3,
        'original_price' => 99000,        // Giá gốc cao (anchor)
        'sale_price' => 39000,            // Giá bán thực tế
        'discount_percent' => 61,         // 61% off
        'per_day' => 13000,               // ~13K/ngày
        'searches_per_day' => -1,         // ✅ UNLIMITED
        'color' => 'green',
        'badge' => '🚀 KHỞI ĐẦU',
        'popular' => false,
        'features' => [
            '♾️ Không giới hạn tìm kiếm',
            'Xem đầy đủ kết quả',
            'Phân tích chi tiết',
            'Dùng thử 3 ngày',
            'Tự gắn YouTube API key',
            'Hỗ trợ qua email'
        ],
        'cta' => 'Dùng Thử Ngay'
    ],
    '1m' => [
        'id' => '1m',
        'name' => 'Basic',
        'duration' => '1 tháng',
        'duration_days' => 30,
        'original_price' => 299000,       // Giá gốc (anchor) - từ 299K giảm xuống
        'sale_price' => 99000,            // Giá bán thực tế - GIẢM MẠNH!
        'discount_percent' => 67,         // 67% off - BIG PLAY!
        'per_day' => 3300,                // 99K / 30 = ~3.300đ/ngày
        'searches_per_day' => -1,         // ✅ UNLIMITED
        'color' => 'blue',
        'badge' => '🔥 GIÁ SỐC',
        'popular' => false,
        'features' => [
            '♾️ Không giới hạn tìm kiếm',
            'Xem đầy đủ kết quả',
            'Xuất CSV không giới hạn',
            'Phân tích thumbnail AI',
            'Tự gắn YouTube API key',
            'Hỗ trợ ưu tiên',
            'Hoa hồng CTV 20%'
        ],
        'cta' => 'Mua Ngay'
    ],
    '3m' => [
        'id' => '3m',
        'name' => 'Standard',
        'duration' => '3 tháng',
        'duration_days' => 90,
        'original_price' => 297000,       // 99K x 3 = 297K
        'sale_price' => 252450,           // 99K x 3 x 0.85 (giảm 15%)
        'discount_percent' => 15,         // 15% off
        'per_day' => 2805,                // 252.450 / 90 = ~2.805đ/ngày
        'save_amount' => 44550,           // Tiết kiệm 15% (297K - 252.45K)
        'searches_per_day' => -1,         // ✅ UNLIMITED
        'color' => 'purple',
        'badge' => '💎 TIẾT KIỆM 15%',
        'popular' => false,
        'features' => [
            '♾️ Không giới hạn tìm kiếm',
            'Xem đầy đủ kết quả',
            'Xuất CSV không giới hạn',
            'Phân tích thumbnail AI',
            'Tự gắn YouTube API key',
            'Hỗ trợ ưu tiên',
            'Hoa hồng CTV 20%',
            '💰 Tiết kiệm 44.550đ (15%)'
        ],
        'cta' => 'Mua Ngay'
    ],
    '6m' => [
        'id' => '6m',
        'name' => 'Pro',
        'duration' => '6 tháng',
        'duration_days' => 180,
        'original_price' => 594000,       // 99K x 6 = 594K
        'sale_price' => 415800,           // 99K x 6 x 0.70 (giảm 30%)
        'discount_percent' => 30,         // 30% off
        'per_day' => 2310,                // 415.800 / 180 = ~2.310đ/ngày
        'save_amount' => 178200,          // Tiết kiệm 30% (594K - 415.8K)
        'searches_per_day' => -1,         // ✅ UNLIMITED
        'color' => 'indigo',
        'badge' => '🚀 ƯU ĐÃI 30%',
        'popular' => false,
        'features' => [
            '♾️ Không giới hạn tìm kiếm',
            'Xem đầy đủ kết quả',
            'Xuất CSV không giới hạn',
            'Phân tích thumbnail AI',
            '🧠 Phân tích Deep AI (Gemini)',
            'Tự gắn YouTube API key',
            'Hỗ trợ VIP ưu tiên',
            'Hoa hồng CTV 20%',
            '💰 Tiết kiệm 178.200đ (30%)'
        ],
        'cta' => 'Mua Ngay'
    ],
    '12m' => [
        'id' => '12m',
        'name' => 'Premium',
        'duration' => '12 tháng (1 năm)',
        'duration_days' => 365,
        'original_price' => 1188000,      // 99K x 12 = 1.188K
        'sale_price' => 653400,           // 99K x 12 x 0.55 (giảm 45%)
        'discount_percent' => 45,         // 45% off
        'per_day' => 1790,                // 653.400 / 365 = ~1.790đ/ngày
        'save_amount' => 534600,          // Tiết kiệm 45% (1.188K - 653.4K)
        'searches_per_day' => -1,         // Unlimited
        'color' => 'yellow',
        'badge' => '👑 GIÁ TỐT NHẤT',
        'popular' => true,
        'features' => [
            '♾️ Không giới hạn tìm kiếm',
            'Xem đầy đủ kết quả',
            'Xuất CSV không giới hạn',
            'Phân tích thumbnail AI',
            '🧠 Phân tích Deep AI (Gemini)',
            'Tự gắn YouTube API key',
            'Hỗ trợ VIP ưu tiên',
            'Hoa hồng CTV 20%',
            '💰 Tiết kiệm 534.600đ (45%)'
        ],
        'cta' => 'Mua Ngay - Giá Tốt Nhất'
    ]
];

/**
 * Get pricing plan by ID
 */
function getPricingPlan($planId) {
    global $GLOBAL_PRICING;
    return $GLOBAL_PRICING[$planId] ?? null;
}

/**
 * Get all pricing plans
 */
function getAllPricingPlans() {
    global $GLOBAL_PRICING;
    return $GLOBAL_PRICING;
}

/**
 * Format price in VND
 */
function formatPrice($amount) {
    return number_format($amount, 0, ',', '.') . 'đ';
}
