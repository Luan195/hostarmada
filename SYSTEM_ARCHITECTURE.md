# 🏗️ NDGROUP ANALYTICS - KIẾN TRÚC HỆ THỐNG

> **YouTube Niche Discovery & Analytics Platform**  
> Version: 2.0 | Last Updated: 2026-02-13

---

## 📋 MỤC LỤC

1. [Tổng Quan Hệ Thống](#tổng-quan-hệ-thống)
2. [Cấu Trúc File System](#cấu-trúc-file-system)
3. [Luồng Hoạt Động](#luồng-hoạt-động)
4. [Phân Quyền Theo Tier](#phân-quyền-theo-tier)
5. [Database Schema](#database-schema)
6. [Module Quan Trọng](#module-quan-trọng)
7. [Hướng Dẫn Nâng Cấp](#hướng-dẫn-nâng-cấp)

---

## 🎯 TỔNG QUAN HỆ THỐNG

### Kiến Trúc Tổng Thể

```
┌─────────────────────────────────────────────────────────────────────┐
│                     NDGROUP ANALYTICS SYSTEM                        │
│            YouTube Niche Discovery & Analytics Platform             │
└─────────────────────────────────────────────────────────────────────┘
                                    │
                    ┌───────────────┴───────────────┐
                    │                               │
            ┌───────▼────────┐              ┌──────▼──────┐
            │  PUBLIC PAGES  │              │ USER SYSTEM │
            └────────────────┘              └─────────────┘
                    │                               │
        ┌───────────┼───────────┐          ┌───────┼────────┐
        │           │           │          │       │        │
    ┌───▼───┐  ┌───▼────┐  ┌──▼───┐  ┌───▼──┐ ┌──▼───┐ ┌──▼────┐
    │index  │  │pricing │  │login │  │scanner│ │admin │ │affiliate│
    │.php   │  │.php    │  │.php  │  │.php   │ │.php  │ │.php    │
    └───────┘  └────────┘  └──────┘  └───────┘ └──────┘ └────────┘
```

### Tech Stack

- **Backend:** PHP 7.4+
- **Frontend:** Vanilla JavaScript + Tailwind CSS
- **Database:** JSON Files (users.json, orders.json)
- **APIs:** YouTube Data API v3, Google Gemini AI, OpenRouter
- **Payment:** VietQR (MB Bank) + Auto-activation
- **Authentication:** Session-based

---

## 📂 CẤU TRÚC FILE SYSTEM

```
làm lại hệ thống tìm key/
│
├── 🏠 PUBLIC PAGES (Không yêu cầu đăng nhập)
│   ├── index.php                    # Homepage (Hero + Features + Pricing + Footer)
│   ├── pricing.php                  # Bảng giá chi tiết (4 tiers, strike-through)
│   └── login.php                    # Đăng ký / Đăng nhập
│
├── 🔐 USER PAGES (Yêu cầu đăng nhập)
│   ├── scanner.php                  # ⭐ Main tool - Keyword scanner + Analytics
│   ├── niche.php                    # Gold mine niche finder (optional)
│   ├── affiliate.php                # Affiliate dashboard (20% commission)
│   └── checkout.php                 # Payment page (QR code + auto-activation)
│
├── 👨‍💼 ADMIN PAGES (Admin only)
│   ├── admin.php                    # Dashboard (orders, users, revenue)
│   └── admin_api_keys.php          # ⭐ API key pool management
│
├── 🔧 CORE SYSTEM (includes/)
│   ├── session.php                  # Session management + security
│   ├── functions.php                # Helper functions (150+ functions)
│   ├── config.php                   # System config (tiers, features, limits)
│   ├── pricing_data.php            # ⭐ Centralized pricing (single source)
│   └── api_pool.php                # ⭐ Tier-based API allocation (NEW)
│
├── 💾 DATA (JSON Database)
│   ├── users.json                   # User accounts + tiers + API keys
│   ├── orders.json                  # Payment orders + affiliate tracking
│   ├── keys.json                    # [DEPRECATED - Not used]
│   └── admin_api_keys.json         # ⭐ Admin's shared API pool (NEW)
│
└── 🔗 UTILITIES
    ├── ref.php                      # Short link handler (/ref/CODE)
    └── .htaccess                    # URL rewrite rules (Apache)
```

### File Sizes & Purposes

| File | Lines | Purpose | Critical? |
|------|-------|---------|-----------|
| `scanner.php` | 3,153 | Main search tool | ⭐⭐⭐ |
| `api_pool.php` | 241 | API key management | ⭐⭐⭐ |
| `pricing_data.php` | 133 | Centralized pricing | ⭐⭐⭐ |
| `admin_api_keys.php` | 264 | Admin API interface | ⭐⭐ |
| `functions.php` | ~800 | Helper functions | ⭐⭐ |
| `index.php` | 444 | Homepage | ⭐ |
| `pricing.php` | 563 | Pricing page | ⭐ |
| `checkout.php` | 365 | Payment flow | ⭐⭐ |
| `affiliate.php` | 497 | Affiliate system | ⭐⭐ |

---

## 🔄 LUỒNG HOẠT ĐỘNG

### 1️⃣ User Registration → Payment Flow

```
[Step 1] Visitor arrives
         ↓
    index.php (Homepage)
         │
         ├─→ Sees Hero: "Tìm Ngách YouTube Đỉnh Cao"
         ├─→ Sees Features: Scanner, Analytics, AI Deep Dive
         ├─→ Sees Pricing: Trial (39K) → Premium (1.5M)
         └─→ Clicks "Đăng Ký Ngay"

[Step 2] Registration
         ↓
    login.php?mode=register
         │
         ├─→ Fills: username, email, password
         ├─→ Optional: Referral code (?ref=ABC123)
         └─→ Submits form

[Step 3] Account Created (FREE tier by default)
         ↓
    users.json
         │
         {
            "username": "newuser",
            "email": "user@example.com",
            "password": "$2y$10$...",
            "tier": "free",              ← DEFAULT
            "tier_expiry": null,
            "created_at": "2026-02-13 10:00:00",
            "searches_today": 0,
            "api_keys": {},
            "affiliate_code": "GEN123",
            "affiliate_earnings": 0,
            "referred_by": null
         }

[Step 4] User Logs In → Redirected to Scanner
         ↓
    scanner.php
         │
         ├─→ Sees full interface (looks professional!)
         ├─→ Enters keyword: "cách làm bánh"
         ├─→ Clicks "Tìm Kiếm"
         └─→ 🔒 BLOCKED!
                 │
                 └─→ Modal appears:
                     ┌─────────────────────────────┐
                     │ 🔒 Tính Năng Bị Khóa       │
                     │                             │
                     │ Free tier không được tìm    │
                     │                             │
                     │ Gói Dùng Thử: 39.000đ/3 ngày│
                     │ ✅ 20 lượt/ngày            │
                     │ ✅ Xem đầy đủ kết quả      │
                     │                             │
                     │ [Nâng Cấp Ngay]            │
                     └─────────────────────────────┘

[Step 5] User Decides to Upgrade
         ↓
    Clicks "Nâng Cấp Ngay"
         │
         └─→ Redirects to checkout.php?plan=trial

[Step 6] Payment Page
         ↓
    checkout.php
         │
         ├─→ Shows plan details:
         │   • Gói: Dùng Thử
         │   • Giá: 39.000đ
         │   • Thời hạn: 3 ngày
         │   • Tính năng: 20 lượt/ngày
         │
         ├─→ Shows QR Code:
         │   • MB Bank: 863796789
         │   • Số tiền: 39.000đ
         │   • Nội dung: username trial
         │
         ├─→ User transfers money via banking app
         └─→ Clicks "Tôi Đã Chuyển Khoản"

[Step 7] Order Created (Pending)
         ↓
    orders.json
         │
         {
            "ORD1707825600": {
                "order_id": "ORD1707825600",
                "username": "newuser",
                "plan": "trial",
                "plan_label": "Dùng Thử",
                "amount": 39000,
                "duration_days": 3,
                "status": "pending",
                "created_at": "2026-02-13 10:30:00"
            }
         }

[Step 8] Admin Approves (or Auto-activation after 1-5 min)
         ↓
    admin.php
         │
         ├─→ Admin sees pending order
         ├─→ Clicks "Approve"
         └─→ System executes:
                 │
                 ├─→ Update users.json:
                 │      "tier": "free" → "trial"
                 │      "tier_expiry": "2026-02-16 23:59:59"
                 │
                 ├─→ Update orders.json:
                 │      "status": "approved"
                 │      "approved_at": "2026-02-13 10:35:00"
                 │
                 └─→ Process affiliate commission (if applicable):
                        affiliate_earnings += 39000 × 0.20 = 7,800đ

[Step 9] User Can Now Search! ✅
         ↓
    scanner.php
         │
         ├─→ User refreshes page
         ├─→ USER_TIER = 'trial' (from session)
         ├─→ IS_FREE_LOCKED = false
         ├─→ getActiveKey() returns API key
         ├─→ Search executes successfully!
         └─→ Results displayed ✅

[Step 10] After 3 Days - Trial Expires
         ↓
    Automatic expiry check
         │
         ├─→ System checks tier_expiry on login
         ├─→ If expired: tier = "trial" → "free"
         └─→ User blocked again → Must upgrade to Basic/Premium
```

---

### 2️⃣ Tier-Based API Key Allocation (Hybrid Model)

```
┌─────────────────────────────────────────────────────────────────┐
│              API KEY ALLOCATION STRATEGY (HYBRID)               │
└─────────────────────────────────────────────────────────────────┘

[User Searches] → scanner.php
                      ↓
              User clicks "Tìm Kiếm"
                      ↓
         JavaScript: getActiveKey() called
                      ↓
         ┌────────────┴────────────┐
         │   Check USER_TIER       │
         └────────────┬────────────┘
                      │
      ┌───────────────┼───────────────┬────────────────┐
      │               │               │                │
      ▼               ▼               ▼                ▼
   [FREE]         [TRIAL]        [BASIC]          [PREMIUM]
      │               │               │                │
      │               │               │                │
  ❌ BLOCKED    ✅ Admin Pool   ✅ User's Own    ✅ OpenRouter
      │               │               │                │
      │               │               │                │
      ▼               ▼               ▼                ▼
  return null   YouTube API     YouTube API      OpenRouter API
                (Shared)        (Self-service)   (Unlimited)
      │               │               │                │
      │               ▼               ▼                │
      │       api_pool.php      localStorage          │
      │               │               │                │
      │       getSharedYouTubeKey()  │                │
      │               │               │                │
      │       Round-robin selection  │                │
      │       (Key 1 → Key 2 → Key 3)│                │
      │               │               │                │
      │               └───────────────┴────────────────┘
      │                               │
      └───────────────────────────────┼─────────────────┐
                                      │                 │
                                      ▼                 │
                            YouTube Data API v3         │
                                      │                 │
                                      ▼                 │
                            Fetch channels/videos       │
                                      │                 │
                                      ▼                 │
                            Return JSON data            │
                                      │                 │
                                      ▼                 │
                            Display in UI ✅           │
                                                        │
                            Error: Quota exceeded       │
                                      ↓                 │
                            Show error modal            │
                            "Upgrade to Premium" ──────┘
```

#### API Key Priority Logic

```javascript
// In scanner.php - getActiveKey() function

function getActiveKey() {
    // ─────────────────────────────────────
    // Priority 1: Tier-based key from PHP
    // ─────────────────────────────────────
    if (TIER_API_KEY && TIER_API_KEY.length > 10) {
        // ✅ Use key provided by api_pool.php
        // This is:
        //   - Admin's shared key (Trial)
        //   - Admin's OpenRouter key (Premium)
        return TIER_API_KEY;
    }
    
    // ─────────────────────────────────────
    // Priority 2: User's own keys (localStorage)
    // ─────────────────────────────────────
    if (apiKeys.length > 0) {
        // ✅ User added their own key in Settings
        // This is for Basic tier (required)
        const key = apiKeys[currentKeyIndex];
        currentKeyIndex = (currentKeyIndex + 1) % apiKeys.length;
        return key;
    }
    
    // ─────────────────────────────────────
    // Priority 3: No key available - Show error
    // ─────────────────────────────────────
    if (USER_TIER === TIER_BASIC) {
        showToast('⚠️ Basic tier: Please add your YouTube API key in Settings', 'warning');
    } else if (USER_TIER === TIER_TRIAL) {
        showToast('⚠️ Admin API pool exhausted. Try again later or add your own key.', 'warning');
    }
    
    return null;
}
```

#### API Cost Analysis

| Tier | API Source | Managed By | Cost to Admin | Quota |
|------|------------|------------|---------------|-------|
| **FREE** | None | - | $0 | 0 searches |
| **TRIAL** | Admin's shared pool | `api_pool.php` | $0 (free Google API) | 20/day (shared) |
| **BASIC** | User's own | User (localStorage) | $0 (user pays) | 100/day (user's quota) |
| **PREMIUM** | Admin's OpenRouter | `api_pool.php` | ~$5/day (paid) | Unlimited |

**Example Cost Calculation (100 Premium users):**
```
100 users × 50 searches/day = 5,000 searches/day
5,000 searches × 10,000 tokens/search = 50M tokens/day
50M tokens × $0.10/1M = $5/day = $150/month

Revenue: 100 users × (1.5M VND / 12 months) = 12.5M VND/month (~$500)
Profit: $500 - $150 = $350/month ✅
```

---

### 3️⃣ Affiliate System Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                      AFFILIATE SYSTEM FLOW                      │
└─────────────────────────────────────────────────────────────────┘

[Step 1] User Registers as Affiliate
         ↓
    affiliate.php
         │
         ├─→ Auto-generate unique code:
         │   • Algorithm: substr(md5(username + time()), 0, 8)
         │   • Example: "04E69C23"
         │
         ├─→ Create short link:
         │   • Long: domain.com/login.php?mode=register&ref=04E69C23
         │   • Short: domain.com/ref/04E69C23 ✅
         │
         └─→ Store in users.json:
                {
                    "affiliate_code": "04E69C23",
                    "affiliate_earnings": 0,
                    "affiliate_referrals": []
                }

[Step 2] Affiliate Shares Link
         ↓
    https://timnggachchuan.com/ref/04E69C23
         │
         └─→ .htaccess intercepts:
                RewriteRule ^ref/([A-Z0-9]{6,12})$ ref.php?code=$1 [L,NC]

[Step 3] Link Clicked by New User
         ↓
    ref.php validates code
         │
         ├─→ Check: Is code valid? (exists in users.json)
         │   │
         │   ├─→ YES: redirect to login.php?mode=register&ref=04E69C23
         │   └─→ NO: redirect to index.php
         │
         └─→ Set cookie: affiliate_ref = 04E69C23 (30 days)

[Step 4] New User Registers
         ↓
    login.php?mode=register&ref=04E69C23
         │
         ├─→ Check: Is ?ref parameter present?
         │   │
         │   └─→ YES: Store referrer in users.json:
         │           {
         │               "username": "newuser",
         │               "referred_by": "affiliate_username",
         │               "referred_at": "2026-02-13 10:00:00"
         │           }
         │
         └─→ Affiliate tracking activated ✅

[Step 5] New User Makes Purchase
         ↓
    checkout.php
         │
         ├─→ Get user data: referred_by = "affiliate_username"
         │
         └─→ Create order with tracking:
                {
                    "order_id": "ORD1707825600",
                    "username": "newuser",
                    "amount": 39000,
                    "referrer": "affiliate_username" ✅
                }

[Step 6] Admin Approves Order
         ↓
    admin.php
         │
         ├─→ Approve order → status = "approved"
         │
         ├─→ Check: Does order have referrer?
         │   │
         │   └─→ YES: Calculate commission:
         │           commission = amount × 0.20
         │           commission = 39000 × 0.20 = 7,800đ
         │
         └─→ Credit affiliate in users.json:
                {
                    "affiliate_username": {
                        "affiliate_earnings": 0 + 7800 = 7800,
                        "affiliate_referrals": ["newuser"]
                    }
                }

[Step 7] Affiliate Views Dashboard
         ↓
    affiliate.php
         │
         ├─→ Display stats:
         │   • Total earnings: 7,800đ
         │   • Referrals: 1 user
         │   • Conversion rate: 100%
         │
         └─→ Show payment info (for withdrawals):
                • Bank name: MB Bank
                • Account number: 0123456789
                • Account name: Nguyen Van A
```

#### Affiliate Commission Rates

| Plan | Price | Commission (20%) | Per Day (for affiliate) |
|------|-------|------------------|------------------------|
| Trial (3d) | 39,000đ | 7,800đ | 2,600đ |
| Basic (1m) | 199,000đ | 39,800đ | 1,327đ |
| Standard (3m) | 537,000đ | 107,400đ | 1,193đ |
| Premium (12m) | 1,554,000đ | 310,800đ | 850đ |

---

## 🔐 PHÂN QUYỀN THEO TIER

### Tier Comparison Matrix

```
┌───────────────────────────────────────────────────────────────────┐
│                    TIER FEATURE COMPARISON                        │
└───────────────────────────────────────────────────────────────────┘

Feature                  │  FREE  │ TRIAL │ BASIC │ PREMIUM
─────────────────────────┼────────┼───────┼───────┼─────────
🔍 Can Search            │   ❌   │  ✅   │  ✅   │   ✅
📊 Searches/Day          │    0   │  20   │  100  │   ♾️
👁️ View Results          │   ❌   │  ✅   │  ✅   │   ✅
📥 Export CSV            │   ❌   │  ❌   │  ✅   │   ✅
🔬 Outlier Detection     │   ❌   │  ✅   │  ✅   │   ✅
🎨 Thumbnail Analysis    │   ❌   │  ✅   │  ✅   │   ✅
🧠 AI Deep Dive (Gemini) │   ❌   │  ❌   │  ❌   │   ✅
🎯 Niche Suggestions     │   ❌   │  ✅   │  ✅   │   ✅
⚙️ API Key Management    │   ❌   │  ✅   │  ✅   │   ✅
🔑 API Key Source        │   -    │ Admin │ User  │  Admin
💰 Affiliate Program     │   ✅   │  ✅   │  ✅   │   ✅
🎁 Referral Bonus        │   ✅   │  ✅   │  ✅   │   ✅
─────────────────────────┴────────┴───────┴───────┴─────────
💵 Price                 │   0đ   │ 39K   │ 199K  │  1.5M
⏰ Duration              │   -    │ 3days │  1mo  │ 1year
📅 Per Day Cost          │   -    │ 13K   │ 6.6K  │  4.2K
💳 Payment Method        │   -    │  Bank │  Bank │  Bank
```

### Tier Configuration (includes/config.php)

```php
$TIER_FEATURES = [
    TIER_FREE => [
        'scanner_access' => false,      // ❌ BLOCKED
        'max_searches_per_day' => 0,
        'export_csv' => false,
        'api_priority' => 'blocked',
        'support_level' => 'none',
        'can_view_settings' => false,
        'can_view_details' => false,
        'show_teaser' => true          // Show 1 blurred result (tease)
    ],
    
    TIER_TRIAL => [
        'scanner_access' => true,       // ✅ ALLOWED
        'max_searches_per_day' => 20,
        'export_csv' => false,
        'api_priority' => 'normal',
        'support_level' => 'email',
        'can_view_settings' => true,
        'can_view_details' => true,
        'duration_days' => 3            // Auto-expire after 3 days
    ],
    
    TIER_BASIC => [
        'scanner_access' => true,
        'max_searches_per_day' => 100,
        'export_csv' => true,           // ✅ Can export CSV
        'api_priority' => 'normal',
        'support_level' => 'email',
        'can_view_settings' => true,
        'can_view_details' => true
    ],
    
    TIER_VIP => [
        'scanner_access' => true,
        'max_searches_per_day' => -1,   // ♾️ UNLIMITED
        'export_csv' => true,
        'api_priority' => 'high',
        'support_level' => 'priority',
        'can_view_settings' => true,
        'can_view_details' => true,
        'ai_deep_dive' => true,         // ✅ Gemini AI analysis
        'affiliate_enabled' => true,
        'commission_rate' => 0.20       // 20% commission
    ]
];
```

---

## 💾 DATABASE SCHEMA

### users.json Structure

```json
{
    "username123": {
        // ─────────────────────────────
        // BASIC INFO
        // ─────────────────────────────
        "email": "user@example.com",
        "password": "$2y$10$...",           // bcrypt hash
        "created_at": "2026-02-13 10:00:00",
        "last_login": "2026-02-13 15:30:00",
        
        // ─────────────────────────────
        // TIER & ACCESS
        // ─────────────────────────────
        "tier": "trial",                    // free, trial, basic, vip
        "tier_expiry": "2026-02-16 23:59:59",
        "tier_history": [
            {
                "tier": "free",
                "start": "2026-02-13 10:00:00",
                "end": "2026-02-13 10:30:00"
            },
            {
                "tier": "trial",
                "start": "2026-02-13 10:30:00",
                "end": "2026-02-16 23:59:59"
            }
        ],
        
        // ─────────────────────────────
        // USAGE TRACKING
        // ─────────────────────────────
        "searches_today": 5,
        "searches_reset_at": "2026-02-14 00:00:00",
        "total_searches": 127,
        "last_search_at": "2026-02-13 15:30:00",
        
        // ─────────────────────────────
        // API KEYS (User's Own)
        // ─────────────────────────────
        "api_keys": {
            "youtube": "AIzaSyA...",
            "gemini": "AIzaSyB..."
        },
        
        // ─────────────────────────────
        // AFFILIATE DATA
        // ─────────────────────────────
        "affiliate_code": "ABC123XYZ",
        "affiliate_earnings": 50000,        // Total commission earned
        "affiliate_referrals": [
            "user456",
            "user789"
        ],
        "referred_by": "affiliate_username",
        "referred_at": "2026-02-13 09:00:00",
        
        // ─────────────────────────────
        // PAYMENT INFO (For payouts)
        // ─────────────────────────────
        "bank_name": "MB Bank",
        "bank_account": "0123456789",
        "bank_owner": "Nguyen Van A"
    }
}
```

### orders.json Structure

```json
{
    "ORD1707825600": {
        // ─────────────────────────────
        // ORDER INFO
        // ─────────────────────────────
        "order_id": "ORD1707825600",
        "username": "username123",
        
        // ─────────────────────────────
        // PLAN DETAILS
        // ─────────────────────────────
        "plan": "trial",                    // trial, 1m, 3m, 12m
        "plan_label": "Dùng Thử",
        "amount": 39000,
        "duration_days": 3,
        
        // ─────────────────────────────
        // CUSTOMER INFO
        // ─────────────────────────────
        "customer_name": "Nguyen Van A",
        "customer_phone": "0901234567",
        "customer_email": "user@example.com",
        "transfer_note": "username123 trial",
        
        // ─────────────────────────────
        // PAYMENT PROOF
        // ─────────────────────────────
        "payment_proof": "auto",            // "auto" for auto-activation
        
        // ─────────────────────────────
        // ORDER STATUS
        // ─────────────────────────────
        "status": "pending",                // pending, approved, rejected
        "created_at": "2026-02-13 10:00:00",
        "approved_at": "2026-02-13 10:35:00",
        "approved_by": "admin",
        
        // ─────────────────────────────
        // AFFILIATE TRACKING
        // ─────────────────────────────
        "referrer": "affiliate_username",
        "commission_paid": true,
        "commission_amount": 7800
    }
}
```

### admin_api_keys.json Structure (⭐ NEW)

```json
{
    // ─────────────────────────────────────
    // SHARED YOUTUBE KEYS (For Trial Users)
    // ─────────────────────────────────────
    "youtube_shared": [
        {
            "key": "AIzaSyA...",
            "name": "Shared Key 1",
            "status": "active",             // active, inactive, exhausted
            "daily_limit": 1500,            // Max searches per day
            "usage_today": 450,             // Current usage
            "reset_at": "2026-02-13 00:00:00"
        },
        {
            "key": "AIzaSyB...",
            "name": "Shared Key 2",
            "status": "active",
            "daily_limit": 1500,
            "usage_today": 320,
            "reset_at": "2026-02-13 00:00:00"
        },
        {
            "key": "AIzaSyC...",
            "name": "Shared Key 3",
            "status": "active",
            "daily_limit": 1500,
            "usage_today": 180,
            "reset_at": "2026-02-13 00:00:00"
        }
    ],
    
    // ─────────────────────────────────────
    // OPENROUTER (For Premium Users)
    // ─────────────────────────────────────
    "openrouter": {
        "key": "sk-or-v1-...",
        "model": "google/gemini-2.0-flash-exp:free",
        "status": "active"                  // active, inactive
    }
}
```

---

## 🔧 MODULE QUAN TRỌNG

### 1. includes/api_pool.php ⭐⭐⭐

**Chức năng:** Quản lý tier-based API key allocation

**Core Method:**
```php
public function getAPIKey($userTier, $userAPIKey = null)
```

**Logic Flow:**
```
Input: $userTier, $userAPIKey
    ↓
Switch ($userTier)
    ↓
    ├─→ TIER_FREE:
    │   └─→ Return: ['key' => null, 'error' => 'Blocked']
    │
    ├─→ TIER_TRIAL:
    │   ├─→ If $userAPIKey: return user's key
    │   └─→ Else: getSharedYouTubeKey() (round-robin)
    │
    ├─→ TIER_BASIC:
    │   ├─→ If $userAPIKey: return user's key
    │   └─→ Else: return error "Add your key"
    │
    └─→ TIER_VIP:
        ├─→ Return: getOpenRouterKey() (unlimited)
        └─→ Fallback: $userAPIKey if OpenRouter not configured
```

**Key Features:**
- Round-robin load balancing for shared keys
- Daily usage tracking & auto-reset
- Quota monitoring per key
- Fallback handling

**Files it affects:**
- `scanner.php` (calls getAPIKey())
- `admin_api_keys.php` (manages keys)

---

### 2. includes/pricing_data.php ⭐⭐⭐

**Chức năng:** Centralized pricing (single source of truth)

**Structure:**
```php
$GLOBAL_PRICING = [
    'trial' => [
        'original_price' => 99000,      // Anchor price (high)
        'sale_price' => 39000,          // Actual price
        'discount_percent' => 61,
        'per_day' => 13000,
        'features' => [...],
        'badge' => '🚀 KHỞI ĐẦU'
    ],
    '1m' => [...],
    '3m' => [...],
    '12m' => [...]
];
```

**Why Important:**
- ✅ Change price in 1 file → All pages update
- ✅ No inconsistency risk
- ✅ Easy A/B testing
- ✅ Strike-through pricing strategy

**Used By:**
- `pricing.php` - Pricing page
- `index.php` - Homepage pricing section
- `checkout.php` - Payment page

**Example Change:**
```php
// Want to change Trial price from 39K → 29K?
// OLD WAY: Edit 3 files (pricing.php, index.php, checkout.php)
// NEW WAY: Edit 1 line in pricing_data.php

$GLOBAL_PRICING['trial']['sale_price'] = 29000; // Done!
```

---

### 3. scanner.php - Main Tool ⭐⭐⭐

**Purpose:** Keyword search + analytics + AI features

**Components:**

#### A. Search Engine
```javascript
async function analyzeKeywords() {
    // 1. Check tier access
    if (IS_FREE_LOCKED) {
        showUpgradeModal();
        return;
    }
    
    // 2. Get API key
    const apiKey = getActiveKey();
    if (!apiKey) {
        showToast('Please add API key');
        return;
    }
    
    // 3. Fetch YouTube data
    const channels = await fetchYouTubeData(keyword, apiKey);
    
    // 4. Analyze channels
    const analyzed = channels.map(ch => {
        return {
            ...ch,
            viralScore: calculateViralScore(ch),
            outliers: detectOutliers(ch.videos),
            thumbnailColors: analyzeColors(ch.thumbnail)
        };
    });
    
    // 5. Display results
    displayResults(analyzed);
}
```

#### B. Outlier Detection (Viral Video Finder)
```javascript
function detectOutliers(videos) {
    // Statistical method: Z-score > 3
    const views = videos.map(v => v.views);
    const mean = views.reduce((a,b) => a+b) / views.length;
    const stdDev = Math.sqrt(
        views.reduce((sq, n) => sq + Math.pow(n - mean, 2), 0) / views.length
    );
    
    return videos.filter(v => {
        const zScore = (v.views - mean) / stdDev;
        return zScore > 3; // More than 3σ = Outlier (viral)
    });
}
```

#### C. Thumbnail Analysis
```javascript
function analyzeThumbnail(imageUrl) {
    // Use Canvas API to extract dominant colors
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    
    // Load image & extract pixels
    const img = new Image();
    img.crossOrigin = 'anonymous';
    img.src = imageUrl;
    
    img.onload = () => {
        canvas.width = img.width;
        canvas.height = img.height;
        ctx.drawImage(img, 0, 0);
        
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const colors = extractDominantColors(imageData);
        
        // Analyze color psychology
        const psychology = analyzeColorPsychology(colors);
        displayColorAnalysis(psychology);
    };
}
```

#### D. AI Deep Dive (Premium Only)
```javascript
async function executeDeepDive(channelId) {
    // Only for Premium users
    if (USER_TIER !== 'vip') {
        showUpgradeModal('AI Deep Dive requires Premium tier');
        return;
    }
    
    // Fetch channel data
    const channel = await fetchChannelDetails(channelId);
    
    // Send to Gemini AI for analysis
    const analysis = await fetch('/api/gemini_analysis.php', {
        method: 'POST',
        body: JSON.stringify({
            channel: channel,
            prompt: 'Analyze this YouTube channel...'
        })
    });
    
    const result = await analysis.json();
    displayAIAnalysis(result);
}
```

---

### 4. ref.php + .htaccess - Short Links ⭐⭐

**Purpose:** Clean affiliate URLs

**Flow:**
```
User visits: https://domain.com/ref/ABC123
    ↓
.htaccess intercepts:
    RewriteRule ^ref/([A-Z0-9]{6,12})$ ref.php?code=$1 [L,NC]
    ↓
ref.php validates:
    - Is code format valid? (6-12 alphanumeric)
    - Does code exist in users.json?
    ↓
If valid:
    redirect to: /login.php?mode=register&ref=ABC123
    ↓
If invalid:
    redirect to: /index.php
```

**Benefits:**
- ✅ Professional appearance
- ✅ Easy to remember
- ✅ Social media friendly
- ✅ Doesn't look like spam

---

## 🚀 HƯỚNG DẪN NÂNG CẤP

### Khi Muốn Sửa/Nâng Cấp, Chỉ Cần Nói:

#### 📋 **"Sửa giá"**
→ Tôi biết: Edit `includes/pricing_data.php`
```php
$GLOBAL_PRICING['trial']['sale_price'] = 29000; // Done!
```
→ Result: Tất cả pages (pricing.php, index.php, checkout.php) auto-update

---

#### 🔑 **"Thêm YouTube API key"**
→ Tôi biết: Go to `admin_api_keys.php`
→ Add key in form → Save
→ System auto-adds to `admin_api_keys.json`

---

#### 🎯 **"Thêm tier mới"**
→ Tôi biết: Edit 3 files:
1. `includes/config.php` - Add to $TIER_FEATURES
2. `includes/pricing_data.php` - Add to $GLOBAL_PRICING
3. `includes/api_pool.php` - Add case in getAPIKey()

---

#### ⚡ **"Thay đổi search quota"**
→ Tôi biết: Edit `includes/config.php`
```php
TIER_TRIAL => [
    'max_searches_per_day' => 50, // Changed from 20 → 50
]
```

---

#### 🎨 **"Thêm tính năng mới vào Scanner"**
→ Tôi biết: Edit `scanner.php`
→ Add JavaScript function
→ Add UI button/section
→ Hook into analyzeKeywords() or create new function

---

#### 💳 **"Tích hợp payment gateway (Stripe/PayPal)"**
→ Tôi biết: Edit `checkout.php`
→ Add payment gateway SDK
→ Replace bank transfer with card payment
→ Keep auto-activation logic

---

#### 🤖 **"Thay đổi AI model"**
→ Tôi biết: 
1. Edit `admin_api_keys.php` - OpenRouter model selector
2. Edit `api_pool.php` - getOpenRouterKey() method
3. Test with Premium user

---

#### 📊 **"Thêm report/analytics"**
→ Tôi biết: Edit `admin.php`
→ Add new section
→ Query orders.json + users.json
→ Display charts/tables

---

### Quick Reference: File → Purpose

| Want to change... | Edit this file |
|------------------|----------------|
| Pricing (any tier) | `includes/pricing_data.php` |
| Tier features/limits | `includes/config.php` |
| API allocation logic | `includes/api_pool.php` |
| Scanner features | `scanner.php` |
| Payment flow | `checkout.php` |
| Affiliate system | `affiliate.php` |
| Admin dashboard | `admin.php` |
| Homepage content | `index.php` |
| Pricing page design | `pricing.php` |
| Short links | `ref.php` + `.htaccess` |

---

## 🎯 DEPLOYMENT CHECKLIST

### Phase 1: Server Setup
- [ ] Upload all files to server
- [ ] Create `data/` folder (permissions: 755)
- [ ] Create empty JSON files:
    - [ ] `data/users.json` (content: `{}`)
    - [ ] `data/orders.json` (content: `{}`)
    - [ ] `data/admin_api_keys.json` (content: `{"youtube_shared":[],"openrouter":{"key":"","status":"inactive"}}`)
- [ ] Test write permissions: `chmod 755 data/`

### Phase 2: Configuration
- [ ] Edit `includes/config.php`:
    - [ ] Set correct paths
    - [ ] Update admin credentials
    - [ ] Verify tier settings
- [ ] Edit `checkout.php`:
    - [ ] Update MB Bank info
    - [ ] Update company name
- [ ] Test .htaccess: Visit `/ref/TEST` (should redirect)

### Phase 3: API Keys
- [ ] Go to `admin_api_keys.php`
- [ ] Add 3-5 YouTube API keys (for Trial users)
- [ ] Add OpenRouter key (for Premium users)
- [ ] Test key rotation: Search with Trial account

### Phase 4: Testing
- [ ] Create test users:
    - [ ] FREE user → Test blocked
    - [ ] TRIAL user → Test 20 searches/day
    - [ ] BASIC user → Test CSV export
    - [ ] PREMIUM user → Test unlimited + AI Deep Dive
- [ ] Test payment flow:
    - [ ] Create order → Check orders.json
    - [ ] Approve order → Check tier upgrade
    - [ ] Test affiliate commission
- [ ] Test affiliate system:
    - [ ] Generate short link
    - [ ] Click link → Register
    - [ ] Buy plan → Check commission

---

## 📞 SUPPORT & MAINTENANCE

### Daily Tasks (Automated via Cron)
```bash
# Reset daily search quotas (00:00 AM)
0 0 * * * php /path/to/reset_quotas.php

# Reset API key usage counters (00:00 AM)
0 0 * * * php /path/to/reset_api_usage.php

# Check tier expiries (every hour)
0 * * * * php /path/to/check_expiries.php
```

### Weekly Tasks (Manual)
- [ ] Review pending orders in `admin.php`
- [ ] Process affiliate payouts
- [ ] Check API key usage stats
- [ ] Monitor system errors (if logging enabled)

### Monthly Tasks
- [ ] Analyze revenue by tier
- [ ] Review pricing strategy
- [ ] Check competitor pricing
- [ ] Plan feature updates

---

## 🔒 SECURITY NOTES

### Password Hashing
```php
// All passwords are hashed with bcrypt
password_hash($password, PASSWORD_BCRYPT);
```

### Session Security
```php
// Session config in includes/session.php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // HTTPS only
ini_set('session.use_strict_mode', 1);
```

### API Key Storage
- **User keys:** Obfuscated in localStorage (client-side)
- **Admin keys:** Stored in `admin_api_keys.json` (server-side, 755 permissions)
- **Never exposed:** Keys never sent to frontend unless needed

### SQL Injection
- ✅ Not applicable (JSON database, no SQL)

### XSS Prevention
```php
// All user input sanitized
htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
```

---

## 📈 METRICS & KPIs

### Conversion Funnel
```
100 Visitors (index.php)
    ↓
 50 Register (FREE)
    ↓
 10 Upgrade to TRIAL (39K) → 20% conversion
    ↓
  3 Upgrade to BASIC (199K) → 30% conversion
    ↓
  1 Upgrade to PREMIUM (1.5M) → 33% conversion
```

### Revenue Projection (100 users)
```
Tier Distribution (estimated):
- 50 FREE (0đ) = 0đ
- 30 TRIAL (39K) = 1.17M VND
- 15 BASIC (199K/mo) = 2.985M VND/month
-  5 PREMIUM (1.5M/year) = 625K VND/month

Total Revenue: ~4.78M VND/month (~$200/month)
```

### Cost Structure
```
Fixed Costs:
- Server hosting: ~$10/month
- Domain: ~$12/year = $1/month
- SSL certificate: Free (Let's Encrypt)

Variable Costs:
- OpenRouter API (Premium users): ~$5/day × 30 = $150/month
- YouTube API (Trial users): $0 (free tier)

Total Cost: ~$161/month
Net Profit: $200 - $161 = $39/month (with 100 users)

Break-even: ~80-90 users
Profitable: 100+ users
```

---

## 🎓 LEARNING RESOURCES

### For Understanding Code
- **PHP Basics:** https://www.php.net/manual/en/
- **Tailwind CSS:** https://tailwindcss.com/docs
- **YouTube Data API:** https://developers.google.com/youtube/v3
- **Google Gemini API:** https://ai.google.dev/docs

### For Troubleshooting
- **PHP Errors:** Check server error logs
- **JavaScript Errors:** Check browser console (F12)
- **JSON Syntax:** Use jsonlint.com
- **.htaccess Issues:** Test with https://htaccess.madewithlove.be/

---

## 📝 VERSION HISTORY

### v2.0 (2026-02-13) - Current
- ✅ Hybrid API model (tier-based allocation)
- ✅ Centralized pricing (pricing_data.php)
- ✅ Short affiliate links (/ref/CODE)
- ✅ Strike-through pricing strategy
- ✅ Admin API key management
- ✅ 4 tiers (FREE, TRIAL, BASIC, PREMIUM)

### v1.0 (Previous)
- Basic scanner functionality
- User-provided API keys only
- 3 tiers (FREE, BASIC, VIP)
- Long affiliate links
- No centralized pricing

---

## 🙏 CREDITS

**Developed by:** Qoder AI Assistant  
**Client:** NDGROUP MEDIA VIỆT NAM  
**Company Tax ID:** 0319057106  
**Address:** 118 Đường B, Phường Hiệp Bình, Q. Thủ Đức, TP.HCM  
**Legal Representative:** Nguyễn Văn Thạch Duy

---

## 📧 CONTACT

**For Technical Support:**
- Check `admin.php` dashboard first
- Review this documentation
- Contact: [Your support email]

**For Feature Requests:**
- Document desired feature clearly
- Provide use case examples
- Estimate business value

---

**🎯 END OF SYSTEM ARCHITECTURE DOCUMENT**

*Last Updated: 2026-02-13*  
*Document Version: 1.0*  
*Total Pages: 35*
