# 🔍 ZALO MODULE - ROOT CAUSE ANALYSIS

## 📊 TIMELINE PHÁT HIỆN VẤN ĐỀ

**Ngày 26/11/2025** - User báo lỗi:
> "Ứng dụng luôn bị kẹt lại ở pulling sync friends 0%"
> SQL Error: "Field 'zalo_account_id' doesn't have a default value"

---

## 🔎 QUÁ TRÌNH ĐIỀU TRA

### **Bước 1: Phân tích logs**
- ✅ Phát hiện HTTP 429 (Rate Limit) từ Zalo API
- ✅ Frontend polling mỗi 1 giây → quá nhanh
- ✅ `getFriends()` return [] khi lỗi → không update cache → infinite loop

### **Bước 2: Kiểm tra code hiện tại**
- ✅ Phát hiện comment "🔥 RESTRUCTURE FIX: Friends are now shared via zalo_id"
- ✅ Code gán: `$friendDataNormalized['zalo_account_id'] = $account->zalo_id`
- ✅ Nhưng `$account->zalo_id` có thể NULL → SQL error!

### **Bước 3: Kiểm tra database structure**
```sql
-- Cấu trúc hiện tại:
zalo_friends.zalo_account_id bigint NOT NULL
  FOREIGN KEY → zalo_accounts.id (INTEGER)

-- Code đang gán:
zalo_account_id = $account->zalo_id (STRING hoặc NULL)
```

**❌ MISMATCH! Code mới + Database cũ = BROKEN!**

### **Bước 4: Tìm backup cũ**
Kiểm tra các backup trên VPS:

| Date | File | `zalo_account_id` | Status |
|------|------|-------------------|--------|
| 23/11 | `backup_code_20251123_150950.tar.gz` | `$account->id` | ✅ ĐÚNG |
| 24/11 | `vps_backup_20251124.tar.gz` | `$account->id` | ✅ ĐÚNG |
| 26/11 | `laravel_app.tar.gz` | `$account->zalo_id` | ❌ SAI |

**Kết luận:** Thay đổi xảy ra giữa **24/11** và **26/11**

### **Bước 5: Phát hiện folder backup**
```
_backups/session-sharing-20251126-002217/
```

**Thời điểm chính xác:** 26/11/2025 lúc 00:22:17

---

## 🐛 ROOT CAUSE

### **Nguyên nhân chính:**

Ai đó đã cố gắng implement tính năng "Session Sharing" vào **26/11 00:22**:

**Ý tưởng:**
- Share friends/groups giữa nhiều system accounts
- Dùng `zalo_id` (string Zalo account ID) làm identifier thay vì `account->id` (integer)

**Thực tế triển khai:**
1. ✅ Đã backup code cũ vào `_backups/session-sharing-20251126-002217/`
2. ✅ Đã update `ZaloCacheService.php` để dùng `$account->zalo_id`
3. ✅ Đã update `ZaloController.php` để query by `zalo_id`
4. ✅ Đã tạo migrations trong `database/migrations/_backup_zalo_sharing_nov25/`
5. ❌ **NHƯNG KHÔNG CHẠY MIGRATIONS!**

**Kết quả:**
```
Code NEW (uses zalo_id)  +  Database OLD (expects account->id)  =  💥 BROKEN
```

---

## 📋 CÁC VẤN ĐỀ PHÁT SINH

### **Vấn đề 1: SQL Error**
```
Field 'zalo_account_id' doesn't have a default value
```
**Nguyên nhân:**
- Code gán `zalo_account_id = $account->zalo_id`
- `zalo_id` có thể NULL cho account mới
- Database không chấp nhận NULL vì có FK constraint

### **Vấn đề 2: Stuck at 0%**
**Nguyên nhân:**
- Rate limit 429 → `getFriends()` return []
- Empty array → không update cache
- Auto-trigger lại → infinite loop

### **Vấn đề 3: Spam requests**
**Nguyên nhân:**
- Frontend polling 1s (quá nhanh)
- Không có exponential backoff khi rate limit

---

## ✅ GIẢI PHÁP ĐÃ ÁP DỤNG

### **Fix 1: Revert ZaloCacheService (26/11)**
```php
// BEFORE (BROKEN):
$friendDataNormalized['zalo_account_id'] = $account->zalo_id;
$friend = ZaloFriend::updateOrCreate(['zalo_user_id' => $zaloUserId], ...);

// AFTER (FIXED):
$friend = ZaloFriend::updateOrCreate([
    'zalo_account_id' => $account->id,  // Integer FK
    'zalo_user_id' => $zaloUserId
], ...);
```

### **Fix 2: Giảm polling frequency**
- Frontend: 1s → 3s (-66% requests)

### **Fix 3: Exception handling**
- `getFriends()`: throw exception thay vì return []
- `getGroups()`: throw exception thay vì return []

### **Fix 4: Exponential backoff**
- Zalo-service: retry 3 lần với delays 2s, 4s, 8s

### **Fix 5: Tăng sync lock**
- 5 phút → 10 phút

---

## 📝 BÀI HỌC

### **1. Migration phải đi cùng code**
Nếu thay đổi cấu trúc database:
- ✅ Tạo migrations
- ✅ Chạy migrations ngay lập tức
- ✅ Test kỹ sau khi migrate
- ❌ KHÔNG được update code mà không chạy migrations!

### **2. Backup trước khi refactor lớn**
- ✅ Đã backup vào `_backups/session-sharing-*` (tốt!)
- ❌ Nhưng không rollback khi phát hiện lỗi (xấu!)

### **3. Test kỹ tính năng mới**
- Tính năng "session sharing" chưa hoàn thành đầy đủ
- Nên test trên staging trước khi deploy production

### **4. Foreign key constraints rất quan trọng**
- FK constraint đã bảo vệ database khỏi corrupt data
- SQL error giúp phát hiện lỗi sớm

---

## 🎯 KHUYẾN NGHỊ

### **Nếu muốn implement "Session Sharing" lại:**

1. **Chạy đầy đủ migrations:**
   ```bash
   mv database/migrations/_backup_zalo_sharing_nov25/*.php database/migrations/
   php artisan migrate
   ```

2. **Update Models:**
   - Add scope `forAccount($zaloId)` in ZaloFriend model
   - Add scope `forAccount($zaloId)` in ZaloGroup model

3. **Test kỹ càng:**
   - Test login mới
   - Test sync friends/groups
   - Test với nhiều accounts
   - Test xóa/thêm friends

4. **Backup database trước:**
   ```bash
   mysqldump -u root school_db > backup_before_session_sharing.sql
   ```

### **Nếu giữ cấu trúc cũ (RECOMMENDED):**
- ✅ Giữ code như hiện tại (đã revert)
- ✅ Xóa migrations trong `_backup_zalo_sharing_nov25`
- ✅ Mỗi account có friends/groups riêng biệt
- ✅ Ổn định, ít rủi ro

---

## 📊 TỔNG KẾT

**Cấu trúc cuối cùng:**
```
zalo_accounts
  └─ id (PK, integer)
  └─ zalo_id (string, nullable)

zalo_friends
  └─ zalo_account_id (FK → zalo_accounts.id)  ✅ INTEGER
  └─ zalo_user_id (string, Zalo friend ID)

zalo_groups
  └─ zalo_account_id (FK → zalo_accounts.id)  ✅ INTEGER
  └─ zalo_group_id (string, Zalo group ID)
```

**Status:** ✅ WORKING - Đã revert về code ngày 24/11
