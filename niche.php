<?php
/**
 * ✅ PRICING UPDATE SUMMARY
 * Date: 2026-02-13
 * 
 * NEW PRICING STRUCTURE (Base: 179K/month)
 * ==========================================
 * 
 * 1. TRIAL (3 ngày)
 *    - Giá gốc: 99.000đ
 *    - Giá bán: 39.000đ
 *    - Discount: 61% off
 *    - Per day: ~13.000đ/ngày
 * 
 * 2. BASIC (1 tháng) 
 *    - Giá gốc: 299.000đ
 *    - Giá bán: 179.000đ ⭐ BASE PRICE
 *    - Discount: 40% off
 *    - Per day: ~5.967đ/ngày
 *    - Tiết kiệm: 120.000đ
 * 
 * 3. STANDARD (3 tháng)
 *    - Giá gốc: 537.000đ (179K x 3)
 *    - Giá bán: 483.300đ (179K x 3 x 0.9)
 *    - Discount: 10% off
 *    - Per day: ~5.370đ/ngày
 *    - Tiết kiệm: 53.700đ
 * 
 * 4. PRO (6 tháng) ⭐ NEW TIER
 *    - Giá gốc: 1.074.000đ (179K x 6)
 *    - Giá bán: 805.500đ (179K x 6 x 0.75)
 *    - Discount: 25% off
 *    - Per day: ~4.475đ/ngày
 *    - Tiết kiệm: 268.500đ
 *    - Features: Includes AI Deep Dive (Gemini)
 * 
 * 5. PREMIUM (12 tháng) 👑 MOST POPULAR
 *    - Giá gốc: 2.148.000đ (179K x 12)
 *    - Giá bán: 1.290.000đ (179K x 12 x 0.6)
 *    - Discount: 40% off
 *    - Per day: ~3.534đ/ngày
 *    - Tiết kiệm: 858.000đ
 *    - Features: Includes AI Deep Dive (Gemini)
 * 
 * 
 * FILES UPDATED:
 * ==============
 * 1. ✅ includes/pricing_data.php
 *    - Updated all 4 paid tiers (1m, 3m, 6m, 12m)
 *    - Added new 6-month "Pro" tier
 *    - Recalculated all prices, discounts, per-day rates
 * 
 * 2. ✅ pricing.php
 *    - Hero badge: "68%" → "40%"
 *    - Grid: 4 cols → 5 cols (responsive)
 *    - CTA button: "Tiết Kiệm 68%" → "Tiết Kiệm 40%"
 * 
 * 3. ✅ checkout.php
 *    - No changes needed (uses centralized pricing_data.php)
 *    - Will automatically show 6m tier
 * 
 * 
 * PAYMENT CALCULATION:
 * ====================
 * Base Rate: 179.000đ/month
 * 
 * Discount Tiers:
 * - 1 month:  0% discount  = 179.000đ
 * - 3 months: 10% discount = 179K x 3 x 0.9 = 483.300đ
 * - 6 months: 25% discount = 179K x 6 x 0.75 = 805.500đ
 * - 12 months: 40% discount = 179K x 12 x 0.6 = 1.290.000đ
 * 
 * 
 * FEATURE ENTITLEMENTS:
 * =====================
 * Trial (39K):
 *   - Unlimited searches
 *   - Full results
 *   - Email support
 * 
 * Basic (179K):
 *   + CSV Export
 *   + AI Thumbnail Analysis
 *   + Priority support
 *   + 20% Affiliate commission
 * 
 * Standard (483K):
 *   (Same as Basic)
 *   + Savings badge
 * 
 * Pro (806K): ⭐ NEW
 *   (Same as Standard)
 *   + AI Deep Dive (Gemini)
 *   + VIP support
 * 
 * Premium (1.290K):
 *   (Same as Pro)
 *   + Best value badge
 *   + Maximum savings
 * 
 * 
 * NOTES:
 * ======
 * - All prices include 20% affiliate commission
 * - No refund policy (key-finding service)
 * - Auto-activation within 5-15 minutes
 * - Payment via MB Bank (VietQR)
 */
?>
