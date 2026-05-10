# 🔒 CONFIG DIRECTORY - SECURE CONFIGURATION FILES

## 📁 MỤC ĐÍCH

Thư mục này chứa **các file cấu hình nhạy cảm** không được phép truy cập trực tiếp qua web browser.

## 🛡️ BẢO MẬT

### `.htaccess` Protection
```apache
# Deny all direct access
<Files "*">
    Order Allow,Deny
    Deny from all
</Files>
```

**Nghĩa là:** 
- ❌ User KHÔNG THỂ access: `yoursite.com/config/lixitet.php`
- ✅ PHP CÓ THỂ include: `require_once 'config/lixitet.php'`

## 📄 FILES

### `lixitet.php` (254 lines)
**Mục đích:** Pricing configuration cho chiến dịch Tết 2026

**Chứa:**
- Campaign dates (start/end)
- Pricing plans: tet_6m (299K), tet_12m (399K)
- Discount logic (63%, 69%)
- Helper functions: getTetPricingPlan(), isTetCampaignActive()

**Tại sao phải bảo mật:**
- Chứa pricing strategy (competitor không được xem)
- Có thể thay đổi giá động (không muốn leak logic)
- Best practice: Config files luôn nằm ngoài public

## 🔗 USAGE

### Trong Root Files (tet-promo.php, tet-checkout.php)
```php
require_once 'config/lixitet.php';
```

### Trong Admin Files (admin/orders.php)
```php
require_once '../config/lixitet.php';
```

## ⚠️ QUAN TRỌNG

1. **KHÔNG BAO GIỜ** xóa `.htaccess` trong thư mục này
2. **KHÔNG BAO GIỜ** di chuyển config files ra public
3. Khi thêm config mới, luôn để trong `config/`

## 📊 STRUCTURE RECOMMENDATION

```
timngachchuan/
├── config/                    [SECURE - Denied by .htaccess]
│   ├── .htaccess
│   ├── lixitet.php           [Tet pricing]
│   └── README.md             [This file]
│
├── includes/                  [PUBLIC - PHP includes]
│   ├── session.php
│   ├── functions.php
│   └── pricing_data.php
│
└── ... (public files)
```

## 🚀 BEST PRACTICES

### ✅ DO:
- Đặt pricing logic trong `config/`
- Đặt API keys trong `config/`
- Đặt database credentials trong `config/`

### ❌ DON'T:
- Đặt sensitive data trong `includes/` (public accessible)
- Hardcode passwords trong code
- Commit sensitive config vào Git

---

**Last Updated:** 2026-02-17  
**Security Level:** 🔒 HIGH
