# 📖 HƯỚNG DẪN CÀI ĐẶT SEPAY WEBHOOK
## Tự động kích hoạt đơn hàng khi nhận thanh toán

---

## 🎯 TỔNG QUAN

Hệ thống sử dụng **SePay** để tự động nhận diện chuyển khoản và kích hoạt gói cước.

### ⚠️ QUAN TRỌNG: PHÂN BIỆT 2 HỆ THỐNG NDGROUP

| Domain | API Key | Format | Merchant Code |
|--------|---------|--------|---------------|
| **ndgroupmedia.com** (WordPress) | `NDGROUP2026` | `MLV123P456` | `MLV` |
| **timngachchuan.com** (Pure PHP) | `TIMNGACH2026` | `NDGROUP 4567 TRIAL` | `TIMNGACH` |

**Lý do tách biệt:**
- ✅ Tránh xung đột API keys
- ✅ Dễ dàng track giao dịch theo domain
- ✅ Không nhầm lẫn khi SePay gửi webhook

---

## 📋 BƯỚC 1: CHUẨN BỊ TỪ SEPAY

### 1.1. Đăng ký tài khoản SePay

1. Truy cập: `https://sepay.vn` (hoặc URL do SePay cung cấp)
2. Đăng ký tài khoản doanh nghiệp
3. Xác thực thông tin:
   - Tên công ty: **NDGroup Vietnam**
   - MST: [Điền mã số thuế]
   - Số TK ngân hàng: [Số TK cần monitor]

### 1.2. Lấy thông tin API

Sau khi đăng ký, bạn sẽ nhận được:

```
✅ API Key / Secret Key: _______________
✅ Webhook URL: https://timngachchuan.com/sepay_webhook.php
✅ Merchant Code (nếu có): _______________
✅ Bank Account: _______________
```

**Lưu ý:** 
- API Key nên là `TIMNGACH2026` để dễ nhớ
- Merchant Code nên là `TIMNGACH` để phân biệt

---

## 🔧 BƯỚC 2: CẤU HÌNH WEBSITE

### 2.1. Cập nhật API Key trong code

**File:** `sepay_webhook.php` (Line 47-63)

```php
// 🔐 SEPAY API KEY CONFIGURATION
// Use TIMNGACH prefix to AVOID CONFLICT with ndgroupmedia.com
$apiKey = 'TIMNGACH2026';  // ← Thay bằng key từ SePay
```

**Cách làm:**
1. Mở file `sepay_webhook.php`
2. Tìm line 58: `$apiKey = 'TIMNGACH2026';`
3. Thay bằng API Key thật từ SePay
4. Lưu file

### 2.2. Cấu hình permissions

```bash
# SSH vào server
cd /path/to/timngachchuan23T3

# Set permissions cho webhook file
chmod 644 sepay_webhook.php

# Set permissions cho data folder
chmod 755 data/
chmod 644 data/*.json

# Tạo log file nếu chưa có
touch data/sepay_webhook.log
chmod 644 data/sepay_webhook.log
```

### 2.3. Kiểm tra SSL Certificate

Webhook **BẮT BUỘC** phải dùng HTTPS:

```bash
# Check SSL certificate
curl -I https://timngachchuan.com/sepay_webhook.php

# Kết quả mong đợi:
# HTTP/2 200 
# (không phải lỗi SSL)
```

---

## ⚙️ BƯỚC 3: CẤU HÌNH TRÊN SEPAY DASHBOARD

### 3.1. Đăng nhập SePay Dashboard

```
URL: https://sepay.vn/dashboard
Username: _______________
Password: _______________
```

### 3.2. Thiết lập Webhook Endpoint

**Menu:** Settings → Webhooks → Add New Webhook

**Thông tin cần điền:**

| Field | Giá trị | Ghi chú |
|-------|---------|---------|
| **Webhook Name** | `TimNgachChuan Auto-Activation` | Dễ nhận diện |
| **Webhook URL** | `https://timngachchuan.com/sepay_webhook.php` | **BẮT BUỘC HTTPS** |
| **HTTP Method** | `POST` | |
| **Content Type** | `application/json` | |
| **API Key / Secret** | `TIMNGACH2026` | Khớp với code |
| **Status** | `Active` | Kích hoạt ngay |

**Screenshot mẫu:**
```
┌─────────────────────────────────────────┐
│ Add New Webhook                         │
├─────────────────────────────────────────┤
│ Name: TimNgachChuan Auto-Activation    │
│ URL: https://timngachchuan.com/...     │
│ Method: POST ☑                          │
│ Content-Type: application/json ☑        │
│ API Key: TIMNGACH2026                   │
│ Status: Active ☑                        │
│                                         │
│ [Save Webhook]                          │
└─────────────────────────────────────────┘
```

### 3.3. Cấu hình Bank Account Monitor

**Menu:** Settings → Bank Accounts → Add Account

**Thông tin:**

| Field | Giá trị |
|-------|---------|
| **Bank Name** | MB Bank (hoặc bank của bạn) |
| **Account Number** | `123456789` |
| **Account Name** | NDGroup Vietnam |
| **Monitor** | `Yes` |

### 3.4. Test Webhook

**Menu:** Settings → Webhooks → Test

**Thông tin test:**

```
Test Amount: 1000
Test Content: NDGROUP 9999 TRIAL
```

**Kết quả mong đợi:**
```
✅ Webhook delivered successfully
Response: {"status":"success","message":"Order processed"}
```

---

## 🧪 BƯỚC 4: KIỂM TRA END-TO-END

### 4.1. Tạo đơn hàng test

**Chọn 1 trong các gói sau:**

| Gói | Thời hạn | Giá | Transfer Content |
|-----|----------|-----|------------------|
| **Dùng Thử** | 3 ngày | 39,000đ | `NDGROUP XXXX TRIAL` |
| **Basic** | 1 tháng | 99,000đ | `NDGROUP XXXX BASIC` |
| **Standard** | 3 tháng | 252,450đ | `NDGROUP XXXX 3M` |
| **Pro** | 6 tháng | 415,800đ | `NDGROUP XXXX 6M` |
| **Premium** | 12 tháng | 653,400đ | `NDGROUP XXXX 12M` |

**Các bước:**

1. Truy cập: `https://timngachchuan.com/checkout.php?plan=trial` (hoặc `basic`, `3m`, `6m`, `12m`)
2. Điền thông tin:
   - Username: `testuser`
   - Phone: `0901234567`
   - Email: `test@example.com`
3. Click "XÁC NHẬN ĐÃ CHUYỂN KHOẢN"

### 4.2. Xem nội dung chuyển khoản

Hệ thống hiển thị:

```
┌─────────────────────────────────────┐
│ Ngân hàng: MB Bank                  │
│ Số TK: 123456789                    │
│ Nội dung CK: NDGROUP 4567 TRIAL     │
│ Mã tham chiếu: NDG2303A1B2          │
└─────────────────────────────────────┘
```

**Note lại:**
- Số tiền: Theo bảng giá ở trên (39K, 99K, 252K, 415K, hoặc 653K)
- Nội dung: `NDGROUP 4567 TRIAL` (hoặc BASIC/3M/6M/12M)

### 4.3. Chuyển khoản thật

**Mở app ngân hàng và chuyển:**

```
Người thụ hưởng: NDGroup Vietnam
Số TK: [Your SePay account number]
Ngân hàng: [Bank name from SePay]
Số tiền: [Chọn theo gói]
  • Dùng Thử: 39,000đ
  • Basic: 99,000đ
  • Standard (3 tháng): 252,450đ
  • Pro (6 tháng): 415,800đ
  • Premium (12 tháng): 653,400đ
Nội dung: NDGROUP 4567 TRIAL (hoặc BASIC/3M/6M/12M)
```

**⚠️ QUAN TRỌNG:**
- ✅ Số tiền phải **CHÍNH XÁC** theo bảng giá
- ✅ Nội dung phải đúng format: `NDGROUP XXXX PLAN`
- ✅ 4 số cuối điện thoại phải khớp với user đã đăng ký

### 4.4. Kiểm tra webhook log

**SSH vào server:**
```bash
cd /path/to/timngachchuan23T3/data
tail -f sepay_webhook.log
```

**Log thành công:**
```
2026-03-23 15:30:45 - IP: 14.225.1.1
Amount: 99000 | Content: 'NDGROUP 4567 TRIAL'
Transaction ID: VQ2026032301234
Reference Code: NDG2303A1B2
Merchant Code: TIMNGACH
Bank Code: MB | Account: 123456789

✅ MATCHED by phone last 4: 4567 → username='testuser'
✅ Order found! Username: testuser, Plan: trial
✅ User tier updated successfully!
```

### 4.5. Kiểm tra user được kích hoạt

```bash
cd /path/to/timngachchuan23T3/data
cat users.json | grep -A 20 '"testuser"'
```

**Kết quả:**
```json
{
  "username": "testuser",
  "tier": "trial",
  "tier_updated_at": "2026-03-23 15:30:45",
  "tier_expires_at": "2026-03-26 15:30:45"
}
```

### 4.6. Kiểm tra orders.json

```bash
cat orders.json | grep -A 15 '"testuser_trial_'
```

**Kết quả:**
```json
{
  "order_id": "testuser_trial_1234567890",
  "username": "testuser",
  "plan": "trial",
  "amount": 99000,
  "status": "paid",
  "payment_method": "sepay",
  "transaction_id": "VQ2026032301234",
  "created_at": "2026-03-23 15:25:00",
  "updated_at": "2026-03-23 15:30:45"
}
```

---

## 🔍 BƯỚC 5: GIÁM SÁT & DEBUG

### 5.1. Xem log real-time

```bash
# Xem log webhook
tail -f data/sepay_webhook.log

# Xem log với filter
tail -f data/sepay_webhook.log | grep "✅ MATCHED"
tail -f data/sepay_webhook.log | grep "❌"
```

### 5.2. Các lỗi thường gặp

#### ❌ Lỗi: "Invalid API Key"

**Triệu chứng:**
```
❌ Invalid API Key: Expected 'TIMNGACH2026', got 'XYZ123'
```

**Nguyên nhân:** API Key không khớp giữa SePay dashboard và code

**Giải pháp:**
1. Check SePay Dashboard → Settings → Webhooks
2. Copy API Key chính xác
3. Update trong `sepay_webhook.php` line 58

---

#### ❌ Lỗi: "Could not extract username"

**Triệu chứng:**
```
❌ Could not extract username from content='NDGROUP 9999 TRIAL'
```

**Nguyên nhân:** 
- Phone 9999 không tồn tại trong users.json
- User chưa điền phone

**Giải pháp:**
1. Kiểm tra transfer content đúng format: `NDGROUP XXXX PLAN`
2. Đảm bảo user đã điền phone trong form
3. Check users.json có user với phone match

---

#### ❌ Lỗi: "Wrong merchant code"

**Triệu chứng:**
```
⚠️ REJECTED: Wrong merchant code! Expected 'TIMNGACH', got 'MLV'
```

**Nguyên nhân:** Nhầm với merchant code của ndgroupmedia.com

**Giải pháp:**
1. Uncomment block validation trong `sepay_webhook.php` line 140
2. Set đúng merchant code: `$yourMerchantCode = 'TIMNGACH';`

---

#### ❌ Lỗi: "WordPress format detected"

**Triệu chứng:**
```
⚠️ REJECTED: WordPress format detected! Content='MLV123P456'
```

**Nguyên nhân:** Transfer content bắt đầu bằng MLV (format WordPress)

**Giải pháp:**
- Đây là protection chống nhầm domain
- Đảm bảo transfer content format: `NDGROUP XXXX PLAN`
- KHÔNG dùng `MLV...` format

---

### 5.3. Enable API Key Validation (Security)

Mặc định validation đang comment. Để enable:

**File:** `sepay_webhook.php` Line 87-96

```php
// Remove /* and */ to enable validation
if (!empty($apiKey) && $requestApiKey !== $apiKey) {
    $log = "❌ Invalid API Key: Expected '$apiKey', got '$requestApiKey'\n";
    file_put_contents($logFile, $log, FILE_APPEND | LOCK_EX);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}
```

---

## 📊 BƯỚC 6: MONITORING DAILY

### 6.1. Checklist hàng ngày

- [ ] Check webhook logs: `tail -n 50 data/sepay_webhook.log`
- [ ] Verify successful activations: `grep "✅ User tier updated" data/sepay_webhook.log`
- [ ] Check failed payments: `grep "❌" data/sepay_webhook.log`
- [ ] Review orders.json: `cat orders.json | jq '.[] | select(.status=="paid")'`

### 6.2. Weekly Reports

```bash
# Số lượng đơn hàng thành công trong tuần
grep "✅ User tier updated" data/sepay_webhook.log | wc -l

# Tổng revenue từ SePay
grep "Amount:" data/sepay_webhook.log | awk '{sum+=$2} END {print sum}'

# Các lỗi thường gặp
grep "❌" data/sepay_webhook.log | sort | uniq -c
```

---

## 🎯 PHỤ LỤC A: FORMAT CHUYỂN KHOẢN

### ✅ Format đúng:

```
NDGROUP 4567 TRIAL      ← 4 số cuối điện thoại + Gói Dùng Thử (39K)
NDGROUP 4567 BASIC      ← 4 số cuối điện thoại + Gói Basic (99K)
NDGROUP 4567 3M         ← 4 số cuối điện thoại + Gói 3 tháng (252K)
NDGROUP 4567 6M         ← 4 số cuối điện thoại + Gói 6 tháng (415K)
NDGROUP 4567 12M        ← 4 số cuối điện thoại + Gói 12 tháng (653K)
NDGROUP A1B2C3D4 TRIAL  ← Unique code (8 chars) + Plan
```

### ❌ Format sai:

```
nguyenduy123 trial      ← Username format (cũ)
MLV123P456              ← WordPress format (sai domain)
NDGROUP                 ← Thiếu plan
TRIAL                   ← Thiếu NDGROUP prefix
NDGROUP 4567            ← Thiếu tên plan
```

---

## 🎯 PHỤ LỤC B: BẢNG GIÁ CHI TIẾT

### Pricing Structure (Cập nhật: 2026-03-23)

| Plan ID | Name | Duration | Original Price | Sale Price | Discount | Per Day |
|---------|------|----------|---------------|------------|----------|---------|
| **trial** | Dùng Thử | 3 ngày | 99,000đ | **39,000đ** | 61% OFF | ~13K |
| **1m** | Basic | 1 tháng | 299,000đ | **99,000đ** | 67% OFF | ~3.3K |
| **3m** | Standard | 3 tháng | 297,000đ | **252,450đ** | 15% OFF | ~2.8K |
| **6m** | Pro | 6 tháng | 594,000đ | **415,800đ** | 30% OFF | ~2.3K |
| **12m** | Premium | 12 tháng | 1,188,000đ | **653,400đ** | 45% OFF | ~1.8K |

### Features Comparison:

| Feature | Trial | Basic | Standard | Pro | Premium |
|---------|-------|-------|----------|-----|---------|
| Searches | ♾️ Unlimited | ♾️ Unlimited | ♾️ Unlimited | ♾️ Unlimited | ♾️ Unlimited |
| View Results | ✅ Yes | ✅ Yes | ✅ Yes | ✅ Yes | ✅ Yes |
| Export CSV | ❌ No | ✅ Yes | ✅ Yes | ✅ Yes | ✅ Yes |
| Thumbnail AI | ❌ No | ✅ Yes | ✅ Yes | ✅ Yes | ✅ Yes |
| Deep AI (Gemini) | ❌ No | ❌ No | ❌ No | ✅ Yes | ✅ Yes |
| Priority Support | ❌ No | ✅ Yes | ✅ Yes | ✅ VIP | ✅ VIP |
| Affiliate 20% | ❌ No | ✅ Yes | ✅ Yes | ✅ Yes | ✅ Yes |
| **Best For** | Testing | Starters | Short-term | Serious Users | **Best Value** |

### Transfer Content Mapping:

```php
// URL Parameter → Plan Name → Transfer Content
?plan=trial   → "TRIAL"   → NDGROUP XXXX TRIAL
?plan=1m      → "BASIC"   → NDGROUP XXXX BASIC
?plan=3m      → "3M"      → NDGROUP XXXX 3M
?plan=6m      → "6M"      → NDGROUP XXXX 6M
?plan=12m     → "12M"     → NDGROUP XXXX 12M
```

---

## 🎯 PHỤ LỤC C: SEPAY WEBHOOK PAYLOAD

### Request Example:

```json
{
  "transaction_id": "VQ2026032301234",
  "reference_code": "NDG2303A1B2",
  "merchant_code": "TIMNGACH",
  "amount": 99000,
  "amount_original": 99000,
  "content": "NDGROUP 4567 TRIAL",
  "transfer_content": "NDGROUP 4567 TRIAL",
  "bank_code": "MB",
  "account_number": "123456789",
  "id": "12345",
  "referenceCode": "NDG2303A1B2"
}
```

### Headers:

```http
POST /sepay_webhook.php HTTP/1.1
Host: timngachchuan.com
Content-Type: application/json
Authorization: Bearer TIMNGACH2026
X-API-Key: TIMNGACH2026
```

---

## 🎯 PHỤ LỤC D: CÁC FILE LIÊN QUAN

| File | Purpose | Location |
|------|---------|----------|
| **sepay_webhook.php** | Webhook handler | Root folder |
| **checkout.php** | Generate transfer content | Root folder |
| **users.json** | User database | `data/` folder |
| **orders.json** | Order tracking | `data/` folder |
| **sepay_webhook.log** | Debug logs | `data/` folder |

---

## 📞 HỖ TRỢ

### Khi gặp vấn đề, cung cấp:

1. **Screenshot SePay Dashboard:**
   - Webhook settings
   - Recent transactions

2. **Logs từ server:**
   ```bash
   tail -n 100 data/sepay_webhook.log
   ```

3. **Error messages:**
   - Từ SePay dashboard
   - Từ browser console
   - Từ webhook response

4. **Test case details:**
   - Username
   - Phone number
   - Transfer content
   - Amount
   - Transaction time

---

## ✅ CHECKLIST HOÀN TẤT

- [ ] API Key configured in `sepay_webhook.php`
- [ ] Webhook URL setup on SePay dashboard
- [ ] SSL certificate valid
- [ ] File permissions correct (644/755)
- [ ] Test transaction completed successfully
- [ ] User tier updated after payment
- [ ] Order status changed to "paid"
- [ ] Logs being written properly
- [ ] Monitoring process established

---

**🎉 CHÚC MỪNG! Bạn đã hoàn tất cài đặt SePay webhook!**

---

*Tài liệu này được cập nhật lần cuối: 2026-03-23*
*Version: 1.0*
*Author: NDGroup Development Team*
