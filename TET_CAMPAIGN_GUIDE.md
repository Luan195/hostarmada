# 🧧 LÌ XÌ TẾT 2026 - HƯỚNG DẪN TRIỂN KHAI

> **Chiến dịch:** Lì Xì Tết Ất Tỵ 2026  
> **Thời gian:** 17/02/2026 (Hôm nay) → 28/02/2026 (11 ngày)  
> **Giá shock:** 299K/6 tháng | 399K/12 tháng
> 
> ⚠️ **CẬP NHẬT MỚI:**
> - ✅ Đổi tên: `tet_pricing.php` → `lixitet.php` (sang hơn)
> - ✅ Fix lỗi: Array offset on null (lines 170-177)
> - ✅ Campaign ACTIVE ngay hôm nay!

---

## 📋 I. TÓM TẮT CẤU TRÚC

### Files Mới Tạo

```
timngachchuan/
├── config/
│   ├── lixitet.php            [254 lines] 🧧 Lì Xì Tết Pricing (SECURE)
│   └── .htaccess              [13 lines] Deny all access
├── tet-promo.php              [1,041 lines] Landing page Tết
└── tet-checkout.php           [359 lines] Checkout page riêng
```

**🔒 BẢO MẬT:** File `lixitet.php` đã di chuyển vào `config/` (ngoài public_html) và được bảo vệ bởi `.htaccess`

### Files Đã Cập Nhật

```
admin/orders.php               +15 lines (Nhận diện orders Tết)
.htaccess                      +4 lines (Short URL: /tet)
```

### Files KHÔNG Đụng Chạm

```
✅ pricing.php                 GIỮ NGUYÊN giá thường
✅ checkout.php                GIỮ NGUYÊN flow thường
✅ includes/pricing_data.php   GIỮ NGUYÊN
✅ scanner.php                 GIỮ NGUYÊN
```

---

## 🎯 II. CHIẾN LƯỢC HOẠT ĐỘNG

### A. Tách Biệt Hoàn Toàn

**Landing Page Tết (tet-promo.php):**
- URL: `yoursite.com/tet-promo.php` hoặc `yoursite.com/tet`
- Chỉ hiển thị 2 gói: tet_6m (299K) và tet_12m (399K)
- Countdown timer: Còn X ngày
- Theme: Đỏ vàng, chúc Tết, tâm lý urgency

**Trang Giá Thường (pricing.php):**
- URL: `yoursite.com/pricing.php`
- Hiển thị giá thường (trial 39K, 1m 179K, 12m 1.29M)
- KHÔNG có mention về Tết

### B. Luồng User

```
MARKETING → TẾT LANDING → TẾT CHECKOUT → ADMIN APPROVE
    ↓
Facebook Ad: "Giảm 61% Tết"
    ↓
Click → tet-promo.php
    ↓
Chọn gói → tet-checkout.php?plan=tet_12m
    ↓
Chuyển tiền → Order created (campaign: "tet2026")
    ↓
Admin approve → Kích hoạt VIP 365 ngày
```

---

## 💰 III. BẢNG GIÁ TẾT

| Gói | Giá Thường | Giá Tết | Tiết Kiệm | % OFF |
|-----|------------|---------|-----------|-------|
| **6 tháng** | 805.500đ | **299.000đ** | 506.500đ | **63%** |
| **12 tháng** | 1.290.000đ | **399.000đ** | 891.000đ | **69%** |

**So sánh:**
- Giá Tết 12 tháng (399K) < Giá thường 3 tháng (483K) ✅
- Per day: 1.093đ/ngày (rẻ hơn 1 ly cà phê)

---

## 🔧 IV. HƯỚNG DẪN TRIỂN KHAI

### Bước 1: Cập Nhật Ngày Campaign

**File:** `config/lixitet.php` (đã di chuyển ra ngoài public_html - SECURE)

```php
// Line 12-14: Campaign đã ACTIVE (17/02 - 28/02/2026)
define('TET_CAMPAIGN_START', '2026-02-17 00:00:00'); // Active từ hôm nay
define('TET_CAMPAIGN_END', '2026-02-28 23:59:59');   // Kéo dài 11 ngày
```

### Bước 2: Tạo Marketing Links

**Share các link sau:**

```
Landing page:
https://yoursite.com/tet-promo.php
hoặc
https://yoursite.com/tet

Direct checkout (for logged users):
https://yoursite.com/tet-checkout.php?plan=tet_12m
https://yoursite.com/tet-checkout.php?plan=tet_6m
```

### Bước 3: Admin Approve Orders

**Vào:** `admin/orders.php`

**Orders Tết có đặc điểm:**
- Badge đỏ: 🎊 TẾT
- Plan: "🎊 Lì Xì Tết 6 Tháng" hoặc "👑 Lì Xì Tết 12 Tháng"
- Amount: 299.000đ hoặc 399.000đ

**Approve như bình thường:**
- Click "Approve"
- System tự động:
  - Kích hoạt VIP tier
  - Duration: 180 days (6m) hoặc 365 days (12m)
  - Tính affiliate commission (nếu có)

---

## 📊 V. TRACKING & REPORTING

### A. Xem Orders Tết

**Query trong admin panel:**
```php
// Filter orders by campaign
$tetOrders = array_filter($orders, function($order) {
    return isset($order['campaign']) && $order['campaign'] === 'tet2026';
});
```

### B. Thống Kê

```php
// Total revenue from Tet campaign
$tetRevenue = array_sum(array_column($tetOrders, 'amount'));

// Conversion by plan
$tet6mCount = count(array_filter($tetOrders, fn($o) => $o['plan'] === 'tet_6m'));
$tet12mCount = count(array_filter($tetOrders, fn($o) => $o['plan'] === 'tet_12m'));
```

---

## 🎨 VI. THIẾT KẾ & TÂM LÝ

### A. Color Psychology

```
Đỏ (#ff0000):  May mắn, sung túc, Tết truyền thống
Vàng (#ffd700): Vàng kim, giàu sang, lì xì
Cam (#ff6b00):  Năng lượng, nhiệt huyết, hành động
```

### B. Copy Highlights

**Hero:**
- "🧧 LÌ XÌ TẾT 2026"
- "GIẢM ĐẾN 61%"
- "Chúc Quý Khách Năm Mới An Khang Thịnh Vượng"

**Urgency:**
- Countdown: "Còn X ngày để hưởng ưu đãi Tết!"
- "Sau Mùng 8 Tết, giá quay về mức thường (tăng 2-3 lần)"

**Social Proof:**
- "999+ đơn đã mua Tết này"

---

## 🚀 VII. CHECKLIST TRIỂN KHAI

### Pre-Launch (Trước 17/02)

- [x] Kiểm tra dates trong `lixitet.php` ✅
- [x] Fix lỗi array offset on null ✅
- [x] Rename `tet_pricing.php` → `lixitet.php` ✅
- [ ] Test flow: tet-promo → tet-checkout → orders
- [ ] Test admin approve với Tet orders
- [ ] Chuẩn bị QR code MB Bank (VIETQR_IMAGE)
- [ ] Tạo Facebook Ads / Zalo marketing posts

### Launch Day (17/02 - Hôm nay)

- [x] Verify campaign is active: `isTetCampaignActive()` = true ✅
- [ ] Share link: `yoursite.com/tet` trên social media
- [ ] Monitor orders trong admin panel
- [ ] Approve orders nhanh (trong 1-5 phút)

### During Campaign (17-28/02)

- [ ] Check orders daily
- [ ] Track conversion rate
- [ ] Respond to customer support
- [ ] Update ads nếu cần adjust

### Post-Campaign (Sau 28/02)

- [ ] Campaign auto-expired
- [ ] tet-promo.php hiển thị "Đã kết thúc"
- [ ] Export Tet orders report
- [ ] Calculate ROI

---

## 📞 VIII. CUSTOMER SUPPORT

### FAQs

**Q: Sau 28/02 còn mua được giá này không?**  
A: Không. Giá Tết chỉ áp dụng đến hết 28/02. Sau đó quay về giá thường.

**Q: Gói Tết 12 tháng có tính năng gì?**  
A: VIP tier - Unlimited searches, AI Deep Dive, CSV Export, Affiliate 20%.

**Q: Tôi đã mua gói thường, có nâng cấp lên Tết được không?**  
A: Liên hệ support để xem xét gia hạn thêm với giá ưu đãi.

---

## 🔒 IX. BẢO MẬT & CHỐNG ABUSE

### A. Ngăn Abuse

```php
// Check if user already bought Tet plan
if (isset($currentUser['tet2026_purchased'])) {
    die('Bạn đã mua gói Tết rồi!');
}

// Mark as purchased after approve
$users[$username]['tet2026_purchased'] = true;
```

### B. Limit per User

**Option:** Giới hạn 1 gói Tết/user (implement nếu cần)

---

## 📈 X. EXPECTED RESULTS

### A. Conversion Rate Estimate

```
100 visitors → 20 signups → 10 purchases = 10% conversion
Average: 8 gói 12m (399K) + 2 gói 6m (299K) = 3.79M revenue
```

### B. ROI

```
Marketing spend: 500K (Facebook Ads)
Revenue: 3.79M
Profit: 3.79M - 500K = 3.29M
ROI: 658%
```

---

## ✅ XI. KẾT LUẬN

### Ưu Điểm

✅ **Tách biệt hoàn toàn:** Không ảnh hưởng giá thường  
✅ **Tâm lý Tết:** Design đỏ vàng, chúc Tết, urgency  
✅ **Giá shock:** Rẻ hơn gói 3 tháng thường (conviction cao)  
✅ **Track dễ dàng:** Campaign tag, badge trong admin  
✅ **Auto-expire:** Sau 25/02 tự động kết thúc  

### Next Steps

1. **Test toàn bộ flow** (tet-promo → checkout → admin approve)
2. **Chuẩn bị marketing materials** (banner, video, copy)
3. **Launch ngày 18/02** (Mùng 1 Tết)
4. **Monitor & optimize** trong 7 ngày

---

**🎊 CHÚC CHIẾN DỊCH TẾT THÀNH CÔNG! 🎊**

---

**Liên hệ:**  
- Support: 0944851719
- Email: htpgroupmedia.com  

**Files Reference:**
- Landing: `tet-promo.php` [1,041 lines]
- Checkout: `tet-checkout.php` [359 lines]
- Pricing: `config/lixitet.php` [254 lines] 🧧🔒
- Security: `config/.htaccess` [13 lines] Deny all
- Admin: `admin/orders.php` [+15 lines updated]
